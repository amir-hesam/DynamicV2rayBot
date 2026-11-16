<?php

return [
    'app_name' => 'VPN Master Bot & MiniApp',
    'version' => '1.0.0',
    'base_url' => getenv('APP_URL') ?: 'http://127.0.0.1:8000',
    'timezone' => 'Asia/Tehran',
    'session_secret' => 'vpn_bot_secure_random_key_' . md5(__DIR__),
];
