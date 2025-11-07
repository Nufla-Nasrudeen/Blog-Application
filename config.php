<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
// Database configuration
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'blog_db');

define('DB_HOST', 'sql212.infinityfree.com'); // Your DB host
define('DB_USER', 'if0_40294128');           // Your DB username
define('DB_PASS', 'nYdTM8G8aVqXp');            // Your DB password
define('DB_NAME', 'if0_40294128_blog_db1');   // Your DB name
// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>