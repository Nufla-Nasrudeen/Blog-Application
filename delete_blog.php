<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($blog_id <= 0) {
    header('Location: index.php');
    exit();
}

// Delete blog post (only if user owns it)
$stmt = $conn->prepare("DELETE FROM blogPost WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $blog_id, $_SESSION['user_id']);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $_SESSION['message'] = 'Blog post deleted successfully';
} else {
    $_SESSION['message'] = 'Failed to delete blog post or unauthorized';
}

$stmt->close();
header('Location: index.php');
exit();
?>