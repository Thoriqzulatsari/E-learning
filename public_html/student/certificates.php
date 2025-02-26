<?php
// student/certificates.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

// Handle certificate generation request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_certificate'])) {
    $course_id = (int)$_POST['course_id'];
    
    // Check if certificate already exists
    $check_cert = $conn->prepare("SELECT certificate_id FROM certificates WHERE user_id = ? AND course_id = ?");
    $check_cert->bind_param("ii", $student_id, $course_id);
    $check_cert->execute();
    
    if ($check_cert->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Certificate for this course already exists!";
    } else {
        // Use the generateCertificate function from functions.php
        $result = generateCertificate($student_id, $course_id);
        
        if (isset($result['success'])) {
            $_SESSION['success'] = "Certificate generated successfully!";
        } else {
            $_SESSION['error'] = $result['error'] ?? "Failed to generate certificate.";
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: certificates.php");
    exit;
}

// Get all certificates for the student
$certificates_query = $conn->prepare("
    SELECT 
        cert.certificate_id,
        cert.certificate_number,
        cert.issued_date,
        c.course_id,
        c.title AS course_title,
        u.full_name AS student_name,
        u2.full_name AS instructor_name
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.course_id
    JOIN users u ON cert.user_id = u.user_id
    JOIN users u2 ON c.instructor_id = u2.user_id
    WHERE cert.user_id = ?
    ORDER BY cert.issued_date DESC
");
$certificates_query->bind_param("i", $student_id);
$certificates_query->execute();
$certificates = $certificates_query->get_result();

// Get completed courses without certificates
$completed_courses_query = $conn->prepare("
    SELECT 
        c.course_id,
        c.title,
        e.progress,
        u.full_name AS instructor_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    JOIN users u ON c.instructor_id = u.user_id
    WHERE e.user_id = ? 
    AND e.progress = 100
    AND c.course_id NOT IN (
        SELECT course_id FROM certificates WHERE user_id = ?
    )
");
$completed_courses_query->bind_param("ii", $student_id, $student_id);
$completed_courses_query->execute();
$completed_courses = $completed_courses_query->get_result();

// Get user details for certificate
$user_query = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$user_query->bind_param("i", $student_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();
?>

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

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h4 class="card-title mb-0">My Certificates</h4>
                </div>
                <div class="card-body">
                    <?php if ($certificates->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Certificate Number</th>
                                        <th>Course</th>
                                        <th>Instructor</th>
                                        <th>Issue Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($certificate = $certificates->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($certificate['certificate_number']); ?></td>
                                            <td><?php echo htmlspecialchars($certificate['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($certificate['instructor_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($certificate['issued_date'])); ?></td>
                                            <td>
                                                <a href="download_certificate.php?id=<?php echo $certificate['certificate_id']; ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <a href="view_certificate.php?id=<?php echo $certificate['certificate_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-award fa-4x text-muted"></i>
                            </div>
                            <h4>No Certificates Yet</h4>
                            <p class="text-muted">Complete courses to earn your certificates!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($completed_courses->num_rows > 0): ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="card-title mb-0">Completed Courses</h4>
                    </div>
                    <div class="card-body">
                        <p>Generate certificates for courses you've completed:</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Instructor</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($course = $completed_courses->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                            <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $course['progress']; ?>%" 
                                                         aria-valuenow="<?php echo $course['progress']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $course['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <form method="POST">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                                    <button type="submit" name="generate_certificate" class="btn btn-warning">
                                                        <i class="fas fa-certificate"></i> Generate Certificate
                                                    </button>
                                                </form>
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
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>