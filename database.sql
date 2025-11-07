CREATE DATABASE IF NOT EXISTS if0_40294128_blog_db1;
USE if0_40294128_blog_db1;

-- User table
CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Blog post table
CREATE TABLE IF NOT EXISTS blogPost (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Blog reactions table
CREATE TABLE IF NOT EXISTS blogReaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (blog_id, user_id, reaction_type)
);

-- Blog views counter
CREATE TABLE IF NOT EXISTS blogView (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    view_count INT DEFAULT 0,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    UNIQUE KEY unique_blog (blog_id)
);


-- ADVANCED FEATURES DATABASE SCHEMA
-- Run this after your basic database.sql

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Blog-Category Junction Table (Many-to-Many)
CREATE TABLE IF NOT EXISTS blog_category (
    blog_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (blog_id, category_id),
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
);

-- 3. Comments Table
CREATE TABLE IF NOT EXISTS comment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- 4. Blog Reactions Table (Likes, Love, etc.)
CREATE TABLE IF NOT EXISTS blogReaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'love', 'wow', 'sad', 'angry') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_reaction (blog_id, user_id)
);

-- 5. Blog Views Counter
CREATE TABLE IF NOT EXISTS blogView (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    views INT DEFAULT 0,
    last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blog_id) REFERENCES blogPost(id) ON DELETE CASCADE,
    UNIQUE KEY unique_blog (blog_id)
);

-- 6. Enhance User Table with Profile Fields
ALTER TABLE user 
ADD COLUMN bio TEXT AFTER email,
ADD COLUMN profile_pic VARCHAR(255) AFTER bio,
ADD COLUMN website VARCHAR(255) AFTER profile_pic,
ADD COLUMN twitter VARCHAR(100) AFTER website,
ADD COLUMN github VARCHAR(100) AFTER twitter,
ADD COLUMN location VARCHAR(100) AFTER github;

-- 7. Enhance BlogPost Table with Image Support
ALTER TABLE blogPost 
ADD COLUMN image_path VARCHAR(255) AFTER content,
ADD COLUMN category_id INT AFTER image_path,
ADD COLUMN views INT DEFAULT 0 AFTER category_id,
ADD COLUMN status ENUM('draft', 'published', 'archived') DEFAULT 'published' AFTER views;

-- 8. Sample Categories
INSERT INTO category (name, slug, description, icon) VALUES
('Technology', 'technology', 'Tech news, tutorials, and innovations', '💻'),
('Lifestyle', 'lifestyle', 'Life tips, health, and wellness', '🌟'),
('Travel', 'travel', 'Travel guides and experiences', '✈️'),
('Food', 'food', 'Recipes and food reviews', '🍕'),
('Education', 'education', 'Learning resources and tutorials', '📚'),
('Business', 'business', 'Business insights and entrepreneurship', '💼'),
('Entertainment', 'entertainment', 'Movies, music, and pop culture', '🎬'),
('Sports', 'sports', 'Sports news and analysis', '⚽'),
('Science', 'science', 'Scientific discoveries and research', '🔬'),
('Art', 'art', 'Creative arts and design', '🎨');

-- 9. Notifications Table (Future Feature)
CREATE TABLE IF NOT EXISTS notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('comment', 'like', 'follow', 'mention') NOT NULL,
    content TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- 10. Followers Table (Future Feature)
CREATE TABLE IF NOT EXISTS follower (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
);

-- 11. Create Indexes for Better Performance
CREATE INDEX idx_blog_user ON blogPost(user_id);
CREATE INDEX idx_blog_created ON blogPost(created_at DESC);
CREATE INDEX idx_comment_blog ON comment(blog_id);
CREATE INDEX idx_comment_user ON comment(user_id);
CREATE INDEX idx_reaction_blog ON blogReaction(blog_id);
CREATE INDEX idx_reaction_user ON blogReaction(user_id);



-- Sample data (optional)
-- INSERT INTO user (username, email, password, role) 
-- VALUES ('admin', 'admin@blog.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password is 'password' (hashed)