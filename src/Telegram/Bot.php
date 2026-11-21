<?php

namespace App\Telegram;

use App\Models\Setting;

class Bot
{
    private string $token;
    private string $apiUrl;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?: (Setting::get('bot_token') ?? '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}/";
    }

    public function request(string $method, array $params = []): array
    {
        if (empty($this->token)) {
            return ['ok' => false, 'description' => 'Bot token is empty'];
        }

        $ch = curl_init($this->apiUrl . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        // Check if params contain a CURLFile
        $hasFile = false;
        foreach ($params as $val) {
            if ($val instanceof \CURLFile) {
                $hasFile = true;
                break;
            }
        }

        if ($hasFile) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            @curl_close($ch);
        }

        // Some minimal shared hosts ship cURL without an up-to-date CA bundle.
        // Retry once without peer verification (and log it) instead of leaving
        // the whole bot dead on that host.
        $sslFailure = $response === false && (
            $curlErrNo === 60 /* CURLE_SSL_CACERT */
            || stripos($curlErr, 'certificate') !== false
            || stripos($curlErr, 'SSL') !== false
            || stripos($curlErr, 'CA ') !== false
        );
        if ($sslFailure) {
            error_log("Telegram Bot API: SSL verification failed ({$curlErr}); retrying without CA verification");
            $ch = curl_init($this->apiUrl . $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $hasFile ? $params : http_build_query($params));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            if (PHP_VERSION_ID < 80500) {
                @curl_close($ch);
            }
        }

        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : ['ok' => false, 'raw' => (string)$response];
    }

    public function sendDocument(int|string $chatId, string $filePath, string $caption = '', array $replyMarkup = [], string $parseMode = 'HTML', ?int $messageThreadId = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'document' => new \CURLFile($filePath, 'text/plain', basename($filePath)),
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if ($messageThreadId !== null) {
            $params['message_thread_id'] = $messageThreadId;
        }

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendDocument', $params);
    }

    public function sendMessage(int|string $chatId, string $text, array $replyMarkup = [], string $parseMode = 'HTML', ?int $messageThreadId = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($messageThreadId !== null) {
            $params['message_thread_id'] = $messageThreadId;
        }

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendMessage', $params);
    }

    public function sendPhoto(int|string $chatId, string|\CURLFile $photo, string $caption = '', array $replyMarkup = [], string $parseMode = 'HTML', ?int $messageThreadId = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if ($messageThreadId !== null) {
            $params['message_thread_id'] = $messageThreadId;
        }

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendPhoto', $params);
    }

    /**
     * Create a forum topic in a supergroup
     */
    public function createForumTopic(int|string $chatId, string $name, ?int $iconColor = null, ?string $iconCustomEmojiId = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'name' => $name,
        ];

        if ($iconColor !== null) {
            $params['icon_color'] = $iconColor;
        }
        if ($iconCustomEmojiId !== null) {
            $params['icon_custom_emoji_id'] = $iconCustomEmojiId;
        }

        return $this->request('createForumTopic', $params);
    }

    /**
     * Edit a forum topic
     */
    public function editForumTopic(int|string $chatId, int $messageThreadId, ?string $name = null, ?string $iconCustomEmojiId = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_thread_id' => $messageThreadId,
        ];

        if ($name !== null) {
            $params['name'] = $name;
        }
        if ($iconCustomEmojiId !== null) {
            $params['icon_custom_emoji_id'] = $iconCustomEmojiId;
        }

        return $this->request('editForumTopic', $params);
    }

    /**
     * Delete a forum topic
     */
    public function deleteForumTopic(int|string $chatId, int $messageThreadId): array
    {
        return $this->request('deleteForumTopic', [
            'chat_id' => $chatId,
            'message_thread_id' => $messageThreadId,
        ]);
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('editMessageText', $params);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 25): array
    {
        return $this->request('getUpdates', [
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
        ]);
    }

    public function setWebhook(string $url): array
    {
        // Register a secret token so incoming updates can be authenticated
        // (see WebhookAuth). Persist it only after Telegram confirms.
        $secret = (string)Setting::get('webhook_secret', '');
        if ($secret === '') {
            $secret = bin2hex(random_bytes(16));
        }

        $res = $this->request('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
        ]);

        if (!empty($res['ok'])) {
            Setting::set('webhook_secret', $secret);
        } else {
            // Older/limited API: retry without the secret and disable verification
            // so the bot does not end up rejecting every update.
            $fallback = $this->request('setWebhook', ['url' => $url]);
            if (!empty($fallback['ok'])) {
                Setting::set('webhook_secret', '');
                return $fallback;
            }
        }

        return $res;
    }

    public function deleteWebhook(): array
    {
        return $this->request('deleteWebhook', ['drop_pending_updates' => false]);
    }

    public function getMe(): array
    {
        return $this->request('getMe');
    }
}
