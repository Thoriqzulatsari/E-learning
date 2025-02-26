<?php
// instructor/materials.php
require_once '../includes/header.php';
requireRole('instructor');

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify course belongs to instructor
$course_query = $conn->prepare("SELECT * FROM courses WHERE course_id = ? AND instructor_id = ?");
$course_query->bind_param("ii", $course_id, $_SESSION['user_id']);
$course_query->execute();
$course = $course_query->get_result()->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found or unauthorized access";
    header("Location: courses/index.php");
    exit;
}

// Handle material upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $content_type = $conn->real_escape_string($_POST['content_type']);
    $order_number = (int)$_POST['order_number'];
    $content = null;
    $file_path = null;

    // Handle different content types
    switch ($content_type) {
        case 'text':
            $content = $conn->real_escape_string($_POST['text_content']);
            break;
        
        case 'link':
            $content = filter_var($_POST['link_content'], FILTER_VALIDATE_URL);
            break;
        
        case 'pdf':
        case 'video':
            if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
                $upload_dir = '../uploads/materials/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION));
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // Validate file type
                $allowed_extensions = $content_type == 'pdf' ? 
                    ['pdf'] : 
                    ['mp4', 'avi', 'mov', 'wmv', 'flv'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $upload_path)) {
                        $file_path = $new_filename;
                    } else {
                        $_SESSION['error'] = "File upload failed.";
                        header("Location: materials.php?id=" . $course_id);
                        exit;
                    }
                } else {
                    $_SESSION['error'] = "Invalid file type.";
                    header("Location: materials.php?id=" . $course_id);
                    exit;
                }
            }
            break;
    }

    // Insert material
    $stmt = $conn->prepare("INSERT INTO materials (course_id, title, content_type, content, file_path, order_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $course_id, $title, $content_type, $content, $file_path, $order_number);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Material added successfully";
        header("Location: materials.php?id=" . $course_id);
        exit;
    } else {
        $_SESSION['error'] = "Error adding material: " . $conn->error;
    }
}

// Fetch existing materials
$materials_query = $conn->prepare("SELECT * FROM materials WHERE course_id = ? ORDER BY order_number");
$materials_query->bind_param("i", $course_id);
$materials_query->execute();
$materials = $materials_query->get_result();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Manage Materials: <?php echo htmlspecialchars($course['title']); ?></h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Add Material Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Material Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="content_type" class="form-label">Content Type</label>
                            <select class="form-select" id="content_type" name="content_type" required>
                                <option value="">Select Content Type</option>
                                <option value="text">Text</option>
                                <option value="link">External Link</option>
                                <option value="pdf">PDF Document</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <div id="content_input_container" class="mb-3" style="display:none;">
                            <!-- Dynamic content input will be inserted here via JavaScript -->
                        </div>

                        <div class="mb-3">
                            <label for="order_number" class="form-label">Order Number</label>
                            <input type="number" class="form-control" id="order_number" name="order_number" min="1" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Material</button>
                    </form>

                    <!-- Materials List -->
                    <hr>
                    <h5>Existing Materials</h5>
                    <?php if ($materials->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($material = $materials->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($material['title']); ?></h6>
                                        <small class="text-muted">
                                            Type: <?php echo ucfirst($material['content_type']); ?> | 
                                            Order: <?php echo $material['order_number']; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="#" class="btn btn-sm btn-info">Edit</a>
                                        <a href="#" class="btn btn-sm btn-danger">Delete</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No materials added yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('content_type').addEventListener('change', function() {
    const container = document.getElementById('content_input_container');
    const contentType = this.value;
    
    // Clear previous content
    container.innerHTML = '';
    container.style.display = 'none';

    switch(contentType) {
        case 'text':
            container.innerHTML = `
                <label for="text_content" class="form-label">Text Content</label>
                <textarea class="form-control" id="text_content" name="text_content" rows="5"></textarea>
            `;
            container.style.display = 'block';
            break;
        
        case 'link':
            container.innerHTML = `
                <label for="link_content" class="form-label">External Link URL</label>
                <input type="url" class="form-control" id="link_content" name="link_content">
            `;
            container.style.display = 'block';
            break;
        
        case 'pdf':
            container.innerHTML = `
                <label for="file_upload" class="form-label">Upload PDF</label>
                <input type="file" class="form-control" id="file_upload" name="file_upload" accept=".pdf">
            `;
            container.style.display = 'block';
            break;
        
        case 'video':
            container.innerHTML = `
                <label for="file_upload" class="form-label">Upload Video</label>
                <input type="file" class="form-control" id="file_upload" name="file_upload" accept="video/*">
            `;
            container.style.display = 'block';
            break;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>