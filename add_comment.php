<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit();
}

$blog_id = intval($_POST['blog_id']);
$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'];

if (empty($content)) {
    $_SESSION['error'] = 'Comment cannot be empty';
    header('Location: view_blog.php?id=' . $blog_id);
    exit();
}

if ($blog_id <= 0) {
    header('Location: index.php');
    exit();
}

// Verify blog exists
$check_stmt = $conn->prepare("SELECT id FROM blogPost WHERE id = ?");
$check_stmt->bind_param("i", $blog_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

// Insert comment
$stmt = $conn->prepare("INSERT INTO comment (blog_id, user_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $blog_id, $user_id, $content);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Comment posted successfully!';
} else {
    $_SESSION['error'] = 'Failed to post comment';
}

$stmt->close();
header('Location: view_blog.php?id=' . $blog_id . '#comments');
exit();
?>