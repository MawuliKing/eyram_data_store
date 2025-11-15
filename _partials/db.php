<?php
// We need to start a session on every page to remember the logged-in user
session_start();

// Load configuration (which loads .env and defines constants)
require_once __DIR__ . '/config.php';

// --- DATABASE CONNECTION ---
// Database credentials are now loaded from .env file via config.php

// Create a connection to the database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
    // If connection fails, stop the script and show an error
    die("ERROR: Could not connect. " . $conn->connect_error);
}

// BASE_URL is now defined in config.php from .env
?>