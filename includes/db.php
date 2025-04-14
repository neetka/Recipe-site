<?php
require_once 'config.php';

// Create database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("A system error occurred. Please try again later.");
}

// Set connection timeout
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

// Set charset
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error setting charset: " . $conn->error);
    die("A system error occurred. Please try again later.");
}

// Enable error reporting for mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>