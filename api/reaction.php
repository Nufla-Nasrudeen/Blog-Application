<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

$action = isset($data['action']) ? $data['action'] : '';
$blog_id = isset($data['blog_id']) ? intval($data['blog_id']) : 0;
$reaction_type = isset($data['reaction_type']) ? $data['reaction_type'] : 'like';
$user_id = $_SESSION['user_id'];

if ($blog_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid blog ID']);
    exit();
}

switch ($action) {
    case 'add':
        // Check if already reacted
        $check_stmt = $conn->prepare("SELECT id FROM blogReaction WHERE blog_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $blog_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing reaction
            $update_stmt = $conn->prepare("UPDATE blogReaction SET reaction_type = ? WHERE blog_id = ? AND user_id = ?");
            $update_stmt->bind_param("sii", $reaction_type, $blog_id, $user_id);
            $success = $update_stmt->execute();
            $message = 'Reaction updated';
        } else {
            // Insert new reaction
            $insert_stmt = $conn->prepare("INSERT INTO blogReaction (blog_id, user_id, reaction_type) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iis", $blog_id, $user_id, $reaction_type);
            $success = $insert_stmt->execute();
            $message = 'Reaction added';
        }
        
        // Get updated counts
        $counts_stmt = $conn->prepare("SELECT reaction_type, COUNT(*) as count FROM blogReaction WHERE blog_id = ? GROUP BY reaction_type");
        $counts_stmt->bind_param("i", $blog_id);
        $counts_stmt->execute();
        $counts_result = $counts_stmt->get_result();
        
        $counts = [];
        while ($row = $counts_result->fetch_assoc()) {
            $counts[$row['reaction_type']] = $row['count'];
        }
        
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'counts' => $counts,
            'user_reaction' => $reaction_type
        ]);
        break;
        
    case 'remove':
        $delete_stmt = $conn->prepare("DELETE FROM blogReaction WHERE blog_id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $blog_id, $user_id);
        $success = $delete_stmt->execute();
        
        // Get updated counts
        $counts_stmt = $conn->prepare("SELECT reaction_type, COUNT(*) as count FROM blogReaction WHERE blog_id = ? GROUP BY reaction_type");
        $counts_stmt->bind_param("i", $blog_id);
        $counts_stmt->execute();
        $counts_result = $counts_stmt->get_result();
        
        $counts = [];
        while ($row = $counts_result->fetch_assoc()) {
            $counts[$row['reaction_type']] = $row['count'];
        }
        
        echo json_encode([
            'success' => $success,
            'message' => 'Reaction removed',
            'counts' => $counts,
            'user_reaction' => null
        ]);
        break;
        
    case 'get':
        // Get all reactions for blog
        $get_stmt = $conn->prepare("SELECT reaction_type, COUNT(*) as count FROM blogReaction WHERE blog_id = ? GROUP BY reaction_type");
        $get_stmt->bind_param("i", $blog_id);
        $get_stmt->execute();
        $get_result = $get_stmt->get_result();
        
        $counts = [];
        while ($row = $get_result->fetch_assoc()) {
            $counts[$row['reaction_type']] = $row['count'];
        }
        
        // Get user's reaction
        $user_reaction_stmt = $conn->prepare("SELECT reaction_type FROM blogReaction WHERE blog_id = ? AND user_id = ?");
        $user_reaction_stmt->bind_param("ii", $blog_id, $user_id);
        $user_reaction_stmt->execute();
        $user_reaction_result = $user_reaction_stmt->get_result();
        $user_reaction = $user_reaction_result->num_rows > 0 ? $user_reaction_result->fetch_assoc()['reaction_type'] : null;
        
        echo json_encode([
            'success' => true,
            'counts' => $counts,
            'user_reaction' => $user_reaction
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>