<?php

// Use environment variables for database credentials when they are available.
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'vegetable_system';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            // Report database errors instead of failing silently.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // Return each database row as an associative array.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Let MySQL handle prepared statements.
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit(
        'Database connection failed. Check the settings in config/database.php '
        . 'and import database.sql.'
    );
}
