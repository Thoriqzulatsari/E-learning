<?php
// instructor/courses/delete_course.php
require_once '../../includes/header.php';
requireRole('instructor');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $instructor_id = $_SESSION['user_id'];

    // Verify course belongs to instructor
    $verify_query = "SELECT course_id, thumbnail FROM courses WHERE course_id = ? AND instructor_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $course_id, $instructor_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if ($course) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Delete course materials
            $materials_query = "SELECT file_path FROM materials WHERE course_id = ?";
            $stmt = $conn->prepare($materials_query);
            $stmt->bind_param("i", $course_id);
            $stmt->execute();
            $materials = $stmt->get_result();

            // Delete material files
            while ($material = $materials->fetch_assoc()) {
                if ($material['file_path'] && file_exists('../../uploads/materials/' . $material['file_path'])) {
                    unlink('../../uploads/materials/' . $material['file_path']);
                }
            }

            // Delete course thumbnail if exists
            if ($course['thumbnail'] && file_exists('../../uploads/thumbnails/' . $course['thumbnail'])) {
                unlink('../../uploads/thumbnails/' . $course['thumbnail']);
            }

            // Delete quiz options and questions
            $conn->query("DELETE qo FROM quiz_options qo 
                         INNER JOIN quiz_questions qq ON qo.question_id = qq.question_id 
                         INNER JOIN quizzes q ON qq.quiz_id = q.quiz_id 
                         WHERE q.course_id = $course_id");

            // Delete quiz questions
            $conn->query("DELETE qq FROM quiz_questions qq 
                         INNER JOIN quizzes q ON qq.quiz_id = q.quiz_id 
                         WHERE q.course_id = $course_id");

            // Delete quiz attempts
            $conn->query("DELETE qa FROM quiz_attempts qa 
                         INNER JOIN quizzes q ON qa.quiz_id = q.quiz_id 
                         WHERE q.course_id = $course_id");

            // Delete quizzes
            $conn->query("DELETE FROM quizzes WHERE course_id = $course_id");

            // Delete materials
            $conn->query("DELETE FROM materials WHERE course_id = $course_id");

            // Delete enrollments
            $conn->query("DELETE FROM enrollments WHERE course_id = $course_id");

            // Finally, delete the course
            $conn->query("DELETE FROM courses WHERE course_id = $course_id");

            // Commit transaction
            $conn->commit();

            $_SESSION['success'] = "Course has been successfully deleted";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Error deleting course: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Unauthorized access or course not found";
    }

    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>