<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data);
        $file = __DIR__ . '/../../views/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($file)) {
            http_response_code(404);
            die("View template not found: " . htmlspecialchars($template));
        }

        ob_start();
        include $file;
        $content = ob_get_clean();

        echo $content;
        exit;
    }
}
