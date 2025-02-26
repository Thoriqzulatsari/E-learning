<?php
// instructor/quizzes/index.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];

// Fetch all quizzes for this instructor
$query = "SELECT q.*, c.title as course_title,
          (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.quiz_id) as total_questions,
          (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.quiz_id) as total_attempts
          FROM quizzes q
          JOIN courses c ON q.course_id = c.course_id
          WHERE c.instructor_id = ?
          ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$quizzes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management - Instructor Dashboard</title>
    <!-- CSS dan Bootstrap sudah termasuk di header.php -->
</head>
<body>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Quiz Management</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Quiz
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

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($quizzes->num_rows > 0): ?>
            <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Course:</strong> <?php echo htmlspecialchars($quiz['course_title']); ?></p>
                            <p class="mb-2"><strong>Questions:</strong> <?php echo $quiz['total_questions']; ?></p>
                            <p class="mb-2"><strong>Attempts:</strong> <?php echo $quiz['total_attempts']; ?></p>
                            <p class="mb-2"><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> minutes</p>
                            <p class="mb-2"><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</p>
                            <p class="mb-0">
                                <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></small>
                            </p>
                        </div>
                       <div class="card-footer bg-transparent p-2">
    <div class="row g-1">
        <div class="col-4">
            <a href="edit.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-sm btn-primary w-100" title="Edit Quiz">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
        <div class="col-4">
            <a href="questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-sm btn-info w-100" title="Manage Questions">
                <i class="fas fa-list"></i> Questions
            </a>
        </div>
        <div class="col-4">
            <button type="button" class="btn btn-sm btn-danger w-100" title="Delete Quiz"
                    onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>)">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading">No Quizzes Found!</h4>
                    <p>You haven't created any quizzes yet. Click the "Create New Quiz" button to get started.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Quiz Modal -->
<div class="modal fade" id="deleteQuizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this quiz?</p>
                <p class="text-danger"><strong>Warning:</strong> This will also delete:</p>
                <ul class="text-danger">
                    <li>All quiz questions and options</li>
                    <li>All student attempts and scores</li>
                </ul>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete.php" method="POST">
                    <input type="hidden" name="quiz_id" id="deleteQuizId">
                    <button type="submit" class="btn btn-danger">Delete Quiz</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script untuk delete quiz -->
<script>
function deleteQuiz(quizId) {
    if (document.getElementById('deleteQuizId')) {
        document.getElementById('deleteQuizId').value = quizId;
        var modal = new bootstrap.Modal(document.getElementById('deleteQuizModal'));
        modal.show();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>