<?php
// student/quizzes.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

// Get all quizzes from enrolled courses
$quizzes_query = $conn->prepare("
    SELECT 
        q.quiz_id,
        q.title as quiz_title,
        q.description,
        q.time_limit,
        q.passing_score,
        c.title as course_title,
        c.course_id,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as total_questions,
        (SELECT qa.score 
         FROM quiz_attempts qa 
         WHERE qa.quiz_id = q.quiz_id 
         AND qa.user_id = ? 
         ORDER BY qa.attempt_id DESC 
         LIMIT 1) as last_score,
        (SELECT COUNT(*) 
         FROM quiz_attempts qa 
         WHERE qa.quiz_id = q.quiz_id 
         AND qa.user_id = ?) as attempts_count
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE e.user_id = ?
    ORDER BY c.title, q.title");

$quizzes_query->bind_param("iii", $student_id, $student_id, $student_id);
$quizzes_query->execute();
$quizzes = $quizzes_query->get_result();

// Get quiz statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(DISTINCT q.quiz_id) as total_quizzes,
        COUNT(DISTINCT CASE WHEN qa.score >= q.passing_score THEN q.quiz_id END) as passed_quizzes,
        COALESCE(AVG(qa.score), 0) as average_score
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = ?
    WHERE e.user_id = ?");

$stats_query->bind_param("ii", $student_id, $student_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
?>

<div class="container-fluid py-4">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Quizzes</h6>
                            <h2 class="mb-0"><?php echo $stats['total_quizzes']; ?></h2>
                        </div>
                        <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Passed Quizzes</h6>
                            <h2 class="mb-0"><?php echo $stats['passed_quizzes']; ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Average Score</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['average_score'], 1); ?>%</h2>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz List -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">My Quizzes</h5>
        </div>
        <div class="card-body">
            <?php if ($quizzes->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Course</th>
                                <th>Questions</th>
                                <th>Time Limit</th>
                                <th>Passing Score</th>
                                <th>Your Score</th>
                                <th>Attempts</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($quiz['quiz_title']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo substr(htmlspecialchars($quiz['description']), 0, 50); ?>...
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($quiz['course_title']); ?></td>
                                    <td><?php echo $quiz['total_questions']; ?></td>
                                    <td><?php echo $quiz['time_limit']; ?> mins</td>
                                    <td><?php echo $quiz['passing_score']; ?>%</td>
                                    <td>
                                        <?php if ($quiz['last_score'] !== null): ?>
                                            <span class="badge bg-<?php echo $quiz['last_score'] >= $quiz['passing_score'] ? 'success' : 'danger'; ?>">
                                                <?php echo $quiz['last_score']; ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not attempted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $quiz['attempts_count']; ?></td>
                                    <td>
                                        <?php if ($quiz['last_score'] !== null && $quiz['last_score'] >= $quiz['passing_score']): ?>
                                            <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                               class="btn btn-sm btn-success">
                                                <i class="fas fa-eye"></i> Review
                                            </a>
                                        <?php else: ?>
                                            <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <?php echo $quiz['attempts_count'] > 0 ? 'Retake Quiz' : 'Start Quiz'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-clipboard-list fa-4x text-muted"></i>
                    </div>
                    <h4>No Quizzes Available</h4>
                    <p class="text-muted">You haven't enrolled in any courses with quizzes yet.</p>
                    <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>