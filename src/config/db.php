<?php
declare(strict_types=1);

/**
 * Database Configuration and PDO Connection
 * Bloom & Vine Flower Store
 * 
 * This file handles secure database connections using PDO with named parameters.
 * All database operations should use this connection to prevent SQL injection.
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloom_vine');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 * 
 * @return PDO Returns a PDO instance with error handling and prepared statements enabled
 * @throws PDOException If connection fails
 */
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
            PDO::ATTR_EMULATE_PREPARES   => false, // Use native prepared statements
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error in production, show generic message to user
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Please contact the administrator.');
        }
    }
    
    return $pdo;
}

