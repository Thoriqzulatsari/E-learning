<?php
// admin/course_add.php
require_once '../includes/header.php';
requireRole('admin');

// Fetch all instructors
$instructors = $conn->query("SELECT user_id, username, full_name FROM users WHERE role = 'instructor'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $instructor_id = (int)$_POST['instructor_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Handle thumbnail upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/thumbnails/' . $new_filename;
            
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
            $_SESSION['success'] = "Course created successfully";
            header("Location: courses.php");
            exit;
        } else {
            $error = "Error creating course: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="card-title">Add New Course</h4>
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
                            <label for="instructor_id" class="form-label">Instructor</label>
                            <select class="form-control" id="instructor_id" name="instructor_id" required>
                                <option value="">Select Instructor</option>
                                <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                    <option value="<?php echo $instructor['user_id']; ?>">
                                        <?php echo htmlspecialchars($instructor['full_name']); ?> 
                                        (<?php echo htmlspecialchars($instructor['username']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail" class="form-label">Course Thumbnail</label>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Recommended size: 1280x720 pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Course</button>
                            <a href="courses.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>