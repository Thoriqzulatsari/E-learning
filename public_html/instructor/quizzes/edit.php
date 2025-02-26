<?php
// instructor/quizzes/edit.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$quiz_id) {
    $_SESSION['error'] = "Invalid quiz ID";
    header("Location: index.php");
    exit;
}

// Fetch quiz data along with the course
$query = "SELECT q.*, c.course_id, c.title AS course_title 
          FROM quizzes q
          JOIN courses c ON q.course_id = c.course_id
          WHERE q.quiz_id = ? AND c.instructor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $quiz_id, $instructor_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    $_SESSION['error'] = "Quiz not found or you don't have permission to edit it";
    header("Location: index.php");
    exit;
}

// Fetch all courses for this instructor for the dropdown
$course_query = "SELECT course_id, title FROM courses WHERE instructor_id = ? ORDER BY title";
$course_stmt = $conn->prepare($course_query);
$course_stmt->bind_param("i", $instructor_id);
$course_stmt->execute();
$courses = $course_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $course_id = intval($_POST['course_id']);
    $time_limit = intval($_POST['time_limit']);
    $passing_score = intval($_POST['passing_score']);
    $is_randomized = isset($_POST['is_randomized']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Quiz title is required";
    }
    
    if ($time_limit < 1) {
        $errors[] = "Time limit must be at least 1 minute";
    }
    
    if ($passing_score < 0 || $passing_score > 100) {
        $errors[] = "Passing score must be between 0 and 100";
    }
    
    // Verify the course belongs to this instructor
    $course_check = "SELECT course_id FROM courses WHERE course_id = ? AND instructor_id = ?";
    $check_stmt = $conn->prepare($course_check);
    $check_stmt->bind_param("ii", $course_id, $instructor_id);
    $check_stmt->execute();
    $course_result = $check_stmt->get_result();
    
    if ($course_result->num_rows === 0) {
        $errors[] = "Invalid course selection";
    }
    
    if (empty($errors)) {
        // Update the quiz in the database
        $update_query = "UPDATE quizzes SET 
                        title = ?, 
                        description = ?, 
                        course_id = ?, 
                        time_limit = ?, 
                        passing_score = ?, 
                        is_randomized = ?,
                        is_active = ?,
                        updated_at = NOW()
                        WHERE quiz_id = ?";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssiiiiii", $title, $description, $course_id, $time_limit, $passing_score, $is_randomized, $is_active, $quiz_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Quiz updated successfully";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error'] = "Error updating quiz: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz - Instructor Dashboard</title>
    <!-- CSS dan Bootstrap sudah termasuk di header.php -->
</head>
<body>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Quiz</h4>
                        <a href="index.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Quizzes
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="edit.php?id=<?php echo $quiz_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
                            <small class="text-muted">Provide a brief description of what this quiz covers.</small>
                        </div>

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['course_id']; ?>" <?php echo ($course['course_id'] == $quiz['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="time_limit" class="form-label">Time Limit (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="time_limit" name="time_limit" min="1" value="<?php echo $quiz['time_limit']; ?>" required>
                                <small class="text-muted">How long students have to complete the quiz.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="passing_score" name="passing_score" min="0" max="100" value="<?php echo $quiz['passing_score']; ?>" required>
                                <small class="text-muted">Minimum percentage required to pass the quiz.</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_randomized" name="is_randomized" <?php echo ($quiz['is_randomized'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_randomized">
                                        Randomize Questions
                                    </label>
                                    <div class="form-text">Shuffle questions for each attempt</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo ($quiz['is_active'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                    <div class="form-text">Make quiz available to students</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Quiz
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-success">
                            <i class="fas fa-list me-1"></i> Manage Questions
                        </a>
                        <small class="text-muted">Last updated: <?php echo date('M d, Y H:i', strtotime($quiz['updated_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>