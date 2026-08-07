<?php
declare(strict_types=1);

/**
 * Creates one reusable PDO connection with strict error handling.
 */
function database(array $db): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    try {
        $connection = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        http_response_code(503);
        $safeMessage = 'Database connection failed. Run setup.php and verify the database settings.';
        if (PHP_SAPI === 'cli') {
            throw new RuntimeException($safeMessage, 0, $exception);
        }
        exit('<!doctype html><meta charset="utf-8"><title>Setup required</title><style>body{font:16px system-ui;max-width:720px;margin:10vh auto;padding:24px;color:#1e2a31}code{background:#eef1eb;padding:3px 6px;border-radius:5px}</style><h1>VaxCare needs setup</h1><p>' . $safeMessage . '</p><p>From this project folder, run <code>php setup.php</code>.</p>');
    }

    return $connection;
}
