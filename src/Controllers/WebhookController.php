<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Telegram\Handler;

class WebhookController
{
    public function handle(Request $request): void
    {
        $update = $request->json();

        if (!empty($update)) {
            $handler = new Handler();
            $handler->handleUpdate($update);
        }

        Response::json(['ok' => true]);
    }
}
