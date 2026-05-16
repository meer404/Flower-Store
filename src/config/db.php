<?php
declare(strict_types=1);

/**
 * Database Configuration and PDO Connection
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/env.php';

define('DB_HOST',    (string) env('DB_HOST',    'localhost'));
define('DB_NAME',    (string) env('DB_NAME',    'u997521431_bloomvine'));
define('DB_USER',    (string) env('DB_USER',    'u997521431_bloomvine'));
define('DB_PASS',    (string) env('DB_PASS',    'Bloom2026Vine'));
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    return $pdo;
}
