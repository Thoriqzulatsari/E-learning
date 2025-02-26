<?php
// student/take_quiz.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get quiz details and verify enrollment
$quiz_query = $conn->prepare("
    SELECT q.*, c.title as course_title, c.course_id 
    FROM quizzes q
    JOIN courses c ON q.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE q.quiz_id = ? AND e.user_id = ?
");
$quiz_query->bind_param("ii", $quiz_id, $student_id);
$quiz_query->execute();
$quiz_result = $quiz_query->get_result();

// Check if quiz exists and user is enrolled
if ($quiz_result->num_rows === 0) {
    $_SESSION['error'] = "Kuis ga ditemuin atau akses ga diizinin.";
    header("Location: quizzes.php");
    exit;
}

$quiz = $quiz_result->fetch_assoc();

// Check if quiz is already passed
$check_attempts = $conn->prepare("
    SELECT * FROM quiz_attempts 
    WHERE quiz_id = ? AND user_id = ? 
    ORDER BY attempt_id DESC LIMIT 1
");
$check_attempts->bind_param("ii", $quiz_id, $student_id);
$check_attempts->execute();
$attempts_result = $check_attempts->get_result();
$last_attempt = $attempts_result->num_rows > 0 ? $attempts_result->fetch_assoc() : null;

$is_review = false;
if ($last_attempt && $last_attempt['score'] >= $quiz['passing_score']) {
    $is_review = true;
}

// Get questions
$questions_query = $conn->prepare("
    SELECT * FROM quiz_questions 
    WHERE quiz_id = ?
    ORDER BY question_id
");
$questions_query->bind_param("i", $quiz_id);
$questions_query->execute();
$questions_result = $questions_query->get_result();

$questions = [];
while ($question = $questions_result->fetch_assoc()) {
    if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'true_false') {
        $options_query = $conn->prepare("
            SELECT * FROM quiz_options 
            WHERE question_id = ?
            ORDER BY option_id
        ");
        $options_query->bind_param("i", $question['question_id']);
        $options_query->execute();
        $options_result = $options_query->get_result();
        
        $options = [];
        while ($option = $options_result->fetch_assoc()) {
            $options[] = $option;
        }
        $question['options'] = $options;
    }
    $questions[] = $question;
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    ob_start(); // Start output buffering

    $score = 0;
    $total_points = 0;
    $answers = [];
    $detailed_results = [];

    $started_at = $_POST['started_at'];
    $time_spent = (time() - strtotime($started_at)) / 60; // in minutes

    // Cek time limit
    if ($quiz['time_limit'] > 0 && $time_spent > $quiz['time_limit']) {
        $_SESSION['error'] = "Waktu habis. Hasil kuis ga direkam.";
        header("Location: quizzes.php");
        ob_end_clean();
        exit;
    }

    foreach ($questions as $question) {
        $question_id = $question['question_id'];
        $total_points += $question['points'];

        if (isset($_POST['answers'][$question_id])) {
            $user_answer = $_POST['answers'][$question_id];
            $answers[$question_id] = $user_answer;

            if ($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'true_false') {
                $is_correct = false;
                $correct_answer_text = "";

                foreach ($question['options'] as $option) {
                    if ($option['is_correct']) {
                        $correct_answer_text = $option['option_text'];
                    }
                    if ($option['option_id'] == $user_answer && $option['is_correct']) {
                        $is_correct = true;
                        $score += $question['points'];
                    }
                }

                $selected_text = "";
                foreach ($question['options'] as $option) {
                    if ($option['option_id'] == $user_answer) {
                        $selected_text = $option['option_text'];
                        break;
                    }
                }

                $detailed_results[$question_id] = [
                    'question_text' => $question['question_text'],
                    'is_correct' => $is_correct,
                    'points' => $question['points'],
                    'points_earned' => $is_correct ? $question['points'] : 0,
                    'user_answer' => $selected_text,
                    'correct_answer' => $correct_answer_text
                ];
            } else if ($question['question_type'] == 'essay') {
                $detailed_results[$question_id] = [
                    'question_text' => $question['question_text'],
                    'is_correct' => null,
                    'points' => $question['points'],
                    'points_earned' => 0,
                    'user_answer' => $user_answer,
                    'correct_answer' => null
                ];
            }
        } else {
            $answers[$question_id] = null;
            $detailed_results[$question_id] = [
                'question_text' => $question['question_text'],
                'is_correct' => false,
                'points' => $question['points'],
                'points_earned' => 0,
                'user_answer' => null,
                'correct_answer' => $question['question_type'] == 'essay' ? null : 'Not answered'
            ];
        }
    }

    $percentage_score = ($total_points > 0) ? ($score / $total_points) * 100 : 0;
    $answers_json = json_encode($answers);
    $details_json = json_encode($detailed_results);

    // Record attempt
    $record_attempt = $conn->prepare("
        INSERT INTO quiz_attempts (
            user_id, quiz_id, score, answers, details, time_spent, started_at, completed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if ($record_attempt === false) {
        error_log("Prepare statement gagal: " . $conn->error);
        $_SESSION['error'] = "Error database: " . $conn->error;
        header("Location: quizzes.php");
        ob_end_clean();
        exit;
    }

    $record_attempt->bind_param("iidssds", $student_id, $quiz_id, $percentage_score, $answers_json, $details_json, $time_spent, $started_at);

    if (!$record_attempt->execute()) {
        error_log("Execute gagal: " . $record_attempt->error);
        $_SESSION['error'] = "Gagal nyimpan attempt: " . $record_attempt->error;
        header("Location: quizzes.php");
        ob_end_clean();
        exit;
    }

    $attempt_id = $conn->insert_id;

    // Update progress
    try {
        $update_progress = $conn->prepare("
            UPDATE enrollments 
            SET progress = (
                SELECT AVG(CASE WHEN qa.score >= q.passing_score THEN 100 ELSE qa.score END)
                FROM quizzes q 
                LEFT JOIN quiz_attempts qa ON q.quiz_id = qa.quiz_id AND qa.user_id = ?
                WHERE q.course_id = ?
            )
            WHERE user_id = ? AND course_id = ?
        ");

        if ($update_progress) {
            $update_progress->bind_param("iiii", $student_id, $quiz['course_id'], $student_id, $quiz['course_id']);
            $update_progress->execute();
        } else {
            error_log("Gagal prepare update_progress: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Exception update progress: " . $e->getMessage());
    }

    ob_end_clean(); // Bersihin output buffer
    $_SESSION['success'] = "Kuis berhasil disubmit!";
    header("Location: quiz_result.php?attempt_id=" . $attempt_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title'] ?? 'Take Quiz'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .quiz-timer { position: sticky; top: 70px; z-index: 1000; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: space-between; }
        .quiz-timer i { font-size: 24px; margin-right: 10px; }
        .time-remaining { font-size: 20px; font-weight: bold; }
        .progress-indicator { display: flex; flex-wrap: wrap; margin-bottom: 20px; gap: 10px; }
        .question-indicator { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #f0f0f0; cursor: pointer; transition: all 0.3s ease; }
        .question-indicator.answered { background-color: #4a90e2; color: white; }
        .question-indicator.current { border: 2px solid #4a90e2; font-weight: bold; }
        .question-card { display: none; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; background-color: white; position: relative; }
        .question-card.active { display: block; }
        .option-card { display: block; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 10px; cursor: pointer; transition: all 0.2s ease; }
        .option-card:hover { border-color: #4a90e2; transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .option-input:checked + .option-card { background-color: #e3f2fd; border-color: #4a90e2; }
        .option-input { display: none; }
        .quiz-navigation { display: flex; justify-content: space-between; margin-top: 20px; }
        .quiz-navigation button { padding: 10px 20px; border-radius: 50px; font-weight: bold; }
        .quiz-submit-section { background-color: #f8f9fa; border-radius: 10px; padding: 20px; margin-top: 30px; text-align: center; }
        .quiz-question-number { position: absolute; top: -15px; left: 20px; background-color: #4a90e2; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        @media (max-width: 768px) { .question-indicator { width: 35px; height: 35px; font-size: 14px; } .quiz-navigation button { padding: 8px 15px; font-size: 14px; } }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($quiz['title'] ?? 'Take Quiz'); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Kursus:</strong> <?php echo htmlspecialchars($quiz['course_title'] ?? 'N/A'); ?></p>
                            <p><strong>Batas Waktu:</strong> <?php echo isset($quiz['time_limit']) ? $quiz['time_limit'] : '0'; ?> menit</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Jumlah Soal:</strong> <?php echo count($questions); ?></p>
                            <p><strong>Skor Lulus:</strong> <?php echo isset($quiz['passing_score']) ? $quiz['passing_score'] : '0'; ?>%</p>
                        </div>
                    </div>
                    <?php if ($is_review): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Mode Review: Kamu udah lulus kuis ini.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Jangan refresh atau keluar dari halaman ini selama kuis.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$is_review): ?>
                <div class="quiz-timer">
                    <div><i class="fas fa-clock"></i><span>Sisa Waktu</span></div>
                    <div id="timer" class="time-remaining">--:--</div>
                </div>
                
                <div class="progress-indicator" id="progressIndicator">
                    <?php for ($i = 0; $i < count($questions); $i++): ?>
                        <div class="question-indicator<?php echo $i === 0 ? ' current' : ''; ?>" 
                             data-question="<?php echo $i; ?>" onclick="navigateToQuestion(<?php echo $i; ?>)">
                            <?php echo $i + 1; ?>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <form method="POST" id="quizForm">
                    <input type="hidden" name="submit_quiz" value="1">
                    <input type="hidden" name="started_at" value="<?php echo date('Y-m-d H:i:s'); ?>">
                    
                    <?php foreach ($questions as $index => $question): ?>
                        <div id="question<?php echo $index; ?>" class="question-card<?php echo $index === 0 ? ' active' : ''; ?>">
                            <div class="quiz-question-number">Soal <?php echo $index + 1; ?></div>
                            <h5 class="mt-4 mb-3"><?php echo htmlspecialchars($question['question_text']); ?></h5>
                            <div class="question-points text-end mb-3">
                                <span class="badge bg-secondary"><?php echo $question['points']; ?> poin</span>
                            </div>
                            
                            <?php if ($question['question_type'] == 'multiple_choice'): ?>
                                <div class="options-container">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <div class="mb-3">
                                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" 
                                                   value="<?php echo $option['option_id']; ?>" 
                                                   id="option<?php echo $option['option_id']; ?>" 
                                                   class="option-input"
                                                   onchange="markAnswered(<?php echo $index; ?>)">
                                            <label for="option<?php echo $option['option_id']; ?>" class="option-card">
                                                <?php echo htmlspecialchars($option['option_text']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($question['question_type'] == 'true_false'): ?>
                                <div class="options-container">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <div class="mb-3">
                                            <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" 
                                                   value="<?php echo $option['option_id']; ?>" 
                                                   id="option<?php echo $option['option_id']; ?>" 
                                                   class="option-input"
                                                   onchange="markAnswered(<?php echo $index; ?>)">
                                            <label for="option<?php echo $option['option_id']; ?>" class="option-card">
                                                <?php echo htmlspecialchars($option['option_text']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif ($question['question_type'] == 'essay'): ?>
                                <div class="mb-3">
                                    <textarea class="form-control" name="answers[<?php echo $question['question_id']; ?>]" 
                                              rows="5" placeholder="Ketik jawabanmu di sini..."
                                              onkeyup="markAnswered(<?php echo $index; ?>)"></textarea>
                                </div>
                            <?php endif; ?>
                            
                            <div class="quiz-navigation">
                                <button type="button" class="btn btn-outline-secondary" 
                                        <?php echo $index === 0 ? 'disabled' : ''; ?>
                                        onclick="navigateToQuestion(<?php echo $index - 1; ?>)">
                                    <i class="fas fa-arrow-left"></i> Sebelumnya
                                </button>
                                <?php if ($index === count($questions) - 1): ?>
                                    <button type="button" class="btn btn-success" onclick="showSubmitModal()">
                                        <i class="fas fa-check-circle"></i> Selesai Kuis
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary" onclick="navigateToQuestion(<?php echo $index + 1; ?>)">
                                        Selanjutnya <i class="fas fa-arrow-right"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="quiz-submit-section">
                        <p>Pastikan semua soal terjawab sebelum submit.</p>
                        <p><span id="answeredCounter">0</span> dari <?php echo count($questions); ?> soal terjawab</p>
                        <button type="button" class="btn btn-lg btn-success" onclick="showSubmitModal()">
                            <i class="fas fa-paper-plane"></i> Submit Kuis
                        </button>
                    </div>
                    
                    <div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">Submit Kuis</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Yakin mau submit kuis?</p>
                                    <div id="unansweredWarning" class="alert alert-warning" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i> Ada soal yang belum dijawab.
                                    </div>
                                    <p>Setelah submit, jawaban ga bisa diubah.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lanjut Kuis</button>
                                    <button type="submit" class="btn btn-primary" id="finalSubmitBtn">
                                        <i class="fas fa-paper-plane"></i> Submit Kuis
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Kamu udah lulus kuis ini</h5>
                    <p>Skormu: <?php echo number_format($last_attempt['score'], 1); ?>%</p>
                    <a href="quiz_result.php?attempt_id=<?php echo $last_attempt['attempt_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-chart-bar"></i> Lihat Hasil Detail
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const timeLimit = <?php echo isset($quiz['time_limit']) ? $quiz['time_limit'] : 0; ?> * 60;
let timeLeft = timeLimit;
let timerInterval;

function startTimer() {
    if (timeLimit <= 0) {
        document.getElementById('timer').textContent = "Ga ada batas waktu";
        return;
    }
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer();
}

function updateTimer() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    document.getElementById('timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (timeLeft <= 300) document.getElementById('timer').style.color = 'orange';
    if (timeLeft <= 60) document.getElementById('timer').style.color = 'red';
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        document.getElementById('timer').textContent = "Waktu habis!";
        setTimeout(() => {
            window.removeEventListener('beforeunload', beforeUnloadHandler);
            document.getElementById('quizForm').submit();
        }, 500);
    } else {
        timeLeft--;
    }
}

function navigateToQuestion(index) {
    document.querySelectorAll('.question-card').forEach(card => card.classList.remove('active'));
    document.getElementById(`question${index}`).classList.add('active');
    document.querySelectorAll('.question-indicator').forEach(ind => ind.classList.remove('current'));
    document.querySelector(`.question-indicator[data-question="${index}"]`).classList.add('current');
    window.scrollTo(0, 0);
}

function markAnswered(index) {
    document.querySelector(`.question-indicator[data-question="${index}"]`).classList.add('answered');
    updateAnsweredCounter();
}

function updateAnsweredCounter() {
    const answered = document.querySelectorAll('.question-indicator.answered').length;
    document.getElementById('answeredCounter').textContent = answered;
}

const beforeUnloadHandler = function(e) {
    e.preventDefault();
    e.returnValue = '';
};
window.addEventListener('beforeunload', beforeUnloadHandler);

function showSubmitModal() {
    const answered = document.querySelectorAll('.question-indicator.answered').length;
    const total = <?php echo count($questions); ?>;
    document.getElementById('unansweredWarning').style.display = answered < total ? 'block' : 'none';
    
    const submitModal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
    submitModal.show();
}

function saveAnswerToLocalStorage(questionId, answer) {
    let quizAnswers = JSON.parse(localStorage.getItem('quizAnswers') || '{}');
    quizAnswers[questionId] = answer;
    localStorage.setItem('quizAnswers', JSON.stringify(quizAnswers));
}

function loadAnswersFromLocalStorage() {
    const quizAnswers = JSON.parse(localStorage.getItem('quizAnswers') || '{}');
    Object.keys(quizAnswers).forEach(questionId => {
        const input = document.querySelector(`input[name="answers[${questionId}]"][value="${quizAnswers[questionId]}"]`);
        if (input) {
            input.checked = true;
            const questionCard = input.closest('.question-card');
            if (questionCard) {
                const index = parseInt(questionCard.id.replace('question', ''));
                markAnswered(index);
            }
        } else {
            const textarea = document.querySelector(`textarea[name="answers[${questionId}]"]`);
            if (textarea) {
                textarea.value = quizAnswers[questionId];
                const questionCard = textarea.closest('.question-card');
                if (questionCard) {
                    const index = parseInt(questionCard.id.replace('question', ''));
                    markAnswered(index);
                }
            }
        }
    });
}

document.querySelectorAll('.option-input').forEach(input => {
    input.addEventListener('change', function() {
        const questionId = this.name.match(/\d+/)[0];
        saveAnswerToLocalStorage(questionId, this.value);
    });
});

document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        const questionId = this.name.match(/\d+/)[0];
        saveAnswerToLocalStorage(questionId, this.value);
    });
});

document.getElementById('finalSubmitBtn').addEventListener('click', function(e) {
    e.preventDefault();
    window.removeEventListener('beforeunload', beforeUnloadHandler);
    localStorage.removeItem('quizAnswers');
    document.getElementById('quizForm').submit();
});

window.onload = function() {
    if (!<?php echo $is_review ? 'true' : 'false'; ?>) {
        startTimer();
    }
    loadAnswersFromLocalStorage();
    updateAnsweredCounter();
    console.log("Quiz interface initialized");
};
</script>
</body>
</html>