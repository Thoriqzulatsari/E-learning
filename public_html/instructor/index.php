<?php
// instructor/index.php
require_once '../includes/header.php';
requireRole('instructor');

$instructor_id = $_SESSION['user_id'];

// Get instructor's courses
$courses_query = $conn->prepare("
    SELECT 
        c.course_id,
        c.title,
        c.status,
        (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as total_students,
        (SELECT COUNT(*) FROM materials WHERE course_id = c.course_id) as total_materials,
        (SELECT COUNT(*) FROM quizzes WHERE course_id = c.course_id) as total_quizzes
    FROM courses c
    WHERE c.instructor_id = ?
");
$courses_query->bind_param("i", $instructor_id);
$courses_query->execute();
$courses_result = $courses_query->get_result();

// Get recent quiz submissions
$recent_submissions = $conn->prepare("
    SELECT 
        q.title as quiz_title,
        c.title as course_title,
        u.username as student_name,
        qa.score,
        qa.completed_at
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.quiz_id
    JOIN courses c ON q.course_id = c.course_id
    JOIN users u ON qa.user_id = u.user_id
    WHERE c.instructor_id = ?
    ORDER BY qa.completed_at DESC
    LIMIT 5
");
$recent_submissions->bind_param("i", $instructor_id);
$recent_submissions->execute();
$submissions_result = $recent_submissions->get_result();
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <h4>Welcome, <?php echo $_SESSION['username']; ?>!</h4>
                <p>Manage your courses and track student progress from your dashboard.</p>
                <a href="courses/create.php" class="btn btn-primary">Create New Course</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Course Statistics -->
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Your Courses</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Status</th>
                                <th>Students</th>
                                <th>Materials</th>
                                <th>Quizzes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $course['total_students']; ?></td>
                                <td><?php echo $course['total_materials']; ?></td>
                                <td><?php echo $course['total_quizzes']; ?></td>
                                <td>
                                    <a href="courses/edit.php?id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-sm btn-primary">Edit</a>
                                    <a href="courses/materials.php?id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-sm btn-info">Materials</a>
                                    <a href="quizzes/manage.php?course_id=<?php echo $course['course_id']; ?>" 
                                       class="btn btn-sm btn-warning">Quizzes</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Quiz Submissions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Quiz Submissions</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($submission = $submissions_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($submission['course_title']); ?></td>
                                <td><?php echo htmlspecialchars($submission['quiz_title']); ?></td>
                                <td><?php echo $submission['score']; ?>%</td>
                                <td><?php echo date('M d, Y H:i', strtotime($submission['completed_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>