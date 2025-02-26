<?php
// student/view_course.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify enrollment
$check_enrollment = $conn->prepare("
    SELECT e.*, c.title, c.description, u.username as instructor_name 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    JOIN users u ON c.instructor_id = u.user_id
    WHERE e.user_id = ? AND e.course_id = ?
");
$check_enrollment->bind_param("ii", $student_id, $course_id);
$check_enrollment->execute();
$enrollment = $check_enrollment->get_result()->fetch_assoc();

if (!$enrollment) {
    $_SESSION['error'] = "Please enroll in this course first.";
    header("Location: courses.php");
    exit;
}

// Get course materials
$materials_query = $conn->prepare("
    SELECT * FROM materials 
    WHERE course_id = ? 
    ORDER BY order_number
");
$materials_query->bind_param("i", $course_id);
$materials_query->execute();
$materials = $materials_query->get_result();

// Get quizzes
$quizzes_query = $conn->prepare("
    SELECT q.*, 
    (SELECT qa.score FROM quiz_attempts qa 
     WHERE qa.quiz_id = q.quiz_id AND qa.user_id = ? 
     ORDER BY qa.attempt_id DESC LIMIT 1) as last_score,
    (SELECT COUNT(*) FROM quiz_attempts qa 
     WHERE qa.quiz_id = q.quiz_id AND qa.user_id = ?) as attempts_count
    FROM quizzes q 
    WHERE q.course_id = ?
");
$quizzes_query->bind_param("iii", $student_id, $student_id, $course_id);
$quizzes_query->execute();
$quizzes = $quizzes_query->get_result();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0"><?php echo htmlspecialchars($enrollment['title']); ?></h4>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Instructor:</strong> <?php echo htmlspecialchars($enrollment['instructor_name']); ?></p>
                    <p><?php echo htmlspecialchars($enrollment['description']); ?></p>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" 
                             style="width: <?php echo $enrollment['progress']; ?>%"
                             aria-valuenow="<?php echo $enrollment['progress']; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            Progress: <?php echo $enrollment['progress']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Course Materials -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Course Materials</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($materials->num_rows > 0): ?>
                        <?php while ($material = $materials->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($material['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo ucfirst($material['content_type']); ?>
                                        </small>
                                    </div>
                                    <a href="view_material.php?id=<?php echo $material['material_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item">
                            <p class="mb-0">No materials available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quizzes -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quizzes</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($quizzes->num_rows > 0): ?>
                        <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                <div class="mb-2">
                                    <small class="text-muted">
                                        Time Limit: <?php echo $quiz['time_limit']; ?> minutes
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        Passing Score: <?php echo $quiz['passing_score']; ?>%
                                    </small>
                                </div>
                                
                                <?php if ($quiz['last_score'] !== null): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-<?php echo $quiz['last_score'] >= $quiz['passing_score'] ? 'success' : 'danger'; ?>">
                                            Last Score: <?php echo $quiz['last_score']; ?>%
                                        </span>
                                        <small class="text-muted ms-2">
                                            (<?php echo $quiz['attempts_count']; ?> attempts)
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                   class="btn btn-sm <?php echo $quiz['last_score'] >= $quiz['passing_score'] ? 'btn-success' : 'btn-primary'; ?> w-100">
                                    <?php if ($quiz['last_score'] === null): ?>
                                        Start Quiz
                                    <?php elseif ($quiz['last_score'] >= $quiz['passing_score']): ?>
                                        Review Quiz
                                    <?php else: ?>
                                        Retake Quiz
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="list-group-item">
                            <p class="mb-0">No quizzes available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>