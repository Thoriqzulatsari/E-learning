<?php
// instructor/quizzes/questions.php
require_once '../../includes/header.php';
requireRole('instructor');

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$instructor_id = $_SESSION['user_id'];

// Verify the quiz belongs to a course taught by this instructor
$verify_query = "SELECT q.*, c.title as course_title 
                FROM quizzes q 
                JOIN courses c ON q.course_id = c.course_id 
                WHERE q.quiz_id = ? AND c.instructor_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $quiz_id, $instructor_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();

if (!$quiz) {
    $_SESSION['error'] = "Quiz not found or unauthorized access";
    header("Location: index.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and process question submission
    $question_text = $conn->real_escape_string($_POST['question_text']);
    $question_type = $conn->real_escape_string($_POST['question_type']);
    $points = (int)$_POST['points'];

    // Start transaction for safe insertion
    $conn->begin_transaction();

    try {
        // Insert question
        $question_insert = $conn->prepare("
            INSERT INTO quiz_questions (quiz_id, question_text, question_type, points) 
            VALUES (?, ?, ?, ?)
        ");
        $question_insert->bind_param("issi", $quiz_id, $question_text, $question_type, $points);
        $question_insert->execute();
        $question_id = $conn->insert_id;

        // Handle options based on question type
        switch($question_type) {
            case 'multiple_choice':
                // Validate and insert multiple choice options
                if (isset($_POST['options']) && is_array($_POST['options'])) {
                    $option_insert = $conn->prepare("
                        INSERT INTO quiz_options (question_id, option_text, is_correct) 
                        VALUES (?, ?, ?)
                    ");

                    foreach ($_POST['options'] as $index => $option_text) {
                        if (trim($option_text) != '') {
                            $is_correct = isset($_POST['correct_option']) && $_POST['correct_option'] == $index;
                            $option_insert->bind_param("isi", $question_id, $option_text, $is_correct);
                            $option_insert->execute();
                        }
                    }
                }
                break;

            case 'true_false':
                // Insert True/False options
                $correct_answer = $_POST['correct_option'] === 'true';
                $option_insert = $conn->prepare("
                    INSERT INTO quiz_options (question_id, option_text, is_correct) 
                    VALUES (?, 'True', ?), (?, 'False', ?)
                ");
                $option_insert->bind_param("iiii", 
                    $question_id, $correct_answer, 
                    $question_id, !$correct_answer
                );
                $option_insert->execute();
                break;

            case 'essay':
                // No options for essay questions
                break;
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Question added successfully";
        
        // Redirect back to the questions page
        header("Location: questions.php?quiz_id=" . $quiz_id);
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error adding question: " . $e->getMessage();
    }
}

// Fetch existing questions for this quiz
$questions_query = $conn->prepare("
    SELECT q.*, 
    (SELECT COUNT(*) FROM quiz_options WHERE question_id = q.question_id) as options_count
    FROM quiz_questions q 
    WHERE q.quiz_id = ?
    ORDER BY q.question_id
");
$questions_query->bind_param("i", $quiz_id);
$questions_query->execute();
$questions = $questions_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz Questions</title>
    <style>
        .question-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
        }
        .correct-option {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        Manage Questions for: <?php echo htmlspecialchars($quiz['title']); ?>
                        <small class="text-white-50 d-block">
                            Course: <?php echo htmlspecialchars($quiz['course_title']); ?>
                        </small>
                    </h4>
                </div>
                
                <div class="card-body">
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

                    <!-- Add Question Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Question</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="addQuestionForm">
                                <div class="mb-3">
                                    <label for="question_text" class="form-label">Question Text</label>
                                    <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="question_type" class="form-label">Question Type</label>
                                            <select class="form-select" id="question_type" name="question_type" required>
                                                <option value="multiple_choice">Multiple Choice</option>
                                                <option value="true_false">True/False</option>
                                                <option value="essay">Essay</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="points" class="form-label">Points</label>
                                            <input type="number" class="form-control" id="points" name="points" 
                                                   min="1" max="100" value="1" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Container - Dynamic Content -->
                                <div id="optionsContainer" class="mb-3">
                                    <!-- Options will be dynamically added here based on question type -->
                                </div>

                                <button type="submit" class="btn btn-primary">Add Question</button>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Questions List -->
                    <h4>Existing Questions</h4>
                    <?php if ($questions->num_rows > 0): ?>
                        <?php while ($question = $questions->fetch_assoc()): ?>
                            <div class="card question-card">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($question['question_text']); ?></h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Type:</strong> 
                                            <?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Points:</strong> <?php echo $question['points']; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Display Options -->
                                    <?php if ($question['question_type'] != 'essay'): ?>
                                        <div class="mt-3">
                                            <strong>Options:</strong>
                                            <?php 
                                            $options_query = $conn->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
                                            $options_query->bind_param("i", $question['question_id']);
                                            $options_query->execute();
                                            $options = $options_query->get_result();
                                            ?>
                                            <ul class="list-group">
                                                <?php while ($option = $options->fetch_assoc()): ?>
                                                    <li class="list-group-item <?php echo $option['is_correct'] ? 'correct-option' : ''; ?>">
                                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                                        <?php if ($option['is_correct']): ?>
                                                            <span class="badge bg-success float-end">Correct</span>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-warning">Edit</button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No questions added yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('question_type').addEventListener('change', function() {
    const optionsContainer = document.getElementById('optionsContainer');
    const questionType = this.value;

    // Clear previous options
    optionsContainer.innerHTML = '';

    // Dynamically add options based on question type
    switch(questionType) {
        case 'multiple_choice':
            optionsContainer.innerHTML = `
                <label class="form-label">Options</label>
                <div class="options">
                    <!-- Initial 4 options -->
                    ${generateMultipleChoiceOptions(4)}
                </div>
                <button type="button" class="btn btn-outline-secondary mt-2" onclick="addMultipleChoiceOption()">
                    Add Option
                </button>
                <small class="text-muted d-block mt-2">Select the radio button next to the correct answer.</small>
            `;
            break;

        case 'true_false':
            optionsContainer.innerHTML = `
                <label class="form-label">Correct Answer</label>
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="correct_option" value="true" required>
                        <label class="form-check-label">True</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="correct_option" value="false" required>
                        <label class="form-check-label">False</label>
                    </div>
                </div>
            `;
            break;
    }
});

function generateMultipleChoiceOptions(count) {
    let optionsHtml = '';
    for (let i = 0; i < count; i++) {
        optionsHtml += `
            <div class="input-group mb-2">
                <div class="input-group-text">
                    <input type="radio" name="correct_option" value="${i}" required>
                </div>
                <input type="text" class="form-control" name="options[]" placeholder="Option ${i+1}" required>
                <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
    return optionsHtml;
}

function addMultipleChoiceOption() {
    const optionsContainer = document.querySelector('.options');
    const currentOptionsCount = optionsContainer.children.length;
    
    const newOption = document.createElement('div');
    newOption.className = 'input-group mb-2';
    newOption.innerHTML = `
        <div class="input-group-text">
            <input type="radio" name="correct_option" value="${currentOptionsCount}" required>
        </div>
        <input type="text" class="form-control" name="options[]" placeholder="Option ${currentOptionsCount+1}" required>
        <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    optionsContainer.appendChild(newOption);
}

function removeOption(button) {
    const optionsContainer = document.querySelector('.options');
    if (optionsContainer.children.length > 2) {
        button.closest('.input-group').remove();
        
        // Re-index radio button values
        optionsContainer.querySelectorAll('input[type="radio"]').forEach((radio, index) => {
            radio.value = index;
        });
    } else {
        alert('A multiple choice question must have at least 2 options.');
    }
}

// Trigger initial question type change to set up options
document.getElementById('question_type').dispatchEvent(new Event('change'));
</script>

<?php require_once '../../includes/footer.php'; ?>