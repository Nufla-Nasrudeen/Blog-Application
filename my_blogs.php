<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];




// --- DASHBOARD STATISTICS ---
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM blogPost WHERE user_id = ? AND status='published') AS total_posts,
    (SELECT COUNT(*) FROM blogView WHERE blog_id IN (SELECT id FROM blogPost WHERE user_id = ?)) AS total_views,
    (SELECT COUNT(*) FROM blogReaction WHERE blog_id IN (SELECT id FROM blogPost WHERE user_id = ?)) AS total_reactions,
    (SELECT COALESCE(SUM(LENGTH(content) - LENGTH(REPLACE(content, ' ', '')) + 1), 0) FROM blogPost WHERE user_id = ?) AS total_words
";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$stmt->bind_result($total_posts, $total_views, $total_reactions, $total_words);
$stmt->fetch();
$stmt->close();

$stats = [
    'total_posts' => $total_posts ?? 0,
    'total_views' => $total_views ?? 0,
    'total_reactions' => $total_reactions ?? 0,
    'total_words' => $total_words ?? 0
];


// Fetch user's blog posts
$stmt = $conn->prepare("SELECT * FROM blogPost WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Blogs - BlogHub</title>
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
      <div class="stat-number" style="color:#222;opacity:1;"><?php echo $stats['total_posts']; ?></div>
      <div class="stat-label">Total Posts</div>
  </div>
  <div class="user-stat-card">
      <div class="stat-icon">👁️</div>
      <div class="stat-number" style="color:#222;opacity:1;"><?php echo $stats['total_views']; ?></div>
      <div class="stat-label">Total Views</div>
  </div>
  <div class="user-stat-card">
      <div class="stat-icon">❤️</div>
      <div class="stat-number" style="color:#222;opacity:1;"><?php echo $stats['total_reactions']; ?></div>
      <div class="stat-label">Reactions</div>
  </div>
  <div class="user-stat-card">
      <div class="stat-icon">📊</div>
      <div class="stat-number" style="color:#222;opacity:1;"><?php echo $stats['total_words']; ?></div>
      <div class="stat-label">Words Written</div>
  </div>
  <div class="user-stat-card">
      <div class="stat-icon">⭐</div>
      <div class="stat-number" style="color:#222;opacity:1;">
        <?php echo ($stats['total_posts'] > 0) ? round($stats['total_words'] / $stats['total_posts']) : 0; ?>
      </div>
      <div class="stat-label">Avg Words/Post</div>
  </div>
</div>


        <div class="page-actions">
            <h2 class="section-title">My Blog Posts</h2>
            <a href="create_blog.php" class="btn btn-primary">➕ Create New Post</a>
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
                                if ($hours_ago < 24) echo '<span class="badge badge-new">🆕 New</span>';
                                if ($post['updated_at'] != $post['created_at']) echo '<span class="badge badge-updated">✏️ Updated</span>';
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
                            <span class="word-count">📊 <?php echo str_word_count($post['content']); ?> words</span>
                            <span class="char-count"><?php echo strlen($post['content']); ?> characters</span>
                        </div>
                        
                        <div class="my-blog-actions">
                            <a href="view_blog.php?id=<?php echo $post['id']; ?>" class="action-btn view-btn">👁️ View</a>
                            <a href="edit_blog.php?id=<?php echo $post['id']; ?>" class="action-btn edit-btn">✏️ Edit</a>
                            <button onclick="confirmDelete(<?php echo $post['id']; ?>)" class="action-btn delete-btn">🗑️ Delete</button>
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
