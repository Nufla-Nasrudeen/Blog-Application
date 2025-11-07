<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$comment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($comment_id <= 0) {
    header('Location: index.php');
    exit();
}

// Get comment details and verify ownership
$stmt = $conn->prepare("SELECT blog_id, user_id FROM comment WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Comment not found';
    header('Location: index.php');
    exit();
}

$comment = $result->fetch_assoc();

// Check if user owns the comment
if ($comment['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'Unauthorized action';
    header('Location: view_blog.php?id=' . $comment['blog_id']);
    exit();
}

// Delete comment
$delete_stmt = $conn->prepare("DELETE FROM comment WHERE id = ? AND user_id = ?");
$delete_stmt->bind_param("ii", $comment_id, $_SESSION['user_id']);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = 'Comment deleted successfully';
} else {
    $_SESSION['error'] = 'Failed to delete comment';
}

$delete_stmt->close();
header('Location: view_blog.php?id=' . $comment['blog_id'] . '#comments');
exit();
?>