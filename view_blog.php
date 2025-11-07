<?php
require_once 'config.php';

$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($blog_id <= 0) {
    header('Location: index.php');
    exit();
}

// Increment view count
$view_stmt = $conn->prepare("INSERT INTO blogView (blog_id, views) VALUES (?, 1) ON DUPLICATE KEY UPDATE views = views + 1");
$view_stmt->bind_param("i", $blog_id);
$view_stmt->execute();

// Fetch blog post with author and stats
$stmt = $conn->prepare("SELECT bp.*, u.username, u.profile_pic,
                       COALESCE(v.views, 0) as view_count,
                       cat.name as category_name, cat.icon as category_icon
                       FROM blogPost bp 
                       JOIN user u ON bp.user_id = u.id 
                       LEFT JOIN blogView v ON bp.id = v.blog_id
                       LEFT JOIN category cat ON bp.category_id = cat.id
                       WHERE bp.id = ?");
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$post = $result->fetch_assoc();
$stmt->close();

$is_author = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id'];

// Get comments
$comments_query = "SELECT c.*, u.username, u.profile_pic 
                   FROM comment c 
                   JOIN user u ON c.user_id = u.id 
                   WHERE c.blog_id = ? 
                   ORDER BY c.created_at DESC";
$comments_stmt = $conn->prepare($comments_query);
$comments_stmt->bind_param("i", $blog_id);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$comment_count = $comments_result->num_rows;

// Get reaction counts
$reactions_query = "SELECT reaction_type, COUNT(*) as count 
                    FROM blogReaction 
                    WHERE blog_id = ? 
                    GROUP BY reaction_type";
$reactions_stmt = $conn->prepare($reactions_query);
$reactions_stmt->bind_param("i", $blog_id);
$reactions_stmt->execute();
$reactions_result = $reactions_stmt->get_result();

$reactions = ['like' => 0, 'love' => 0, 'wow' => 0];
while ($row = $reactions_result->fetch_assoc()) {
    $reactions[$row['reaction_type']] = $row['count'];
}

// Get user's reaction if logged in
$user_reaction = null;
if (isset($_SESSION['user_id'])) {
    $user_reaction_stmt = $conn->prepare("SELECT reaction_type FROM blogReaction WHERE blog_id = ? AND user_id = ?");
    $user_reaction_stmt->bind_param("ii", $blog_id, $_SESSION['user_id']);
    $user_reaction_stmt->execute();
    $user_reaction_result = $user_reaction_stmt->get_result();
    if ($user_reaction_result->num_rows > 0) {
        $user_reaction = $user_reaction_result->fetch_assoc()['reaction_type'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - BlogHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
    <link rel="stylesheet" href="css/advanced.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container main-content">
        <article class="blog-post">
            <?php if (!empty($post['image_path'])): ?>
                <div class="blog-post-image">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                </div>
            <?php endif; ?>
            
            <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="blog-post-meta">
                <span class="author">
                    <?php if (!empty($post['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="" class="author-avatar-sm">
                    <?php endif; ?>
                    By <?php echo htmlspecialchars($post['username']); ?>
                </span>
                <span class="date">📅 <?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                <?php if ($post['updated_at'] != $post['created_at']): ?>
                    <span class="date">✏️ Updated <?php echo date('F d, Y', strtotime($post['updated_at'])); ?></span>
                <?php endif; ?>
                <span class="views">👁️ <?php echo number_format($post['view_count']); ?> views</span>
                <?php if (!empty($post['category_name'])): ?>
                    <span class="category">
                        <a href="categories.php?category=<?php echo urlencode(strtolower(str_replace(' ', '-', $post['category_name']))); ?>">
                            <?php echo $post['category_icon'] . ' ' . htmlspecialchars($post['category_name']); ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($is_author): ?>
                <div class="blog-actions">
                    <a href="edit_blog.php?id=<?php echo $post['id']; ?>" class="btn btn-primary">✏️ Edit</a>
                    <button onclick="deleteBlog(<?php echo $post['id']; ?>)" class="btn btn-danger">🗑️ Delete</button>
                </div>
            <?php endif; ?>
            
            <!-- Reactions -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="reactions-container" data-blog-id="<?php echo $blog_id; ?>">
                    <button class="reaction-btn <?php echo ($user_reaction == 'like') ? 'active' : ''; ?>" data-type="like">
                        👍 Like <span class="count"><?php echo $reactions['like']; ?></span>
                    </button>
                    <button class="reaction-btn <?php echo ($user_reaction == 'love') ? 'active' : ''; ?>" data-type="love">
                        ❤️ Love <span class="count"><?php echo $reactions['love']; ?></span>
                    </button>
                    <button class="reaction-btn <?php echo ($user_reaction == 'wow') ? 'active' : ''; ?>" data-type="wow">
                        😮 Wow <span class="count"><?php echo $reactions['wow']; ?></span>
                    </button>
                </div>
            <?php else: ?>
                <div class="reactions-container">
                    <div class="reaction-stats">
                        <span>👍 <?php echo $reactions['like']; ?></span>
                        <span>❤️ <?php echo $reactions['love']; ?></span>
                        <span>😮 <?php echo $reactions['wow']; ?></span>
                    </div>
                    <p class="login-prompt"><a href="login.php">Login</a> to react</p>
                </div>
            <?php endif; ?>
            
            <div class="blog-post-content" id="blog-content">
                <!-- Content will be rendered here -->
            </div>
        </article>
        
        <!-- Comments Section -->
        <div class="comments-section" id="comments">
            <div class="comments-header">
                <h3 class="comments-title">💬 Comments (<?php echo $comment_count; ?>)</h3>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="add_comment.php" class="comment-form">
                    <input type="hidden" name="blog_id" value="<?php echo $blog_id; ?>">
                    <textarea name="content" placeholder="Share your thoughts..." required rows="4"></textarea>
                    <button type="submit" class="btn btn-primary">💬 Post Comment</button>
                </form>
            <?php else: ?>
                <div class="login-prompt-box">
                    <p>Please <a href="login.php">login</a> to leave a comment</p>
                </div>
            <?php endif; ?>
            
            <?php if ($comment_count > 0): ?>
                <div class="comments-list">
                    <?php while ($comment = $comments_result->fetch_assoc()): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-avatar">
                                    <?php echo strtoupper(substr($comment['username'], 0, 2)); ?>
                                </div>
                                <div class="comment-author">
                                    <span class="comment-author-name">
                                        <?php echo htmlspecialchars($comment['username']); ?>
                                    </span>
                                    <span class="comment-date">
                                        <?php echo date('M d, Y - H:i', strtotime($comment['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                <div class="comment-actions">
                                    <a href="delete_comment.php?id=<?php echo $comment['id']; ?>" 
                                       class="comment-action-btn" 
                                       onclick="return confirm('Are you sure you want to delete this comment?')">
                                        🗑️ Delete
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="back-link">
            <a href="index.php" class="btn btn-secondary">← Back to Home</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Render markdown content
        const content = <?php echo json_encode($post['content']); ?>;
        document.getElementById('blog-content').innerHTML = marked.parse(content);
        
        function deleteBlog(id) {
            if (confirm('⚠️ Are you sure you want to delete this blog post?\n\nThis action cannot be undone!')) {
                window.location.href = 'delete_blog.php?id=' + id;
            }
        }
    </script>
    <script src="js/reactions.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>