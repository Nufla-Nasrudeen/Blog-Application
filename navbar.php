<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="index.php">✨ BlogHub</a>
        </div>
        <div class="nav-menu">
            <a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>🏠 Home</a>
            <a href="categories.php" <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : ''; ?>>🗂️ Categories</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_blog.php" <?php echo basename($_SERVER['PHP_SELF']) == 'create_blog.php' ? 'class="active"' : ''; ?>>✍️ Create Blog</a>
                <a href="my_blogs.php" <?php echo basename($_SERVER['PHP_SELF']) == 'my_blogs.php' ? 'class="active"' : ''; ?>>📚 My Blogs</a>
                <span class="nav-user">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="btn-logout">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>