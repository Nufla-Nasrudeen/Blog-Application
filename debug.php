<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug Information</h1>";

// Test PHP
echo "<h2>✅ PHP is working!</h2>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
echo "<h2>Database Connection Test:</h2>";

$host = 'sql212.infinityfree.com'; // Change if needed
$user = 'if0_40294128';      // Your DB username
$pass = 'nYdTM8G8aVqXp';          // Your DB password
$db = 'if0_40294128_blog_db1';     // Your DB name

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    echo "<br><strong>Fix:</strong> Update database credentials in config.php";
} else {
    echo "✅ Database connected successfully!<br>";
    
    // Test tables
    echo "<h3>Checking tables:</h3>";
    $tables = ['user', 'blogPost', 'category', 'comment', 'blogReaction'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
}

// Test file paths
echo "<h2>File Structure Test:</h2>";
$files = [
    'config.php',
    'index.php',
    'css/style.css',
    'js/editor.js',
    'uploads/'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<h2>Server Information:</h2>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

phpinfo();
?>