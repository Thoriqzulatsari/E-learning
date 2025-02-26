<?php
// instructor/quizzes/delete.php
require_once '../../includes/header.php';
requireRole('instructor');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quiz_id'])) {
    $quiz_id = (int)$_POST['quiz_id'];
    $instructor_id = $_SESSION['user_id'];

    // Verify quiz belongs to instructor's course
    $verify_query = "SELECT q.quiz_id 
                    FROM quizzes q
                    JOIN courses c ON q.course_id = c.course_id
                    WHERE q.quiz_id = ? AND c.instructor_id = ?";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $quiz_id, $instructor_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 1) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete quiz attempts
            $stmt = $conn->prepare("DELETE FROM quiz_attempts WHERE quiz_id = ?");
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();

            // Delete quiz options
            $stmt = $conn->prepare("
                DELETE qo FROM quiz_options qo
                INNER JOIN quiz_questions qq ON qo.question_id = qq.question_id
                WHERE qq.quiz_id = ?
            ");
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();

            // Delete quiz questions
            $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();

            // Finally, delete the quiz
            $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
            $stmt->bind_param("i", $quiz_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = "Quiz has been successfully deleted";
            
        } catch (Exception $e) {
            // Roll back if any error occurs
            $conn->rollback();
            $_SESSION['error'] = "Error deleting quiz: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Unauthorized access or quiz not found";
    }
}

// Redirect back
header("Location: index.php");
exit;
?>