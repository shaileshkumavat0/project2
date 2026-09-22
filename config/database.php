<?php

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: 'if0_42415880_startup_portal');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/startup-portal');

define('UPLOAD_DIR_IDEAS', __DIR__ . '/../uploads/ideas/');
define('UPLOAD_DIR_DOCS', __DIR__ . '/../uploads/documents/');
define('UPLOAD_DIR_PROFILES', __DIR__ . '/../uploads/profiles/');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $dsn = "mysql:host=" . DB_HOST .
               ";port=" . DB_PORT .
               ";dbname=" . DB_NAME .
               ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                $options
            );
        } catch (PDOException $e) {
            die('Database connection failed.');
        }
    }

    return $pdo;
}