<?php
require_once 'config.php';

// Get category from URL
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all categories with blog counts
$categories_query = "SELECT c.*, COUNT(bc.blog_id) as blog_count 
                     FROM category c 
                     LEFT JOIN blog_category bc ON c.id = bc.category_id 
                     LEFT JOIN blogPost bp ON bc.blog_id = bp.id AND bp.status = 'published'
                     GROUP BY c.id 
                     ORDER BY c.name";
$categories_result = $conn->query($categories_query);

// Build blog query
if (!empty($category_slug)) {
    // Get category details
    $cat_stmt = $conn->prepare("SELECT * FROM category WHERE slug = ?");
    $cat_stmt->bind_param("s", $category_slug);
    $cat_stmt->execute();
    $category = $cat_stmt->get_result()->fetch_assoc();
    
    if ($category) {
        // Get blogs in this category
        $blogs_query = "SELECT bp.*, u.username, u.profile_pic,
                       COALESCE(v.views, 0) as view_count,
                       COALESCE(r.reaction_count, 0) as reaction_count,
                       COALESCE(c.comment_count, 0) as comment_count
                       FROM blogPost bp
                       JOIN user u ON bp.user_id = u.id
                       JOIN blog_category bc ON bp.id = bc.blog_id
                       LEFT JOIN blogView v ON bp.id = v.blog_id
                       LEFT JOIN (SELECT blog_id, COUNT(*) as reaction_count FROM blogReaction GROUP BY blog_id) r ON bp.id = r.blog_id
                       LEFT JOIN (SELECT blog_id, COUNT(*) as comment_count FROM comment GROUP BY blog_id) c ON bp.id = c.blog_id
                       WHERE bc.category_id = ? AND bp.status = 'published'
                       ORDER BY bp.created_at DESC";
        $stmt = $conn->prepare($blogs_query);
        $stmt->bind_param("i", $category['id']);
        $stmt->execute();
        $blogs_result = $stmt->get_result();
    }
} else {
    // Show all blogs
    $blogs_query = "SELECT bp.*, u.username, u.profile_pic,
                   COALESCE(v.views, 0) as view_count,
                   COALESCE(r.reaction_count, 0) as reaction_count,
                   COALESCE(c.comment_count, 0) as comment_count,
                   cat.name as category_name, cat.icon as category_icon
                   FROM blogPost bp
                   JOIN user u ON bp.user_id = u.id
                   LEFT JOIN category cat ON bp.category_id = cat.id
                   LEFT JOIN blogView v ON bp.id = v.blog_id
                   LEFT JOIN (SELECT blog_id, COUNT(*) as reaction_count FROM blogReaction GROUP BY blog_id) r ON bp.id = r.blog_id
                   LEFT JOIN (SELECT blog_id, COUNT(*) as comment_count FROM comment GROUP BY blog_id) c ON bp.id = c.blog_id
                   WHERE bp.status = 'published'
                   ORDER BY bp.created_at DESC";
    $blogs_result = $conn->query($blogs_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($category) ? $category['name'] . ' - ' : ''; ?>Categories - BlogHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
    <link rel="stylesheet" href="css/advanced.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="categories-hero">
        <div class="container">
            <?php if (!empty($category)): ?>
                <div class="category-header">
                    <span class="category-icon-large"><?php echo $category['icon']; ?></span>
                    <h1 class="hero-title"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <p class="hero-subtitle"><?php echo htmlspecialchars($category['description']); ?></p>
                    <a href="categories.php" class="btn btn-secondary">← View All Categories</a>
                </div>
            <?php else: ?>
                <h1 class="hero-title">Explore by Category 🗂️</h1>
                <p class="hero-subtitle">Browse blogs organized by topics</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container main-content">
        <?php if (empty($category_slug)): ?>
            <!-- Categories Grid -->
            <div class="categories-grid">
                <?php while ($cat = $categories_result->fetch_assoc()): ?>
                    <a href="?category=<?php echo urlencode($cat['slug']); ?>" class="category-card">
                        <div class="category-icon" 
                             style="font-size: 40px; 
                                    transition: transform 0.25s ease, color 0.25s ease; 
                                    color: #5c3df2;">
                            <?php 
                            $icon = trim($cat['icon']);
                            if (empty($icon) || $icon === '?' || $icon === '??') {
                                switch (strtolower($cat['name'])) {
                                    case 'art': $icon = '🎨'; break;
                                    case 'business': $icon = '💼'; break;
                                    case 'education': $icon = '📘'; break;
                                    case 'entertainment': $icon = '🎬'; break;
                                    case 'food': $icon = '🍔'; break;
                                    case 'lifestyle': $icon = '🌿'; break;
                                    case 'science': $icon = '🔬'; break;
                                    case 'sports': $icon = '⚽'; break;
                                    case 'technology': $icon = '💻'; break;
                                    case 'travel': $icon = '✈️'; break;
                                    default: $icon = '📁'; 
                                }
                            }
                            echo $icon;
                            ?>
                        </div>


                        <h3 class="category-name"><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p class="category-description"><?php echo htmlspecialchars($cat['description']); ?></p>
                        <div class="category-count"><?php echo $cat['blog_count']; ?> posts</div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <!-- Blogs in Category -->
            <div class="blog-grid">
                <?php if ($blogs_result && $blogs_result->num_rows > 0): ?>
                    <?php while ($post = $blogs_result->fetch_assoc()): ?>
                        <div class="blog-card advanced">
                            <?php if (!empty($post['image_path'])): ?>
                                <div class="blog-card-image">
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="blog-card-content">
                                <div class="blog-card-header">
                                    <span class="blog-badge"><?php echo $category['icon']; ?> <?php echo htmlspecialchars($category['name']); ?></span>
                                    <span class="reading-time">⏱️ <?php echo ceil(str_word_count($post['content']) / 200); ?> min</span>
                                </div>
                                
                                <h2 class="blog-title">
                                    <a href="view_blog.php?id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h2>
                                
                                <div class="blog-meta">
                                    <span class="author">
                                        <?php if (!empty($post['profile_pic'])): ?>
                                            <img src="<?php echo htmlspecialchars($post['profile_pic']); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="author-avatar-sm">
                                        <?php endif; ?>
                                        <a href="profile.php?user=<?php echo urlencode($post['username']); ?>">
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
                        <div class="no-posts-icon">📝</div>
                        <p>No posts in this category yet!</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="create_blog.php" class="btn btn-primary">Be the First to Post</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="js/animations.js"></script>
</body>
</html>