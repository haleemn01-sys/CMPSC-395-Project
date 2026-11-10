<?php
// Shared database connection (PDO), based on the week 11 CRUD demo.
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $config_file = __DIR__ . '/config.php';
    if (!file_exists($config_file)) {
        die('Missing config.php. Copy config.example.php to config.php and add your MariaDB login.');
    }
    $config = require $config_file;

    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    } catch (PDOException $e) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }
    return $pdo;
}

// Escape output so user-entered text can't inject HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return '$' . number_format((float) $value, 2);
}

// Simple one-time messages that survive a redirect.
function flash(string $message, string $type = 'success'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

const ORDER_STATUSES  = ['Pending', 'Preparing', 'Completed', 'Cancelled'];
const PAYMENT_METHODS = ['Cash', 'Card', 'Meal Plan'];
const MENU_CATEGORIES = ['Coffee', 'Drinks', 'Bakery', 'Food'];
