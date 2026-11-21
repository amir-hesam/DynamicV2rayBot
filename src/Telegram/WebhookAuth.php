<?php

namespace App\Telegram;

use App\Models\Setting;

/**
 * Verifies the X-Telegram-Bot-Api-Secret-Token header sent by Telegram when
 * the webhook was registered with a `secret_token`.
 *
 * Without this check anyone who finds the webhook URL can POST forged updates
 * (e.g. impersonate the admin's Telegram id and approve orders).
 *
 * Fail-open ONLY when no secret has ever been registered — meaning the webhook
 * was set by an older version of this code and Telegram does not send the
 * header at all. As soon as Bot::setWebhook() runs, verification is enforced.
 */
class WebhookAuth
{
    public const HEADER = 'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN';

    public static function verify(): bool
    {
        $secret = (string)Setting::get('webhook_secret', '');
        if ($secret === '') {
            return true; // legacy webhook registered without secret_token
        }

        $provided = $_SERVER[self::HEADER] ?? '';
        if (!is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($secret, $provided);
    }

    public static function rejectResponse(): array
    {
        return ['ok' => false, 'error' => 'unauthorized'];
    }
}
