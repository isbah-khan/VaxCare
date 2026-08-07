<?php
declare(strict_types=1);

/**
 * Central application configuration.
 * Environment variables override the XAMPP-friendly development defaults.
 */
return [
    'app_name' => 'VaxCare',
    'base_url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),
    'timezone' => (string) (getenv('APP_TIMEZONE') ?: 'Asia/Karachi'),
    'db' => [
        'host' => (string) (getenv('DB_HOST') ?: '127.0.0.1'),
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => (string) (getenv('DB_DATABASE') ?: 'vaccination_management'),
        'user' => (string) (getenv('DB_USERNAME') ?: 'root'),
        'pass' => (string) (getenv('DB_PASSWORD') ?: ''),
        'charset' => 'utf8mb4',
    ],
];
