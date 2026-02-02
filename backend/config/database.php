<?php
/**
 * Database Configuration
 * AAC Assist - Augmentative and Alternative Communication Application
 *
 * Supports both Railway MySQL addon and local development
 * Railway automatically provides MYSQL_* environment variables
 */

declare(strict_types=1);

// Railway MySQL addon provides these environment variables:
// MYSQL_HOST, MYSQL_PORT, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD
// Also provides MYSQL_URL as a connection string

return [
    'host'     => getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306',
    'database' => getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'aac_assist',
    'username' => getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
