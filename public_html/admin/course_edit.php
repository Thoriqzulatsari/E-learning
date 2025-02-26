<?php
// admin/course_edit.php
require_once '../includes/header.php';
requireRole('admin');

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found";
    header("Location: courses.php");
    exit;
}

// Fetch all instructors
$instructors = $conn->query("SELECT user_id, username, full_name FROM users WHERE role = 'instructor'");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $instructor_id = (int)$_POST['instructor_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Handle thumbnail upload
    $thumbnail = $course['thumbnail']; // Keep existing thumbnail by default
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = '../uploads/thumbnails/' . $new_filename;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                // Delete old thumbnail if exists
                if ($thumbnail && file_exists('../uploads/thumbnails/' . $thumbnail)) {
                    unlink('../uploads/thumbnails/' . $thumbnail);
                }
                $thumbnail = $new_filename;
            }
        }
    }
    
    $sql = "UPDATE courses SET title = ?, description = ?, instructor_id = ?, 
            thumbnail = ?, status = ? WHERE course_id = ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssissi", $title, $description, $instructor_id, $thumbnail, $status, $course_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course updated successfully";
            header("Location: courses.php");
            exit;
        } else {
            $error = "Error updating course: " . $conn->error;
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
                    <h4 class="card-title">Edit Course</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Course Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($course['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="instructor_id" class="form-label">Instructor</label>
                            <select class="form-control" id="instructor_id" name="instructor_id" required>
                                <option value="">Select Instructor</option>
                                <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                    <option value="<?php echo $instructor['user_id']; ?>" 
                                            <?php echo ($instructor['user_id'] == $course['instructor_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($instructor['full_name']); ?> 
                                        (<?php echo htmlspecialchars($instructor['username']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail" class="form-label">Course Thumbnail</label>
                            <?php if ($course['thumbnail']): ?>
                                <div class="mb-2">
                                    <img src="../uploads/thumbnails/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                         alt="Current thumbnail" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Leave empty to keep current thumbnail. Recommended size: 1280x720 pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="draft" <?php echo ($course['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($course['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo ($course['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="courses.php" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Course
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>