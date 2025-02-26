<?php
// admin/courses.php
require_once '../includes/header.php';
requireRole('admin');

// Handle course deletion
if (isset($_POST['delete_course'])) {
    $course_id = (int)$_POST['course_id'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Course deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting course";
    }
    header("Location: courses.php");
    exit;
}

// Fetch all courses with instructor information
$query = "SELECT c.*, u.username as instructor_name, 
          (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_students
          FROM courses c 
          LEFT JOIN users u ON c.instructor_id = u.user_id 
          ORDER BY c.created_at DESC";
$courses = $conn->query($query);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Course Management</h2>
        <a href="course_add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Course
        </a>
    </div>

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

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Instructor</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</small>
                                </td>
                                <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                <td><?php echo $course['enrolled_students']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $course['status'] == 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="course_edit.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="course_materials.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this course?');">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit" name="delete_course" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>