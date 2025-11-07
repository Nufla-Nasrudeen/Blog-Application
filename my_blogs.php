<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's blog posts
$stmt = $conn->prepare("SELECT * FROM blogPost WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Get user statistics
$stats_query = $conn->prepare("SELECT 
    COUNT(*) as total_posts,
    SUM(LENGTH(content) - LENGTH(REPLACE(content, ' ', '')) + 1) as total_words
    FROM blogPost WHERE user_id = ?");
$stats_query->bind_param("i", $_SESSION['user_id']);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Blogs - Blog Application</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php">✨ BlogHub</a>
            </div>
            <div class="nav-menu">
                <a href="index.php">🏠 Home</a>
                <a href="create_blog.php">✍️ Create Blog</a>
                <a href="my_blogs.php" class="active">📚 My Blogs</a>
                <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
            </div>
            <div class="profile-info">
                <h1 class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?>'s Dashboard</h1>
                <p class="profile-bio">Manage all your blog posts in one place</p>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="user-stats">
            <div class="user-stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?php echo $stats['total_posts']; ?></div>
                <div class="stat-label">Total Posts</div>
            </div>
            <div class="user-stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-number"><?php echo number_format($stats['total_words']); ?></div>
                <div class="stat-label">Words Written</div>
            </div>
            <div class="user-stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number"><?php echo $stats['total_posts'] > 0 ? round($stats['total_words'] / $stats['total_posts']) : 0; ?></div>
                <div class="stat-label">Avg Words/Post</div>
            </div>
        </div>

        <div class="page-actions">
            <h2 class="section-title">My Blog Posts</h2>
            <a href="create_blog.php" class="btn btn-primary">
                ➕ Create New Post
            </a>
        </div>
        
        <div class="my-blogs-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="my-blog-card">
                        <div class="my-blog-header">
                            <h3 class="my-blog-title">
                                <a href="view_blog.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            <div class="my-blog-status">
                                <?php 
                                $hours_ago = (time() - strtotime($post['created_at'])) / 3600;
                                if ($hours_ago < 24) {
                                    echo '<span class="badge badge-new">🆕 New</span>';
                                }
                                if ($post['updated_at'] != $post['created_at']) {
                                    echo '<span class="badge badge-updated">✏️ Updated</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="my-blog-meta">
                            <span>📅 Created: <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                            <?php if ($post['updated_at'] != $post['created_at']): ?>
                                <span>✏️ Updated: <?php echo date('M d, Y', strtotime($post['updated_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="my-blog-excerpt">
                            <?php 
                            $content = strip_tags($post['content']);
                            echo htmlspecialchars(substr($content, 0, 100)) . (strlen($content) > 100 ? '...' : ''); 
                            ?>
                        </div>
                        
                        <div class="my-blog-stats">
                            <span class="word-count">
                                📊 <?php echo str_word_count($post['content']); ?> words
                            </span>
                            <span class="char-count">
                                <?php echo strlen($post['content']); ?> characters
                            </span>
                        </div>
                        
                        <div class="my-blog-actions">
                            <a href="view_blog.php?id=<?php echo $post['id']; ?>" class="action-btn view-btn" title="View">
                                👁️ View
                            </a>
                            <a href="edit_blog.php?id=<?php echo $post['id']; ?>" class="action-btn edit-btn" title="Edit">
                                ✏️ Edit
                            </a>
                            <button onclick="confirmDelete(<?php echo $post['id']; ?>)" class="action-btn delete-btn" title="Delete">
                                🗑️ Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-posts">
                    <div class="no-posts-icon">📝</div>
                    <p>You haven't created any blog posts yet!</p>
                    <p class="no-posts-subtitle">Start sharing your thoughts with the world</p>
                    <a href="create_blog.php" class="btn btn-primary">Create Your First Post</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 BlogHub. Made with ❤️ for sharing stories</p>
        </div>
    </footer>

    <script>
        function confirmDelete(id) {
            if (confirm('⚠️ Are you sure you want to delete this blog post?\n\nThis action cannot be undone!')) {
                window.location.href = 'delete_blog.php?id=' + id;
            }
        }
    </script>
</body>
</html>