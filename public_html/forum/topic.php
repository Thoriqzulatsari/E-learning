<?php
// forum/topic.php
require_once '../includes/header.php';

// Get topic ID from URL
$topic_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// In a real application, you would fetch the topic from database
// For demonstration, we'll use sample data
$topic = [
    'id' => $topic_id,
    'title' => 'Sample Topic Title',
    'content' => 'This is the content of the sample topic. It would normally be loaded from a database.',
    'author' => 'User123',
    'created_at' => '2025-02-20 15:30:45',
    'forum_id' => 1
];

// Sample replies
$replies = [
    [
        'id' => 1,
        'content' => 'This is a reply to the topic. Great discussion!',
        'author' => 'User456',
        'created_at' => '2025-02-20 16:15:22'
    ],
    [
        'id' => 2,
        'content' => 'I have a question about this topic...',
        'author' => 'User789',
        'created_at' => '2025-02-21 09:45:10'
    ]
];
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Forums</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($topic['title']); ?></li>
        </ol>
    </nav>
    
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h2 class="mb-0 fs-4"><?php echo htmlspecialchars($topic['title']); ?></h2>
            <small>Posted by <?php echo htmlspecialchars($topic['author']); ?> on <?php echo date('F j, Y, g:i a', strtotime($topic['created_at'])); ?></small>
        </div>
        <div class="card-body">
            <div class="topic-content mb-3">
                <?php echo nl2br(htmlspecialchars($topic['content'])); ?>
            </div>
            
            <!-- Display attachments if any -->
            <div class="attachments mb-3">
                <!-- In a real app, you would display attachments here -->
            </div>
        </div>
    </div>
    
    <h3>Replies</h3>
    
    <?php if (empty($replies)): ?>
        <div class="alert alert-info">No replies yet. Be the first to reply!</div>
    <?php else: ?>
        <?php foreach ($replies as $reply): ?>
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($reply['author']); ?></span>
                    <small><?php echo date('F j, Y, g:i a', strtotime($reply['created_at'])); ?></small>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h3 class="mb-0 fs-5">Post a Reply</h3>
        </div>
        <div class="card-body">
            <form action="process_reply.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="topic_id" value="<?php echo $topic_id; ?>">
                
                <div class="mb-3">
                    <label for="reply-content" class="form-label">Your Reply</label>
                    <textarea class="form-control" id="reply-content" name="content" rows="4" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="reply-attachments" class="form-label">Attachments (Optional)</label>
                    <input type="file" class="form-control" id="reply-attachments" name="attachments[]" multiple>
                    <div class="form-text">You can upload images, documents or other files (Max 5MB each)</div>
                </div>
                
                <button type="submit" class="btn btn-primary">Post Reply</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>