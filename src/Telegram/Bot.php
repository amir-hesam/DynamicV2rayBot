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

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : ['ok' => false, 'raw' => $response];
    }

    public function sendDocument(int|string $chatId, string $filePath, string $caption = '', array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = [
            'chat_id' => $chatId,
            'document' => new \CURLFile($filePath, 'text/plain', basename($filePath)),
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendDocument', $params);
    }

    public function sendMessage(int|string $chatId, string $text, array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendMessage', $params);
    }

    public function sendPhoto(int|string $chatId, string $photo, string $caption = '', array $replyMarkup = [], string $parseMode = 'HTML'): array
    {
        $params = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if (!empty($replyMarkup)) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }

        return $this->request('sendPhoto', $params);
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
        return $this->request('setWebhook', ['url' => $url]);
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
