<?php
// forum/create.php
require_once '../includes/header.php';
?>

<div class="container my-4">
    <h1>Create New Topic</h1>
    
    <form action="process_topic.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="forum_id" class="form-label">Select Forum</label>
            <select class="form-select" id="forum_id" name="forum_id" required>
                <option value="">Choose a forum...</option>
                <option value="1">Introduction to Web Development</option>
                <option value="2">Advanced JavaScript Techniques</option>
                <option value="3">UI/UX Design Tips</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label for="title" class="form-label">Topic Title</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
        </div>
        
        <div class="mb-3">
            <label for="attachments" class="form-label">Attachments (Optional)</label>
            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
            <div class="form-text">You can upload images, documents or other files (Max 5MB each)</div>
        </div>
        
        <button type="submit" class="btn btn-primary">Create Topic</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>