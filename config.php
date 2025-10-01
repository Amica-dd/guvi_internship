<?php
// Database (MySQL) connection using PDO and prepared statements only
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $db_host = getenv('DB_HOST') ?: '127.0.0.1';
    $db_name = getenv('DB_NAME') ?: 'auth_demo';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: '';
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $db_user, $db_pass, $opts);
    return $pdo;
}

function ensureSchema(): void {
    $pdo = getPDO();
    $sql = <<<SQL
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        age INT NULL,
        dob DATE NULL,
        contact VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    SQL;
    $pdo->exec($sql);
}

ensureSchema();
