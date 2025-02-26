<?php
// instructor/quizzes/manage.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Verify course belongs to instructor
$verify_query = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as quiz_count
    FROM courses c 
    WHERE c.course_id = ? AND c.instructor_id = ?
");
$verify_query->bind_param("ii", $course_id, $instructor_id);
$verify_query->execute();
$course = $verify_query->get_result()->fetch_assoc();

if (!$course) {
    $_SESSION['error'] = "Course not found or unauthorized access";
    header("Location: index.php");
    exit;
}

// Get all quizzes for this course
$quizzes_query = $conn->prepare("
    SELECT q.*,
           (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as question_count,
           (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as attempt_count,
           (SELECT AVG(score) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as average_score
    FROM quizzes q
    WHERE q.course_id = ?
    ORDER BY q.created_at DESC
");
$quizzes_query->bind_param("i", $course_id);
$quizzes_query->execute();
$quizzes = $quizzes_query->get_result();
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Quiz Management - <?php echo htmlspecialchars($course['title']); ?></h4>
                    <a href="create.php?course_id=<?php echo $course_id; ?>" class="btn btn-light">
                        <i class="fas fa-plus"></i> Add New Quiz
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Quizzes</h5>
                                    <h2><?php echo $course['quiz_count']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($quizzes->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Quiz Title</th>
                                        <th>Questions</th>
                                        <th>Time Limit</th>
                                        <th>Passing Score</th>
                                        <th>Attempts</th>
                                        <th>Average Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $quiz['question_count']; ?> Questions
                                                </span>
                                            </td>
                                            <td><?php echo $quiz['time_limit']; ?> minutes</td>
                                            <td><?php echo $quiz['passing_score']; ?>%</td>
                                            <td><?php echo $quiz['attempt_count']; ?></td>
                                            <td>
                                                <?php if ($quiz['average_score']): ?>
                                                    <?php echo number_format($quiz['average_score'], 1); ?>%
                                                <?php else: ?>
                                                    No attempts
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="Manage Questions">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="Edit Quiz">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger" 
                                                            onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>)"
                                                            title="Delete Quiz">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5>No Quizzes Yet</h5>
                            <p>This course doesn't have any quizzes. Click "Add New Quiz" to create your first quiz.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Quiz Statistics -->
<?php if ($quizzes->num_rows > 0): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Quiz Attempts</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get recent quiz attempts
                    $attempts_query = $conn->prepare("
                        SELECT qa.*, 
                               q.title as quiz_title, 
                               q.passing_score,
                               u.username, 
                               u.full_name
                        FROM quiz_attempts qa
                        JOIN quizzes q ON qa.quiz_id = q.quiz_id
                        JOIN users u ON qa.user_id = u.user_id
                        WHERE q.course_id = ?
                        ORDER BY qa.completed_at DESC
                        LIMIT 10
                    ");
                    $attempts_query->bind_param("i", $course_id);
                    $attempts_query->execute();
                    $attempts = $attempts_query->get_result();
                    ?>

                    <?php if ($attempts->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Quiz</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($attempt = $attempts->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attempt['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($attempt['quiz_title']); ?></td>
                                            <td><?php echo $attempt['score']; ?>%</td>
                                            <td><?php echo date('M d, Y H:i', strtotime($attempt['completed_at'])); ?></td>
                                            <td>
                                                <?php if ($attempt['score'] >= $attempt['passing_score']): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No quiz attempts yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Quiz Modal -->
<div class="modal fade" id="deleteQuizModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this quiz?</p>
                <p class="text-danger"><strong>Warning:</strong> This will also delete:</p>
                <ul class="text-danger">
                    <li>All questions and answers</li>
                    <li>All student attempts and scores</li>
                </ul>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete.php" method="POST">
                    <input type="hidden" name="quiz_id" id="deleteQuizId">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <button type="submit" class="btn btn-danger">Delete Quiz</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteQuiz(quizId) {
    document.getElementById('deleteQuizId').value = quizId;
    new bootstrap.Modal(document.getElementById('deleteQuizModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>