<?php
// instructor/quizzes/create.php
require_once '../../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];

// Fetch courses taught by this instructor
$query = "SELECT course_id, title FROM courses WHERE instructor_id = ? AND status = 'published'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$courses = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $course_id = (int)$_POST['course_id'];
    $time_limit = (int)$_POST['time_limit'];
    $passing_score = (int)$_POST['passing_score'];
    
    // Verify the course belongs to this instructor
    $verify_query = "SELECT course_id FROM courses WHERE course_id = ? AND instructor_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $course_id, $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $sql = "INSERT INTO quizzes (course_id, title, description, time_limit, passing_score, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issii", $course_id, $title, $description, $time_limit, $passing_score);
            
            if ($stmt->execute()) {
                $quiz_id = $conn->insert_id;
                $_SESSION['success'] = "Quiz created successfully. Add questions now.";
                header("Location: questions.php?quiz_id=" . $quiz_id);
                exit;
            } else {
                $error = "Error creating quiz: " . $conn->error;
            }
        }
    } else {
        $error = "Invalid course selected.";
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="card-title">Create New Quiz</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="course_id" class="form-label">Select Course</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Choose a course...</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php 
                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                           min="1" max="180" required
                                           value="<?php echo isset($_POST['time_limit']) ? $_POST['time_limit'] : '30'; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score (%)</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                           min="0" max="100" required
                                           value="<?php echo isset($_POST['passing_score']) ? $_POST['passing_score'] : '70'; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create Quiz</button>
                            <a href="index.php" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>