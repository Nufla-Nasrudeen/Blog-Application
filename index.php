<?php
require_once 'config.php';

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$author_filter = isset($_GET['author']) ? trim($_GET['author']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build query with search and filter
$query = "SELECT bp.*, u.username, u.profile_pic,
          COALESCE(v.views, 0) as view_count,
          COALESCE(r.reaction_count, 0) as reaction_count,
          COALESCE(c.comment_count, 0) as comment_count,
          cat.name as category_name, cat.icon as category_icon, cat.slug as category_slug
          FROM blogPost bp
          JOIN user u ON bp.user_id = u.id
          LEFT JOIN blogView v ON bp.id = v.blog_id
          LEFT JOIN (SELECT blog_id, COUNT(*) as reaction_count FROM blogReaction GROUP BY blog_id) r ON bp.id = r.blog_id
          LEFT JOIN (SELECT blog_id, COUNT(*) as comment_count FROM comment GROUP BY blog_id) c ON bp.id = c.blog_id
          LEFT JOIN category cat ON bp.category_id = cat.id";

$conditions = ["bp.status = 'published'"];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($author_filter)) {
    $conditions[] = "u.username = ?";
    $params[] = $author_filter;
    $types .= "s";
}

if (!empty($category_slug)) {
    $conditions[] = "cat.slug = ?";
    $params[] = $category_slug;
    $types .= "s";
}

$query .= " WHERE " . implode(" AND ", $conditions);
$query .= " ORDER BY bp.created_at DESC";

$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all authors for filter
$authors_query = "SELECT DISTINCT u.username 
                  FROM user u 
                  JOIN blogPost bp ON u.id = bp.user_id 
                  WHERE bp.status = 'published'
                  ORDER BY u.username";
$authors_result = $conn->query($authors_query);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM blogPost WHERE status = 'published') as total_posts,
    (SELECT COUNT(*) FROM user) as total_users,
    (SELECT COUNT(*) FROM blogPost WHERE DATE(created_at) = CURDATE() AND status = 'published') as today_posts";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogHub - Share Your Stories</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
    <link rel="stylesheet" href="css/advanced.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Welcome to BlogHub 🚀</h1>
                <p class="hero-subtitle">Discover amazing stories, share your thoughts, and connect with writers</p>
                
                <!-- Statistics -->
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">📝</div>
                        <div class="stat-number"><?php echo $stats['total_posts']; ?></div>
                        <div class="stat-label">Total Posts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Active Writers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-number"><?php echo $stats['today_posts']; ?></div>
                        <div class="stat-label">Today's Posts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container main-content">
        <!-- Search and Filter Section -->
        <div class="search-filter-section">
            <form method="GET" action="" class="search-form">
                <div class="search-box">
                    <input type="text" name="search" placeholder="🔍 Search blogs..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>
                
                <div class="filter-box">
                    <select name="author" onchange="this.form.submit()">
                        <option value="">All Authors</option>
                        <?php while ($author = $authors_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($author['username']); ?>" 
                                <?php echo ($author_filter == $author['username']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($author['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <?php if (!empty($search) || !empty($author_filter) || !empty($category_slug)): ?>
                    <a href="index.php" class="clear-filters">✕ Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <h2 class="section-title">
            <?php 
            if (!empty($search)) {
                echo "Search Results for: " . htmlspecialchars($search);
            } elseif (!empty($author_filter)) {
                echo "Posts by " . htmlspecialchars($author_filter);
            } elseif (!empty($category_slug)) {
                echo "Posts in Category";
            } else {
                echo "Latest Blog Posts";
            }
            ?>
        </h2>
        
        <div class="blog-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($post = $result->fetch_assoc()): ?>
                    <div class="blog-card advanced" data-aos="fade-up">
                        <?php if (!empty($post['image_path'])): ?>
                            <div class="blog-card-image">
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="blog-card-content">
                            <div class="blog-card-header">
                                <?php if (!empty($post['category_name'])): ?>
                                    <span class="blog-badge">
                                        <?php echo $post['category_icon'] . ' ' . htmlspecialchars($post['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="blog-badge">📖 Article</span>
                                <?php endif; ?>
                                <span class="reading-time">⏱️ <?php echo ceil(str_word_count($post['content']) / 200); ?> min read</span>
                            </div>
                            
                            <h2 class="blog-title">
                                <a href="view_blog.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            
                            <div class="blog-meta">
                                <span class="author">
                                    <a href="?author=<?php echo urlencode($post['username']); ?>">
                                        <?php echo htmlspecialchars($post['username']); ?>
                                    </a>
                                </span>
                                <span class="date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                            
                            <div class="blog-excerpt">
                                <?php 
                                $content = strip_tags($post['content']);
                                echo htmlspecialchars(substr($content, 0, 150)) . (strlen($content) > 150 ? '...' : ''); 
                                ?>
                            </div>
                            
                            <div class="blog-stats">
                                <span class="stat-item">👁️ <?php echo $post['view_count']; ?></span>
                                <span class="stat-item">❤️ <?php echo $post['reaction_count']; ?></span>
                                <span class="stat-item">💬 <?php echo $post['comment_count']; ?></span>
                            </div>
                            
                            <div class="blog-card-footer">
                                <a href="view_blog.php?id=<?php echo $post['id']; ?>" class="read-more-btn">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-posts">
                    <div class="no-posts-icon">🔍</div>
                    <p>No blog posts found!</p>
                    <?php if (!empty($search) || !empty($author_filter)): ?>
                        <p class="no-posts-subtitle">Try adjusting your search or filters</p>
                        <a href="index.php" class="btn btn-primary">View All Posts</a>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <p class="no-posts-subtitle">Be the first to create one!</p>
                        <a href="create_blog.php" class="btn btn-primary">Create Your First Post</a>
                    <?php else: ?>
                        <p class="no-posts-subtitle">Login to start creating content!</p>
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="js/animations.js"></script>
</body>
</html>