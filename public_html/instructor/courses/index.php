<?php
// instructor/courses/index.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];

// Fetch all courses for this instructor
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as total_students,
          (SELECT COUNT(*) FROM materials WHERE course_id = c.course_id) as total_materials,
          (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as total_quizzes
          FROM courses c 
          WHERE c.instructor_id = ?
          ORDER BY c.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Courses</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Course
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php while ($course = $courses->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <?php if ($course['thumbnail']): ?>
                        <img src="../../uploads/thumbnails/<?php echo $course['thumbnail']; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php endif; ?>
                    <div class="card-header">
                        <span class="badge bg-<?php echo $course['status'] == 'published' ? 'success' : 'warning'; ?> float-end">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($course['title']); ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 150); ?>...</p>
                        <div class="row text-center mb-3">
                            <div class="col">
                                <h5 class="mb-0"><?php echo $course['total_students']; ?></h5>
                                <small class="text-muted">Students</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0"><?php echo $course['total_materials']; ?></h5>
                                <small class="text-muted">Materials</small>
                            </div>
                            <div class="col">
                                <h5 class="mb-0"><?php echo $course['total_quizzes']; ?></h5>
                                <small class="text-muted">Quizzes</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between">
                            <a href="edit.php?id=<?php echo $course['course_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="materials.php?id=<?php echo $course['course_id']; ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-book"></i> Materials
                            </a>
                            <a href="../quizzes/create.php?course_id=<?php echo $course['course_id']; ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Add Quiz
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($courses->num_rows === 0): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <h4>No courses yet</h4>
                    <p>Start by creating your first course!</p>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Course
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>