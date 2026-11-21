<?php

/**
 * Installer entry point when the document root is the /public directory
 * (e.g. `php -S 127.0.0.1:8000 -t public` or a hosting account pointed at /public).
 *
 * The real installer lives at the project root so that config/, database/ and
 * schema.sql paths always resolve correctly no matter which layout is used.
 */
require __DIR__ . '/../install.php';
