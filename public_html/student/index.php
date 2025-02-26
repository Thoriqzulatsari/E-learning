<?php
// student/index.php - VERSI SEDERHANA TAPI KOMPATIBEL
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

// Get enrolled courses with progress
$enrolled_courses = $conn->prepare("
    SELECT 
        c.course_id,
        c.title,
        c.description,
        u.username as instructor_name,
        e.progress,
        e.enrolled_at,
        (SELECT COUNT(*) FROM materials WHERE course_id = c.course_id) as total_materials,
        (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as total_quizzes
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    JOIN users u ON c.instructor_id = u.user_id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
");
$enrolled_courses->bind_param("i", $student_id);
$enrolled_courses->execute();
$courses_result = $enrolled_courses->get_result();

// Get recent quiz attempts
$recent_quizzes = $conn->prepare("
    SELECT 
        q.quiz_id,
        q.title as quiz_title,
        c.title as course_title,
        qa.score,
        qa.completed_at
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.quiz_id
    JOIN courses c ON q.course_id = c.course_id
    WHERE qa.user_id = ?
    ORDER BY qa.completed_at DESC
    LIMIT 5
");
$recent_quizzes->bind_param("i", $student_id);
$recent_quizzes->execute();
$quizzes_result = $recent_quizzes->get_result();

// Get certificates earned
$certificates = $conn->prepare("
    SELECT 
        cert.certificate_id,
        cert.certificate_number,
        c.title as course_title,
        cert.issued_date
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.course_id
    WHERE cert.user_id = ?
    ORDER BY cert.issued_date DESC
");
$certificates->bind_param("i", $student_id);
$certificates->execute();
$certificates_result = $certificates->get_result();

// Additional stats
$total_courses = $courses_result->num_rows;
$total_quizzes_taken = $conn->query("SELECT COUNT(*) as count FROM quiz_attempts WHERE user_id = $student_id")->fetch_assoc()['count'];
$total_certificates = $certificates_result->num_rows;
?>

<!-- Inline styles untuk memastikan semua komponen tampil dengan benar -->
<style>
    .welcome-card {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        margin-bottom: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .stat-card i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .course-item {
        background-color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .progress {
        height: 20px;
        border-radius: 10px;
        margin: 10px 0;
    }
    
    .quiz-item {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .section-card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .section-title {
        color: #333;
        font-size: 1.25rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 8px;
        color: #6a11cb;
    }
    
    @media (max-width: 768px) {
        .stat-card .stat-value {
            font-size: 1.5rem;
        }
        
        .stat-card i {
            font-size: 1.5rem;
        }
    }
</style>

<!-- Welcome Section -->
<div class="welcome-card">
    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Your learning journey continues today.</p>
    <a href="courses.php" class="btn btn-light mt-3">
        <i class="fas fa-book-open me-2"></i> Browse Courses
    </a>
</div>

<!-- Stats Cards Row -->
<div class="row mb-4">
    <div class="col-md-4 col-sm-4 col-12">
        <div class="stat-card">
            <i class="fas fa-graduation-cap text-primary"></i>
            <div class="stat-value text-primary"><?php echo $total_courses; ?></div>
            <div>Total Courses</div>
        </div>
    </div>
    <div class="col-md-4 col-sm-4 col-12">
        <div class="stat-card">
            <i class="fas fa-clipboard-check text-success"></i>
            <div class="stat-value text-success"><?php echo $total_quizzes_taken; ?></div>
            <div>Quizzes Taken</div>
        </div>
    </div>
    <div class="col-md-4 col-sm-4 col-12">
        <div class="stat-card">
            <i class="fas fa-award text-warning"></i>
            <div class="stat-value text-warning"><?php echo $total_certificates; ?></div>
            <div>Certificates</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Courses Section -->
    <div class="col-lg-8 col-md-7 col-12 mb-4">
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-book"></i> My Courses
            </div>
            
            <?php if ($courses_result->num_rows > 0): ?>
                <?php 
                $courses_result->data_seek(0); // Reset pointer
                while ($course = $courses_result->fetch_assoc()): ?>
                    <div class="course-item">
                        <h5><?php echo htmlspecialchars($course['title']); ?></h5>
                        <p class="text-muted">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                        
                        <div class="progress">
                            <div class="progress-bar bg-primary" 
                                 role="progressbar" 
                                 style="width: <?php echo $course['progress']; ?>%"
                                 aria-valuenow="<?php echo $course['progress']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $course['progress']; ?>%
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-2 mb-2">
                            <small class="text-muted">
                                <i class="fas fa-book me-1"></i> <?php echo $course['total_materials']; ?> Materials
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-tasks me-1"></i> <?php echo $course['total_quizzes']; ?> Quizzes
                            </small>
                        </div>
                        
                        <a href="view_course.php?id=<?php echo $course['course_id']; ?>" 
                           class="btn btn-primary btn-sm">
                            Continue Learning
                        </a>
                    </div>
                <?php endwhile; ?>
                
                <div class="text-center mt-3">
                    <a href="courses.php" class="btn btn-outline-primary">View All Courses</a>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <p>You haven't enrolled in any courses yet.</p>
                    <a href="courses.php" class="btn btn-primary">Browse Available Courses</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4 col-md-5 col-12">
        <!-- Recent Quiz Performance -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-chart-bar"></i> Recent Quiz Results
            </div>
            
            <?php if ($quizzes_result->num_rows > 0): ?>
                <?php 
                $quizzes_result->data_seek(0); // Reset pointer
                while ($quiz = $quizzes_result->fetch_assoc()): ?>
                    <div class="quiz-item">
                        <div>
                            <div><?php echo htmlspecialchars($quiz['quiz_title']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($quiz['course_title']); ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge <?php echo $quiz['score'] >= 70 ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $quiz['score']; ?>%
                            </span>
                            <div class="small text-muted">
                                <?php echo date('M d, Y', strtotime($quiz['completed_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <div class="text-center mt-3">
                    <a href="quizzes.php" class="btn btn-outline-primary">View All Quizzes</a>
                </div>
            <?php else: ?>
                <div class="text-center py-3">
                    <p>No quiz attempts yet.</p>
                    <a href="quizzes.php" class="btn btn-outline-primary">Take a Quiz</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- View Courses Button -->
        <div class="section-card text-center">
            <div class="mb-3">
                <i class="fas fa-graduation-cap fa-3x text-primary"></i>
            </div>
            <h5>Ready to Learn?</h5>
            <p>Explore our courses and start learning today.</p>
            <a href="courses.php" class="btn btn-primary w-100">
                <i class="fas fa-book-open me-2"></i> Browse All Courses
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>