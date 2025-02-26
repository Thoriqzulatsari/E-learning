<?php
// student/submit_quiz.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireLogin();
requireRole('student');

try {
    ini_set('display_errors', 1); // Sementara buat debug
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $user_id = $_SESSION['user_id'];
    $attempt_id = filter_var($_GET['attempt_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!$attempt_id) {
        error_log("ID attempt ga valid: $attempt_id");
        throw new Exception('ID attempt ga valid.');
    }

    error_log("Memproses attempt_id: $attempt_id untuk user_id: $user_id");

    // Ambil data dari quiz_attempts pake $GLOBALS['pdo']
    $stmt = $GLOBALS['pdo']->prepare("
        SELECT qa.score, qa.quiz_id, q.passing_score, q.course_id
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        WHERE qa.attempt_id = ? AND qa.user_id = ?
    ");
    $stmt->execute([$attempt_id, $user_id]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        error_log("Attempt ga ditemuin untuk attempt_id: $attempt_id dan user_id: $user_id");
        throw new Exception('Attempt ga ditemuin.');
    }

    error_log("Data attempt ditemuin: score=" . $attempt['score'] . ", quiz_id=" . $attempt['quiz_id']);

    if ($attempt['score'] >= $attempt['passing_score']) {
        // Cek apakah sertifikat udah ada
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT certificate_id FROM certificates 
            WHERE user_id = ? AND quiz_id = ?
        ");
        $stmt->execute([$user_id, $attempt['quiz_id']]);
        $existing_cert = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_cert) {
            error_log("Sertifikat sudah ada untuk quiz_id: " . $attempt['quiz_id']);
            header("Location: ../generate_certificate.php?id=" . $existing_cert['certificate_id']);
            exit;
        }

        // Bikin sertifikat baru
        $certificate_number = uniqid('CERT-');
        $issued_date = date('Y-m-d');
        $course_id = $attempt['course_id'];
        $quiz_id = $attempt['quiz_id'];

        error_log("Membuat sertifikat baru: certificate_number=$certificate_number, course_id=$course_id, quiz_id=$quiz_id");

        $stmt = $GLOBALS['pdo']->prepare("
            INSERT INTO certificates (user_id, course_id, quiz_id, certificate_number, issued_date, status, title, created_by)
            VALUES (?, ?, ?, ?, ?, 'Active', ?, ?)
        ");
        $stmt->execute([$user_id, $course_id, $quiz_id, $certificate_number, $issued_date, "Completion of Course {$course_id}", $user_id]);
        
        $certificate_id = $GLOBALS['pdo']->lastInsertId();
        
        error_log("Sertifikat dibuat: certificate_id=$certificate_id");
        
        header("Location: ../generate_certificate.php?id=$certificate_id");
        exit;
    } else {
        error_log("Skor ga cukup untuk generate sertifikat: score=" . $attempt['score'] . ", passing_score=" . $attempt['passing_score']);
        header("Location: quiz_result.php?attempt_id=$attempt_id&status=failed");
        exit;
    }

} catch (Exception $e) {
    error_log("Error submit kuis: " . $e->getMessage());
    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>