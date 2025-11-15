<?php
// _partials/config.php

// Define the absolute server path to your project's root directory.
// This is the directory that contains the '_partials' folder.
// dirname(__DIR__) gets the parent directory of the current file's directory (_partials), which is your project root.
define('BASE_PATH', dirname(__DIR__));

// Load environment variables from .env file
// Check if vendor/autoload.php exists (Composer autoloader)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
    
    // Load .env file
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// Load environment variables into constants with fallback defaults
define('DB_SERVER', $_ENV['DB_SERVER'] ?? 'localhost');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? '');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('BASE_URL', $_ENV['BASE_URL'] ?? '');
define('PAYSTACK_SECRET_KEY', $_ENV['PAYSTACK_SECRET_KEY'] ?? '');
define('PAYSTACK_PUBLIC_KEY', $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '');

// Start the session here so it's available everywhere.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>