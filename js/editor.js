// Markdown Editor with Live Preview
document.addEventListener('DOMContentLoaded', function() {
    const contentTextarea = document.getElementById('content');
    const previewDiv = document.getElementById('preview');
    
    if (contentTextarea && previewDiv) {
        // Initial render
        updatePreview();
        
        // Update preview on input
        contentTextarea.addEventListener('input', updatePreview);
        
        function updatePreview() {
            const markdownText = contentTextarea.value;
            if (markdownText.trim() === '') {
                previewDiv.innerHTML = '<p style="color: #999;">Preview will appear here...</p>';
            } else {
                previewDiv.innerHTML = marked.parse(markdownText);
            }
        }
    }
});