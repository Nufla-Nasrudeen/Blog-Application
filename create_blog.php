<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Fetch categories
$categories_query = "SELECT * FROM category ORDER BY name";
$categories_result = $conn->query($categories_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'published';
    $user_id = $_SESSION['user_id'];
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Generate unique filename
            $new_filename = uniqid() . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = $upload_path;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP';
        }
    }
    
    if (empty($error)) {
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            // Insert blog post
            $stmt = $conn->prepare("INSERT INTO blogPost (user_id, title, content, image_path, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssis", $user_id, $title, $content, $image_path, $category_id, $status);
            
            if ($stmt->execute()) {
                $blog_id = $stmt->insert_id;
                
                // Insert into blog_category junction table if category selected
                if ($category_id) {
                    $cat_stmt = $conn->prepare("INSERT INTO blog_category (blog_id, category_id) VALUES (?, ?)");
                    $cat_stmt->bind_param("ii", $blog_id, $category_id);
                    $cat_stmt->execute();
                }
                
                $success = 'Blog post created successfully!';
                header('Location: view_blog.php?id=' . $blog_id);
                exit();
            } else {
                $error = 'Failed to create blog post';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Blog - BlogHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
    <link rel="stylesheet" href="css/advanced.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container main-content">
        <div class="create-blog-header">
            <h1 class="page-title">✍️ Create New Blog Post</h1>
            <p class="page-subtitle">Share your thoughts with the world</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="blog-form advanced">
            <!-- Title -->
            <div class="form-group">
                <label for="title">📝 Blog Title</label>
                <input type="text" id="title" name="title" required placeholder="Enter an engaging title...">
                <small class="form-hint">Make it catchy and descriptive</small>
            </div>
            
            <!-- Category -->
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">🗂️ Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select a category (optional)</option>
                        <?php 
                        $categories_result->data_seek(0); // Reset pointer
                        while ($cat = $categories_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">📊 Status</label>
                    <select id="status" name="status">
                        <option value="published">Published (Visible to all)</option>
                        <option value="draft">Draft (Save for later)</option>
                    </select>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="form-group">
                <label for="image">🖼️ Featured Image (Optional)</label>
                <div class="image-upload-container">
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    <div id="image-preview" class="image-preview"></div>
                </div>
                <small class="form-hint">Recommended: 1200x630px, Max 5MB (JPG, PNG, GIF, WEBP)</small>
            </div>
            
            <!-- Content -->
            <div class="form-group">
                <label for="content">📄 Content (Markdown Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertMarkdown('**', '**')" title="Bold">
                        <strong>B</strong>
                    </button>
                    <button type="button" onclick="insertMarkdown('*', '*')" title="Italic">
                        <em>I</em>
                    </button>
                    <button type="button" onclick="insertMarkdown('# ', '')" title="Heading">
                        H1
                    </button>
                    <button type="button" onclick="insertMarkdown('[', '](url)')" title="Link">
                        🔗
                    </button>
                    <button type="button" onclick="insertMarkdown('`', '`')" title="Code">
                        &lt;/&gt;
                    </button>
                    <button type="button" onclick="insertMarkdown('\n- ', '')" title="List">
                        ≡
                    </button>
                    <button type="button" onclick="insertMarkdown('\n> ', '')" title="Quote">
                        "
                    </button>
                </div>
                <textarea id="content" name="content" rows="20" required placeholder="Write your amazing content here...

### You can use Markdown!

**Bold text**, *italic text*, [links](url), and more!

```code blocks```

> Quotes

- Lists
"></textarea>
                <div class="content-stats">
                    <span id="word-count">0 words</span>
                    <span id="char-count">0 characters</span>
                    <span id="reading-time">0 min read</span>
                </div>
            </div>
            
            <!-- Preview -->
            <div class="editor-preview">
                <h3>👁️ Preview</h3>
                <div id="preview" class="preview-content"></div>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" name="status" value="published">
                    🚀 Publish Now
                </button>
                <button type="submit" class="btn btn-secondary" name="status" value="draft">
                    💾 Save as Draft
                </button>
                <a href="index.php" class="btn btn-secondary">
                    ❌ Cancel
                </a>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="js/editor.js"></script>
    <script>
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    img.style.borderRadius = '8px';
                    preview.appendChild(img);
                    
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = '✕ Remove';
                    removeBtn.className = 'btn btn-danger btn-sm';
                    removeBtn.onclick = function() {
                        input.value = '';
                        preview.innerHTML = '';
                    };
                    preview.appendChild(removeBtn);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Markdown toolbar functions
        function insertMarkdown(before, after) {
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            const newText = before + selectedText + after;
            
            textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + before.length, end + before.length);
            
            // Trigger input event to update preview
            textarea.dispatchEvent(new Event('input'));
        }
        
        // Content statistics
        const textarea = document.getElementById('content');
        textarea.addEventListener('input', function() {
            const text = this.value;
            const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
            const chars = text.length;
            const readingTime = Math.ceil(words / 200);
            
            document.getElementById('word-count').textContent = words + ' words';
            document.getElementById('char-count').textContent = chars + ' characters';
            document.getElementById('reading-time').textContent = readingTime + ' min read';
        });
    </script>
</body>
</html>