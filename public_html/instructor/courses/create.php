<?php
// instructor/courses/create.php
require_once '../../includes/header.php';
requireRole('instructor');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $instructor_id = $_SESSION['user_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Handle thumbnail upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = '../../uploads/thumbnails/' . $new_filename;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                $thumbnail = $new_filename;
            }
        }
    }
    
    $sql = "INSERT INTO courses (title, description, instructor_id, thumbnail, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssiss", $title, $description, $instructor_id, $thumbnail, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course created successfully!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error creating course: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Create New Course</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Course Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail" class="form-label">Course Thumbnail</label>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Recommended size: 1280x720 pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Course Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Course</button>
                            <a href="index.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>