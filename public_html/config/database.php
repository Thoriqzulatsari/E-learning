<?php
// config.php - Configuration for database connection

define('DB_HOST', 'localhost');
define('DB_USER', 'u287442801_mini_elearning');
define('DB_PASS', 'Jawabarat123_');
define('DB_NAME', 'u287442801_mini_elearning');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>