<?php
// student/mark_completed.php
require_once '../includes/header.php';
requireRole('student');

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['material_id']) || !isset($data['course_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$material_id = (int)$data['material_id'];
$course_id = (int)$data['course_id'];

// Mark material as completed
$stmt = $conn->prepare("
    INSERT INTO material_progress (user_id, course_id, material_id, completed) 
    VALUES (?, ?, ?, 1) 
    ON DUPLICATE KEY UPDATE completed = 1
");
$stmt->bind_param("iii", $student_id, $course_id, $material_id);

if ($stmt->execute()) {
    // Recalculate course progress
    $progress_query = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM materials WHERE course_id = ?) as total_materials,
            (SELECT COUNT(*) FROM material_progress WHERE user_id = ? AND course_id = ? AND completed = 1) as completed_materials
    ");
    $progress_query->bind_param("iii", $course_id, $student_id, $course_id);
    $progress_query->execute();
    $progress = $progress_query->get_result()->fetch_assoc();
    
    $course_progress = $progress['total_materials'] > 0 
        ? round(($progress['completed_materials'] / $progress['total_materials']) * 100, 2) 
        : 0;

    // Update enrollment progress
    $update_enrollment = $conn->prepare("
        UPDATE enrollments 
        SET progress = ? 
        WHERE user_id = ? AND course_id = ?
    ");
    $update_enrollment->bind_param("dii", $course_progress, $student_id, $course_id);
    $update_enrollment->execute();

    echo json_encode([
        'status' => 'success', 
        'progress' => $course_progress
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark material as completed']);
}
exit;