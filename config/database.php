<?php
/**
 * Database connection (PDO).
 * Edit the constants below to match your local MySQL setup.
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'ict_systems_registry';
const DB_USER = 'root';
const DB_PASS = 'DREAMTEAM';
const DB_CHARSET = 'utf8mb4';

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Never leak connection details to the browser.
        error_log('Database connection failed: ' . $e->getMessage());
        die('We could not connect to the database. Please try again later.');
    }

    return $pdo;
}