<?php
// forum/create.php - A simplified version that should work in most environments
require_once '../includes/header.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if (!$is_logged_in) {
    echo '<div class="container my-4"><div class="alert alert-danger">You must be logged in to create a topic.</div></div>';
    echo '<div class="container my-4"><a href="/auth/login.php" class="btn btn-primary">Log In</a></div>';
    require_once '../includes/footer.php';
    exit;
}

// Define hard-coded categories for fallback
$default_categories = [
    ['category_id' => 1, 'category_name' => 'General Discussion'],
    ['category_id' => 2, 'category_name' => 'Technical Support'],
    ['category_id' => 3, 'category_name' => 'Course Feedback'],
    ['category_id' => 4, 'category_name' => 'Study Groups']
];

// Try to get categories from database
$categories = [];
$db_connected = isset($conn) && !$conn->connect_error;

if ($db_connected) {
    try {
        $result = $conn->query("SELECT category_id, category_name FROM forum_categories ORDER BY category_name");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
    } catch (Exception $e) {
        // Silently fail and use default categories
    }
}

// If no categories were loaded from database, use defaults
if (empty($categories)) {
    $categories = $default_categories;
}

// Try to get courses if database is connected
$courses = [];
if ($db_connected) {
    try {
        $result = $conn->query("SELECT course_id, title FROM courses WHERE status = 'published' ORDER BY title");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $courses[] = $row;
            }
        }
    } catch (Exception $e) {
        // Silently fail - courses are optional
    }
}
?>

<div class="container my-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1 class="h3 mb-0">Create New Topic</h1>
        </div>
        
        <div class="card-body">
            <?php if (function_exists('display_message')) display_message(); ?>
            
            <form action="process_topic.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="category_id" class="form-label">Select Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Choose a category...</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($courses)): ?>
                <div class="mb-3">
                    <label for="course_id" class="form-label">Related Course (Optional)</label>
                    <select class="form-select" id="course_id" name="course_id">
                        <option value="">Not related to a specific course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">If your topic is related to a specific course, select it here.</div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Topic Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                    <div class="form-text">Be specific and descriptive</div>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="attachments" class="form-label">Attachments (Optional)</label>
                    <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                    <div class="form-text">You can upload images, documents or other files (Max 5MB each)</div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Create Topic</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>