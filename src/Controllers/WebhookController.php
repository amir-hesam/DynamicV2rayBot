<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Telegram\Handler;

class WebhookController
{
    public function handle(Request $request): void
    {
        if (!\App\Telegram\WebhookAuth::verify()) {
            Response::json(\App\Telegram\WebhookAuth::rejectResponse(), 403);
        }

        $update = $request->json();

        if (!empty($update)) {
            $handler = new Handler();
            $handler->handleUpdate($update);
        }

        Response::json(['ok' => true]);
    }
}
