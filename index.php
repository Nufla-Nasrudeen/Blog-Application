<?php
session_start();
require_once 'config.php';

// Debug mode — disable after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- SEARCH AND FILTER HANDLING ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$author_filter = isset($_GET['author']) ? trim($_GET['author']) : '';
$order = isset($_GET['order']) ? $_GET['order'] : 'newest';

// Build query
$query = "
SELECT bp.*, u.username, u.profile_pic,
       COALESCE(v.views, 0) AS view_count,
       COALESCE(r.reaction_count, 0) AS reaction_count,
       COALESCE(c.comment_count, 0) AS comment_count,
       cat.name AS category_name, cat.icon AS category_icon, cat.slug AS category_slug
FROM blogPost bp
JOIN user u ON bp.user_id = u.id
LEFT JOIN blogView v ON bp.id = v.blog_id
LEFT JOIN (SELECT blog_id, COUNT(*) AS reaction_count FROM blogReaction GROUP BY blog_id) r ON bp.id = r.blog_id
LEFT JOIN (SELECT blog_id, COUNT(*) AS comment_count FROM comment GROUP BY blog_id) c ON bp.id = c.blog_id
LEFT JOIN category cat ON bp.category_id = cat.id
WHERE bp.status = 'published'
";

$conditions = [];
$params = [];
$types = "";

// Search filter
if (!empty($search)) {
    $conditions[] = "(bp.title LIKE ? OR bp.content LIKE ? OR u.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Category filter
if ($category_filter > 0) {
    $conditions[] = "bp.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

// Author filter
if (!empty($author_filter)) {
    $conditions[] = "u.username = ?";
    $params[] = $author_filter;
    $types .= "s";
}

// Append conditions
if (count($conditions) > 0) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Order by
switch ($order) {
    case 'oldest':
        $query .= " ORDER BY bp.created_at ASC";
        break;
    case 'popular':
        $query .= " ORDER BY view_count DESC, reaction_count DESC";
        break;
    default: // newest
        $query .= " ORDER BY bp.created_at DESC";
        break;
}

// Execute query
$stmt = $conn->prepare($query);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get authors for dropdown
$authors_result = $conn->query("
    SELECT DISTINCT u.username 
    FROM user u 
    JOIN blogPost bp ON u.id = bp.user_id 
    WHERE bp.status = 'published'
    ORDER BY u.username
");

// Statistics
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM blogPost WHERE status='published') AS total_posts,
    (SELECT COUNT(DISTINCT user.id)
     FROM user
     JOIN blogPost bp ON bp.user_id = user.id
     WHERE bp.status = 'published') AS total_users,
    (SELECT COUNT(*) FROM blogPost WHERE DATE(created_at)=CURDATE() AND status='published') AS today_posts
";
$stats = $conn->query($stats_query)->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BlogHub – Share Your Stories</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/enhanced.css">
<link rel="stylesheet" href="css/advanced.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- HERO SECTION -->
<section class="hero-section">
  <div class="container hero-content">
    <h1 class="hero-title">Welcome to BlogHub 🚀</h1>
    <p class="hero-subtitle">Discover stories, share your voice, and connect with creators.</p>

    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-number"><?= $stats['total_posts'] ?></div>
        <div class="stat-label">Published Posts</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?= $stats['total_users'] ?></div>
        <div class="stat-label">Active Writers</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🔥</div>
        <div class="stat-number"><?= $stats['today_posts'] ?></div>
        <div class="stat-label">Today's Posts</div>
      </div>
    </div>
  </div>
</section>

<!-- SEARCH & FILTER -->
<div class="container search-filter-section" style="margin-top: 30px;">
  <form method="GET" action="index.php" class="search-bar"
        style="
          display: flex;
          flex-wrap: wrap;
          gap: 12px;
          align-items: center;
          justify-content: center;
          background: #ffffff;
          border-radius: 14px;
          box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
          padding: 18px 24px;
          transition: all 0.3s ease-in-out;
        ">
      
    <!-- Search Input -->
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
           placeholder="🔍 Search by title, author, or content..."
           style="
             flex: 1;
             min-width: 280px;
             padding: 12px 16px;
             font-size: 15px;
             border: 1px solid #e0e0e0;
             border-radius: 10px;
             outline: none;
             transition: 0.2s ease-in-out;
           "
           onfocus="this.style.borderColor='#6C63FF';"
           onblur="this.style.borderColor='#e0e0e0';">

    <!-- Category Dropdown -->
    <select name="category" 
            style="
              padding: 12px 16px;
              border-radius: 10px;
              border: 1px solid #e0e0e0;
              background: #f9f9ff;
              cursor: pointer;
              font-size: 15px;
              transition: all 0.2s ease-in-out;
            "
            onfocus="this.style.borderColor='#6C63FF';"
            onblur="this.style.borderColor='#e0e0e0';">
        <option value="0">All Categories</option>
        <?php
        $cat_result = $conn->query("SELECT id, name, icon FROM category ORDER BY name");
        while ($cat = $cat_result->fetch_assoc()):
        ?>
            <option value="<?php echo $cat['id']; ?>" 
                <?php echo ($category_filter == $cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['icon'] . ' ' . $cat['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <!-- Order Dropdown -->
    <select name="order" 
            style="
              padding: 12px 16px;
              border-radius: 10px;
              border: 1px solid #e0e0e0;
              background: #f9f9ff;
              cursor: pointer;
              font-size: 15px;
              transition: all 0.2s ease-in-out;
            "
            onfocus="this.style.borderColor='#6C63FF';"
            onblur="this.style.borderColor='#e0e0e0';">
        <option value="newest" <?php echo ($order == 'newest') ? 'selected' : ''; ?>>⏰ Newest First</option>
        <option value="oldest" <?php echo ($order == 'oldest') ? 'selected' : ''; ?>>📅 Oldest First</option>
        <option value="popular" <?php echo ($order == 'popular') ? 'selected' : ''; ?>>🔥 Most Popular</option>
    </select>

    <!-- Search Button -->
    <button type="submit"
            style="
              padding: 12px 26px;
              border: none;
              background: linear-gradient(135deg, #6C63FF, #7C74FF);
              color: white;
              border-radius: 10px;
              font-weight: 600;
              letter-spacing: 0.3px;
              cursor: pointer;
              transition: 0.25s ease-in-out;
              box-shadow: 0 3px 10px rgba(108, 99, 255, 0.3);
            "
            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(108, 99, 255, 0.4)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 10px rgba(108, 99, 255, 0.3)';">
        🔍 Search
    </button>

    <!-- Clear Filters Button -->
    <?php if (!empty($search) || $category_filter > 0 || !empty($author_filter)): ?>
    <a href="index.php" 
       style="
         padding: 12px 20px;
         border: none;
         background: #f44336;
         color: white;
         border-radius: 10px;
         font-weight: 600;
         text-decoration: none;
         cursor: pointer;
         transition: 0.25s ease-in-out;
       "
       onmouseover="this.style.background='#d32f2f';"
       onmouseout="this.style.background='#f44336';">
      ✕ Clear Filters
    </a>
    <?php endif; ?>
  </form>
</div>

<!-- BLOG GRID -->
<div class="container main-content">
  <h2 class="section-title">
    <?php
      if (!empty($search)) {
          echo "🔍 Search Results for: \"" . htmlspecialchars($search) . "\"";
      } elseif ($category_filter > 0) {
          $cat_name_query = $conn->query("SELECT name FROM category WHERE id = $category_filter");
          if ($cat_name_query && $cat_name_query->num_rows > 0) {
              $cat_name = $cat_name_query->fetch_assoc()['name'];
              echo "📂 Posts in: " . htmlspecialchars($cat_name);
          }
      } elseif (!empty($author_filter)) {
          echo "✍️ Posts by: " . htmlspecialchars($author_filter);
      } else {
          echo "📰 Latest Blog Posts";
      }
    ?>
  </h2>

  <div class="blog-grid">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($post = $result->fetch_assoc()): ?>
        <div class="blog-card advanced" data-aos="fade-up">
          <?php if (!empty($post['image_path'])): ?>
          <div class="blog-card-image" 
               style="
                 width: 100%;
                 height: 220px; 
                 overflow: hidden; 
                 border-radius: 16px 16px 0 0; 
                 background: #f4f4f9; 
                 display: flex; 
                 align-items: center; 
                 justify-content: center;">
            <img src="<?= htmlspecialchars($post['image_path']) ?>" 
                 alt="<?= htmlspecialchars($post['title']) ?>"
                 style="
                   width: 100%; 
                   height: 100%; 
                   object-fit: cover; 
                   transition: transform 0.3s ease-in-out;"
                 onmouseover="this.style.transform='scale(1.1)';"
                 onmouseout="this.style.transform='scale(1)';">
          </div>
          <?php endif; ?>

          <div class="blog-card-content">
            <div class="blog-card-header">
              <?php if (!empty($post['category_name'])): ?>
                <span class="blog-badge"><?= $post['category_icon'] . ' ' . htmlspecialchars($post['category_name']) ?></span>
              <?php else: ?>
                <span class="blog-badge">📖 Article</span>
              <?php endif; ?>
              <span class="reading-time">⏱️ <?= ceil(str_word_count($post['content']) / 200) ?> min read</span>
            </div>

            <h2 class="blog-title">
              <a href="view_blog.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
            </h2>

            <div class="blog-meta">
              <span class="author">
                <a href="?author=<?= urlencode($post['username']) ?>">
                  <?= htmlspecialchars($post['username']) ?>
                </a>
              </span>
              <span class="date"><?= date('M d, Y', strtotime($post['created_at'])) ?></span>
            </div>

            <div class="blog-excerpt">
              <?php
                $excerpt = strip_tags($post['content']);
                echo htmlspecialchars(substr($excerpt, 0, 150)) . (strlen($excerpt) > 150 ? '...' : '');
              ?>
            </div>

            <div class="blog-stats">
              <span class="stat-item">👁️ <?= number_format($post['view_count']) ?></span>
              <span class="stat-item">❤️ <?= number_format($post['reaction_count']) ?></span>
              <span class="stat-item">💬 <?= number_format($post['comment_count']) ?></span>
            </div>

            <div class="blog-card-footer">
              <a href="view_blog.php?id=<?= $post['id'] ?>" class="read-more-btn">Read More →</a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-posts">
        <div class="no-posts-icon">🔍</div>
        <p>No blog posts found!</p>
        <?php if (!empty($search) || $category_filter > 0 || !empty($author_filter)): ?>
          <p class="no-posts-subtitle">Try adjusting your search or filters</p>
          <a href="index.php" class="btn btn-primary">View All Posts</a>
        <?php else: ?>
          <p class="no-posts-subtitle">No posts available yet.</p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="create_blog.php" class="btn btn-primary">Create Your First Post</a>
          <?php else: ?>
            <a href="login.php" class="btn btn-primary">Login to Create</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/animations.js"></script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>