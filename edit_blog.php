<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$blog_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($blog_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch blog post
$stmt = $conn->prepare("SELECT * FROM blogPost WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $blog_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$post = $result->fetch_assoc();
$stmt->close();

// Fetch categories
$categories_query = "SELECT * FROM category ORDER BY name";
$categories_result = $conn->query($categories_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'published';
    
    // Handle image upload
    $image_path = $post['image_path']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $new_filename = uniqid() . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                    unlink($post['image_path']);
                }
                $image_path = $upload_path;
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Invalid image format';
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && !empty($post['image_path'])) {
        if (file_exists($post['image_path'])) {
            unlink($post['image_path']);
        }
        $image_path = null;
    }
    
    if (empty($error)) {
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            $stmt = $conn->prepare("UPDATE blogPost SET title = ?, content = ?, image_path = ?, category_id = ?, status = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssisii", $title, $content, $image_path, $category_id, $status, $blog_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update category relationship
                $conn->query("DELETE FROM blog_category WHERE blog_id = $blog_id");
                if ($category_id) {
                    $cat_stmt = $conn->prepare("INSERT INTO blog_category (blog_id, category_id) VALUES (?, ?)");
                    $cat_stmt->bind_param("ii", $blog_id, $category_id);
                    $cat_stmt->execute();
                }
                
                $success = 'Blog post updated successfully!';
                header('Location: view_blog.php?id=' . $blog_id);
                exit();
            } else {
                $error = 'Failed to update blog post';
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
    <title>Edit Blog - BlogHub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/enhanced.css">
    <link rel="stylesheet" href="css/advanced.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.3.0/marked.min.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container main-content">
        <div class="create-blog-header">
            <h1 class="page-title">✏️ Edit Blog Post</h1>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="blog-form advanced">
            <div class="form-group">
                <label for="title">📝 Blog Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>
            
              <!-- Category -->
<div class="form-row">
  <div class="form-group">
    <label for="category_id">🗂️ Category</label>
    <select id="category_id" name="category_id" 
      style="width: 100%; 
             padding: 10px 14px; 
             border: 1px solid #ccc; 
             border-radius: 10px; 
             font-size: 15px; 
             background: linear-gradient(135deg, #ffffff, #f9f9ff);
             color: #333;
             box-shadow: 0 1px 3px rgba(0,0,0,0.1);
             transition: all 0.2s ease;
             cursor: pointer;">
      <option value="">Select a category (optional)</option>
      <?php 
      $categories_result->data_seek(0);
      while ($cat = $categories_result->fetch_assoc()): 
      ?>
        <option value="<?php echo $cat['id']; ?>">
          <?php echo $cat['icon'] . ' ' . htmlspecialchars($cat['name']); ?>
        </option>
      <?php endwhile; ?>
    </select>
  </div>

  <!-- Status -->
  <div class="form-group">
    <label for="status">📊 Status</label>
    <select id="status" name="status"
      style="width: 100%; 
             padding: 10px 14px; 
             border: 1px solid #ccc; 
             border-radius: 10px; 
             font-size: 15px; 
             background: linear-gradient(135deg, #ffffff, #f9f9ff);
             color: #333;
             box-shadow: 0 1px 3px rgba(0,0,0,0.1);
             transition: all 0.2s ease;
             cursor: pointer;">
      <option value="published">Published (Visible to all)</option>
      <option value="draft">Draft (Save for later)</option>
    </select>
  </div>
</div>
            
            <div class="form-group">
                <label for="image">🖼️ Featured Image</label>
                <?php if (!empty($post['image_path'])): ?>
                    <div class="current-image">
                        <p><strong>Current Image:</strong></p>
                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" style="max-width: 300px; border-radius: 8px;">
                        <label style="display: block; margin-top: 10px;">
                            <input type="checkbox" name="remove_image" value="1"> Remove current image
                        </label>
                    </div>
                <?php endif; ?>
                <div class="image-upload-container">
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                    <div id="image-preview" class="image-preview"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="content">📄 Content (Markdown Supported)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="insertMarkdown('**', '**')" title="Bold"><strong>B</strong></button>
                    <button type="button" onclick="insertMarkdown('*', '*')" title="Italic"><em>I</em></button>
                    <button type="button" onclick="insertMarkdown('# ', '')" title="Heading">H1</button>
                    <button type="button" onclick="insertMarkdown('[', '](url)')" title="Link">🔗</button>
                    <button type="button" onclick="insertMarkdown('`', '`')" title="Code">&lt;/&gt;</button>
                </div>
                <textarea id="content" name="content" rows="20" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                <div class="content-stats">
                    <span id="word-count">0 words</span>
                    <span id="char-count">0 characters</span>
                    <span id="reading-time">0 min read</span>
                </div>
            </div>
            
            <div class="editor-preview">
                <h3>👁️ Preview</h3>
                <div id="preview" class="preview-content"></div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Update Blog</button>
                <a href="view_blog.php?id=<?php echo $blog_id; ?>" class="btn btn-secondary">❌ Cancel</a>
            </div>
        </form>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="js/editor.js"></script>
    <script>
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
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function insertMarkdown(before, after) {
            const textarea = document.getElementById('content');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            const newText = before + selectedText + after;
            
            textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + before.length, end + before.length);
            textarea.dispatchEvent(new Event('input'));
        }
        
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
        
        // Trigger initial count
        textarea.dispatchEvent(new Event('input'));
    </script>
</body>
</html>