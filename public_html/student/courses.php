<?php
// student/courses.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll'])) {
    $course_id = (int)$_POST['course_id'];
    
    // Check if already enrolled
    $check = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    
    if ($check->get_result()->num_rows == 0) {
        // Not enrolled yet, proceed with enrollment
        $enroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
        $enroll->bind_param("ii", $student_id, $course_id);
        
        if ($enroll->execute()) {
            $_SESSION['success'] = "Successfully enrolled in the course!";
        } else {
            $_SESSION['error'] = "Error enrolling in the course.";
        }
    } else {
        $_SESSION['error'] = "You are already enrolled in this course.";
    }
    
    // FIX: Improved redirect handling
    // Make sure we have no output before redirecting
    if (!headers_sent()) {
        // Redirect back to courses.php after processing
        header("Location: courses.php");
        exit();
    } else {
        // Fallback if headers already sent
        echo '<script>window.location.href = "courses.php";</script>';
        exit();
    }
}

// Get enrolled courses
$enrolled_query = "SELECT 
    c.*, 
    u.username as instructor_name,
    e.progress,
    e.enrolled_at,
    (SELECT COUNT(*) FROM materials WHERE course_id = c.course_id) as total_materials,
    (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as total_quizzes
    FROM courses c
    JOIN enrollments e ON c.course_id = e.course_id
    JOIN users u ON c.instructor_id = u.user_id
    WHERE e.user_id = ? AND c.status = 'published'
    ORDER BY e.enrolled_at DESC";

$stmt = $conn->prepare($enrolled_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_courses = $stmt->get_result();

// Get available courses (not enrolled)
$available_query = "SELECT 
    c.*, 
    u.username as instructor_name,
    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_students,
    (SELECT COUNT(*) FROM materials WHERE course_id = c.course_id) as total_materials,
    (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as total_quizzes
    FROM courses c
    JOIN users u ON c.instructor_id = u.user_id
    WHERE c.status = 'published' 
    AND c.course_id NOT IN (SELECT course_id FROM enrollments WHERE user_id = ?)
    ORDER BY c.created_at DESC";

$stmt = $conn->prepare($available_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$available_courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container-fluid py-4">
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

    <!-- My Enrolled Courses -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">My Enrolled Courses</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($enrolled_courses->num_rows > 0): ?>
                            <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="../uploads/thumbnails/<?php echo $course['thumbnail']; ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                            
                                            <div class="mb-3">
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $course['progress']; ?>%"
                                                         aria-valuenow="<?php echo $course['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $course['progress']; ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-book"></i> <?php echo $course['total_materials']; ?> Materials
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-question-circle"></i> <?php echo $course['total_quizzes']; ?> Quizzes
                                                </small>
                                            </div>
                                            
                                            <a href="view_course.php?id=<?php echo $course['course_id']; ?>" 
                                               class="btn btn-primary w-100">Continue Learning</a>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">
                                                Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center">You haven't enrolled in any courses yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Courses -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="card-title mb-0">Available Courses</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($available_courses->num_rows > 0): ?>
                            <?php while ($course = $available_courses->fetch_assoc()): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="../uploads/thumbnails/<?php echo $course['thumbnail']; ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                            
                                            <div class="d-flex justify-content-between mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-users"></i> <?php echo $course['enrolled_students']; ?> Students
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-book"></i> <?php echo $course['total_materials']; ?> Materials
                                                </small>
                                            </div>
                                            
                                            <form method="POST" action="">
                                                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                <button type="submit" name="enroll" class="btn btn-success w-100">
                                                    Enroll Now
                                                </button>
                                            </form>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">
                                                Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center">No courses available at the moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>