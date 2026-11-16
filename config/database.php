<?php

return [
    'driver' => 'sqlite',
    'sqlite' => [
        'database' => __DIR__ . '/../database/bot.db',
    ],
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'vpn_bot',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
