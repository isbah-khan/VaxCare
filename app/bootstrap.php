<?php
declare(strict_types=1);

/**
 * Shared bootstrap: starts a hardened session, loads helpers, and opens PDO.
 */
$config = require __DIR__ . '/config.php';
date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

$pdo = database($config['db']);
