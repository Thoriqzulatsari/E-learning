<?php
// student/quiz_result.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];
$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;

// Get quiz attempt details
$attempt_query = $conn->prepare("
    SELECT qa.*, 
           q.title as quiz_title, 
           q.passing_score,
           c.title as course_title,
           c.course_id
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.quiz_id
    JOIN courses c ON q.course_id = c.course_id
    WHERE qa.attempt_id = ? AND qa.user_id = ?
");
$attempt_query->bind_param("ii", $attempt_id, $student_id);
$attempt_query->execute();
$attempt_result = $attempt_query->get_result();

if ($attempt_result->num_rows === 0) {
    $_SESSION['error'] = "Hasil kuis ga ditemuin atau akses ga diizinin.";
    header("Location: quizzes.php");
    exit;
}

$attempt = $attempt_result->fetch_assoc();

// Parse detailed results
$details = json_decode($attempt['details'], true) ?: [];

// Calculate statistics
$total_questions = count($details);
$answered_questions = 0;
$correct_answers = 0;
$total_points = 0;
$earned_points = 0;

foreach ($details as $question_id => $detail) {
    $total_points += $detail['points'];
    $earned_points += $detail['points_earned'];
    
    if ($detail['user_answer'] !== null) {
        $answered_questions++;
    }
    
    if ($detail['is_correct'] === true) {
        $correct_answers++;
    }
}

// Check if passed
$passed = $attempt['score'] >= $attempt['passing_score'];

// Get badges/achievements
$completion_status = "";
$badge_icon = "";
$badge_color = "";

if ($passed) {
    if ($attempt['score'] >= 95) {
        $completion_status = "Excellence";
        $badge_icon = "trophy";
        $badge_color = "warning"; // Gold
    } elseif ($attempt['score'] >= 85) {
        $completion_status = "Distinction";
        $badge_icon = "award";
        $badge_color = "info"; // Silver
    } else {
        $completion_status = "Passed";
        $badge_icon = "check-circle";
        $badge_color = "success"; // Green
    }
} else {
    $completion_status = "Failed";
    $badge_icon = "times-circle";
    $badge_color = "danger"; // Red
}

// Check if this is the best score
$best_score_query = $conn->prepare("
    SELECT MAX(score) as best_score 
    FROM quiz_attempts 
    WHERE quiz_id = ? AND user_id = ?
");
$best_score_query->bind_param("ii", $attempt['quiz_id'], $student_id);
$best_score_query->execute();
$best_score = $best_score_query->get_result()->fetch_assoc()['best_score'];

$is_best_score = $attempt['score'] >= $best_score;

// Get attempt count
$attempt_count_query = $conn->prepare("
    SELECT COUNT(*) as attempt_count 
    FROM quiz_attempts 
    WHERE quiz_id = ? AND user_id = ?
");
$attempt_count_query->bind_param("ii", $attempt['quiz_id'], $student_id);
$attempt_count_query->execute();
$attempt_count = $attempt_count_query->get_result()->fetch_assoc()['attempt_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuis - <?php echo htmlspecialchars($attempt['quiz_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .result-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .result-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='rgba(255,255,255,0.1)' fill-opacity='1' d='M0,160L48,176C96,192,192,224,288,229.3C384,235,480,213,576,186.7C672,160,768,128,864,133.3C960,139,1056,181,1152,208C1248,235,1344,245,1392,250.7L1440,256L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-position: bottom;
            background-repeat: no-repeat;
            z-index: 1;
            opacity: 0.7;
        }
        
        .result-header-content {
            position: relative;
            z-index: 2;
        }
        
        .result-badge {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .result-badge i {
            font-size: 60px;
        }
        
        .quiz-score {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .badge-excellence { color: #ffc107; }
        .badge-distinction { color: #17a2b8; }
        .badge-passed { color: #28a745; }
        .badge-failed { color: #dc3545; }
        
        .stats-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            margin-bottom: 20px;
        }
        
        .stats-card:hover { transform: translateY(-5px); }
        
        .stats-icon { font-size: 32px; margin-bottom: 10px; }
        .stats-value { font-size: 28px; font-weight: bold; }
        
        .question-card {
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .question-header { padding: 15px; border-bottom: 1px solid rgba(0,0,0,0.1); }
        
        .answer-status {
            padding: 10px 15px;
            font-weight: bold;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .correct-answer { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
        .incorrect-answer { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .pending-answer { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        
        .user-answer {
            border-left: 3px solid #4a90e2;
            padding: 10px 15px;
            background-color: rgba(74, 144, 226, 0.05);
            margin-bottom: 10px;
        }
        
        .correct-solution {
            border-left: 3px solid #28a745;
            padding: 10px 15px;
            background-color: rgba(40, 167, 69, 0.05);
        }
        
        .actions-container { margin-top: 30px; text-align: center; }
        .action-button { border-radius: 50px; padding: 12px 25px; font-weight: bold; margin: 5px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .progress { height: 25px; border-radius: 15px; background-color: rgba(255,255,255,0.2); margin: 20px 0; }
        .progress-bar { border-radius: 15px; }
        
        .time-info { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
        .time-item { background-color: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 20px; }
        
        @media (max-width: 768px) {
            .result-badge { width: 90px; height: 90px; }
            .result-badge i { font-size: 45px; }
            .quiz-score { font-size: 36px; }
            .stats-value { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Result Header -->
    <div class="result-header">
        <div class="result-header-content text-center">
            <div class="result-badge">
                <i class="fas fa-<?php echo $badge_icon; ?> text-<?php echo $badge_color; ?>"></i>
            </div>
            <h1 class="quiz-score"><?php echo number_format($attempt['score'], 2); ?>%</h1>
            <h3><?php echo $completion_status; ?></h3>
            <p class="mb-0">Kuis: <?php echo htmlspecialchars($attempt['quiz_title']); ?></p>
            <p>Kursus: <?php echo htmlspecialchars($attempt['course_title']); ?></p>
            <div class="progress">
                <div class="progress-bar bg-<?php echo $badge_color; ?>" 
                     role="progressbar" 
                     style="width: <?php echo $attempt['score']; ?>%" 
                     aria-valuenow="<?php echo $attempt['score']; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <div class="time-info">
                <span class="time-item"><i class="fas fa-clock me-2"></i><?php echo number_format($attempt['time_spent'], 2); ?> menit</span>
                <span class="time-item"><i class="fas fa-calendar-alt me-2"></i><?php echo date('d M Y H:i', strtotime($attempt['completed_at'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stats-card bg-light p-4 text-center">
                <i class="fas fa-question-circle text-primary stats-icon"></i>
                <div class="stats-value"><?php echo $total_questions; ?></div>
                <p>Total Soal</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card bg-light p-4 text-center">
                <i class="fas fa-check-circle text-success stats-icon"></i>
                <div class="stats-value"><?php echo $correct_answers; ?></div>
                <p>Jawaban Benar</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card bg-light p-4 text-center">
                <i class="fas fa-pen-alt text-info stats-icon"></i>
                <div class="stats-value"><?php echo $answered_questions; ?></div>
                <p>Soal Dijawab</p>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card bg-light p-4 text-center">
                <i class="fas fa-star text-warning stats-icon"></i>
                <div class="stats-value"><?php echo $earned_points; ?>/<?php echo $total_points; ?></div>
                <p>Poin Didapat</p>
            </div>
        </div>
    </div>

    <!-- Detailed Results -->
    <h2 class="mt-5 mb-4">Hasil Detail</h2>
    <?php foreach ($details as $question_id => $detail): ?>
        <div class="question-card">
            <div class="question-header bg-light">
                <h5><?php echo htmlspecialchars($detail['question_text']); ?></h5>
                <small>Poin: <?php echo $detail['points_earned']; ?>/<?php echo $detail['points']; ?></small>
            </div>
            <div class="card-body">
                <?php if ($detail['is_correct'] === true): ?>
                    <span class="answer-status correct-answer">Benar</span>
                <?php elseif ($detail['is_correct'] === false): ?>
                    <span class="answer-status incorrect-answer">Salah</span>
                <?php else: ?>
                    <span class="answer-status pending-answer">Menunggu Penilaian</span>
                <?php endif; ?>
                
                <div class="user-answer">
                    <strong>Jawabanmu:</strong> 
                    <?php echo htmlspecialchars($detail['user_answer'] ?? 'Ga dijawab'); ?>
                </div>
                
                <?php if ($detail['correct_answer'] !== null): ?>
                    <div class="correct-solution">
                        <strong>Jawaban Benar:</strong> 
                        <?php echo htmlspecialchars($detail['correct_answer']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Actions -->
    <div class="actions-container">
        <?php if ($passed): ?>
            <a href="submit_quiz.php?attempt_id=<?php echo $attempt['attempt_id']; ?>" 
               class="btn btn-success action-button">
                <i class="fas fa-certificate"></i> Generate Sertifikat
            </a>
        <?php endif; ?>
        <?php if (!$passed && $attempt_count < 3): // Assuming max 3 attempts ?>
            <a href="take_quiz.php?id=<?php echo $attempt['quiz_id']; ?>" 
               class="btn btn-primary action-button">
                <i class="fas fa-redo"></i> Ulangi Kuis
            </a>
        <?php endif; ?>
        <a href="quizzes.php" class="btn btn-secondary action-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Kuis
        </a>
        <a href="course.php?id=<?php echo $attempt['course_id']; ?>" 
           class="btn btn-info action-button">
            <i class="fas fa-book"></i> Kembali ke Kursus
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>