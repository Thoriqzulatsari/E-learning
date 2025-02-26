<?php
// instructor/courses/edit.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch course details
$query = "SELECT * FROM courses WHERE course_id = ? AND instructor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $course_id, $instructor_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found or unauthorized access";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Handle thumbnail upload
    $thumbnail = $course['thumbnail']; // Keep existing thumbnail by default
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['thumbnail']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = '../../uploads/thumbnails/' . $new_filename;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                // Delete old thumbnail if exists
                if ($thumbnail && file_exists('../../uploads/thumbnails/' . $thumbnail)) {
                    unlink('../../uploads/thumbnails/' . $thumbnail);
                }
                $thumbnail = $new_filename;
            }
        }
    }
    
    $sql = "UPDATE courses SET title = ?, description = ?, thumbnail = ?, status = ? WHERE course_id = ? AND instructor_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssii", $title, $description, $thumbnail, $status, $course_id, $instructor_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course updated successfully!";
            header("Location: index.php");
            exit;
        } else {
            $error = "Error updating course: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Course</h4>
                    <a href="index.php" class="btn btn-light btn-sm">Back to Courses</a>
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
                            <label for="thumbnail" class="form-label">Course Thumbnail</label>
                            <?php if ($course['thumbnail']): ?>
                                <div class="mb-2">
                                    <img src="../../uploads/thumbnails/<?php echo $course['thumbnail']; ?>" 
                                         alt="Current thumbnail" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                            <small class="text-muted">Leave empty to keep current thumbnail. Recommended size: 1280x720 pixels</small>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Course Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft" <?php echo $course['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $course['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $course['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <!-- Course Statistics -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5>Course Statistics</h5>
                                <div class="row text-center">
                                    <?php
                                    // Get enrollments count
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                                    $stmt->bind_param("i", $course_id);
                                    $stmt->execute();
                                    $enrollments = $stmt->get_result()->fetch_row()[0];

                                    // Get materials count
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM materials WHERE course_id = ?");
                                    $stmt->bind_param("i", $course_id);
                                    $stmt->execute();
                                    $materials = $stmt->get_result()->fetch_row()[0];

                                    // Get quizzes count
                                    $stmt = $conn->prepare("SELECT COUNT(*) FROM quizzes WHERE course_id = ?");
                                    $stmt->bind_param("i", $course_id);
                                    $stmt->execute();
                                    $quizzes = $stmt->get_result()->fetch_row()[0];
                                    ?>
                                    <div class="col">
                                        <h3 class="mb-0"><?php echo $enrollments; ?></h3>
                                        <small>Students Enrolled</small>
                                    </div>
                                    <div class="col">
                                        <h3 class="mb-0"><?php echo $materials; ?></h3>
                                        <small>Learning Materials</small>
                                    </div>
                                    <div class="col">
                                        <h3 class="mb-0"><?php echo $quizzes; ?></h3>
                                        <small>Quizzes</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Replace the existing button section with this improved layout -->
<div class="course-actions">
    <div class="action-group">
        <button type="button" class="action-button btn-primary" id="updateCourseBtn">
            <i class="fas fa-save"></i> Update Course
        </button>
        <button type="button" class="action-button btn-info" id="manageMaterialsBtn">
            <i class="fas fa-book"></i> Manage Materials
        </button>
        <button type="button" class="action-button btn-success" id="addQuizBtn">
            <i class=""></i> Add Quiz
        </button>
    </div>
    <div class="action-group">
        <button type="button" class="action-button btn-light" id="cancelBtn">
            <i class="fas fa-times"></i> Cancel
        </button>
        <button type="button" class="action-button btn-danger" id="deleteCourseBtn">
            <i class="fas fa-trash"></i> Delete Course
        </button>
    </div>
</div>

<!-- Add this script to wire up the buttons to the existing functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wire up the new buttons to the existing functionality
    document.getElementById('updateCourseBtn').addEventListener('click', function() {
        // Find the original update course button and click it
        const originalUpdateBtn = document.querySelector('button[type="submit"]');
        if (originalUpdateBtn) originalUpdateBtn.click();
    });
    
    document.getElementById('manageMaterialsBtn').addEventListener('click', function() {
        // Redirect to the materials management page
        window.location.href = 'materials.php?id=' + getUrlParameter('id');
    });
    
    document.getElementById('addQuizBtn').addEventListener('click', function() {
        // Redirect to add quiz page
        window.location.href = '../quizzes/create.php?course_id=' + getUrlParameter('id');
    });
    
    document.getElementById('cancelBtn').addEventListener('click', function() {
        // Redirect back to courses page
        window.location.href = 'index.php';
    });
    
    document.getElementById('deleteCourseBtn').addEventListener('click', function() {
        // Show a confirmation dialog
        if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
            // Find the original delete button and click it
            const originalDeleteBtn = document.querySelector('form[action="delete_course.php"] button');
            if (originalDeleteBtn) originalDeleteBtn.click();
        }
    });
    
    // Hide the original buttons
    const originalButtons = document.querySelectorAll('.btn');
    originalButtons.forEach(btn => {
        btn.style.display = 'none';
    });
});

// Helper function to get URL parameters
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}
</script>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Course Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this course?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will delete:</p>
                <ul class="text-danger">
                    <li>All course materials</li>
                    <li>All quizzes and questions</li>
                    <li>All student enrollments</li>
                    <li>All progress records</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_course.php" method="POST">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <button type="submit" class="btn btn-danger">Delete Course</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>