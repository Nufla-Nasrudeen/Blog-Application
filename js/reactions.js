// Reactions System - AJAX based
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.reactions-container');
    if (!container || !container.dataset.blogId) return;
    
    const blogId = container.dataset.blogId;
    
    // Load current reactions
    loadReactions();
    
    // Handle reaction button clicks
    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.reaction-btn');
        if (!btn) return;
        
        const type = btn.dataset.type;
        const isActive = btn.classList.contains('active');
        
        // Disable button temporarily
        btn.disabled = true;
        
        fetch('api/reaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: isActive ? 'remove' : 'add',
                blog_id: parseInt(blogId),
                reaction_type: type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionUI(data.counts, data.user_reaction);
            } else {
                console.error('Reaction failed:', data.message);
                alert(data.message || 'Failed to process reaction');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to process reaction. Please try again.');
        })
        .finally(() => {
            btn.disabled = false;
        });
    });
    
    function loadReactions() {
        fetch('api/reaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'get',
                blog_id: parseInt(blogId)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateReactionUI(data.counts, data.user_reaction);
            }
        })
        .catch(error => {
            console.error('Error loading reactions:', error);
        });
    }
    
    function updateReactionUI(counts, userReaction) {
        document.querySelectorAll('.reaction-btn').forEach(btn => {
            const type = btn.dataset.type;
            const count = counts[type] || 0;
            const countSpan = btn.querySelector('.count');
            
            if (countSpan) {
                countSpan.textContent = count;
            }
            
            // Update active state
            if (type === userReaction) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }
});