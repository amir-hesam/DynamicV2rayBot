<?php

namespace App\Telegram;

use App\Models\Setting;

/**
 * Validates Telegram Mini App (WebApp) initData using the official HMAC-SHA256
 * scheme described by Telegram: https://core.telegram.org/bots/webapps
 *
 * Pure PHP — no extensions beyond hash/openssl (available on every cPanel host).
 */
class WebAppAuth
{
    /**
     * Maximum accepted age of initData (seconds). Re-opened Mini App sessions
     * always carry fresh initData, so a day is plenty.
     */
    public const MAX_AGE = 86400;

    /**
     * @return array{valid:bool, user:?array, auth_date:int, reason:string}
     */
    public static function validate(?string $initData): array
    {
        $fail = static fn(string $reason) => ['valid' => false, 'user' => null, 'auth_date' => 0, 'reason' => $reason];

        if ($initData === null || $initData === '' || !str_contains($initData, 'hash=')) {
            return $fail('missing_init_data');
        }

        $botToken = (string)Setting::get('bot_token', '');
        if ($botToken === '') {
            return $fail('bot_token_not_configured');
        }

        parse_str($initData, $data);
        // parse_str() always fills an array; only the signature hash matters
        if (empty($data['hash']) || !is_string($data['hash'])) {
            return $fail('missing_hash');
        }

        $hash = $data['hash'];
        unset($data['hash']);
        ksort($data);

        $pairs = [];
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                // Nested structures are not expected in initData; serialize deterministically
                ksort($v);
                $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $pairs[] = $k . '=' . $v;
        }
        $dataCheckString = implode("\n", $pairs);

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculated, $hash)) {
            return $fail('bad_hash');
        }

        $authDate = (int)($data['auth_date'] ?? 0);
        if ($authDate <= 0 || (time() - $authDate) > self::MAX_AGE) {
            return $fail('expired');
        }

        $user = null;
        if (!empty($data['user']) && is_string($data['user'])) {
            $decoded = json_decode($data['user'], true);
            if (is_array($decoded) && !empty($decoded['id'])) {
                $user = [
                    'id' => (string)$decoded['id'],
                    'first_name' => (string)($decoded['first_name'] ?? ''),
                    'username' => (string)($decoded['username'] ?? ''),
                    'last_name' => (string)($decoded['last_name'] ?? ''),
                ];
            }
        }

        if ($user === null) {
            return $fail('missing_user');
        }

        return ['valid' => true, 'user' => $user, 'auth_date' => $authDate, 'reason' => 'ok'];
    }

    /**
     * Convenience: validated user id or null.
     */
    public static function validatedTelegramId(?string $initData): ?string
    {
        $res = self::validate($initData);
        return $res['valid'] ? $res['user']['id'] : null;
    }
}
