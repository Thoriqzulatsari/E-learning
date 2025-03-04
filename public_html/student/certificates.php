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
        try {
            // Check if course is completed
            $check_progress = $conn->prepare("SELECT progress FROM enrollments WHERE user_id = ? AND course_id = ?");
            $check_progress->bind_param("ii", $student_id, $course_id);
            $check_progress->execute();
            $progress_result = $check_progress->get_result();
            
            if ($progress_result->num_rows > 0) {
                $progress_data = $progress_result->fetch_assoc();
                if ($progress_data['progress'] < 100) {
                    throw new Exception("You must complete the course (100%) before generating a certificate.");
                }
                
                // Generate a unique certificate number
                $certificate_number = 'CERT-' . date('Ymd') . '-' . str_pad($student_id, 5, '0', STR_PAD_LEFT) . '-' . 
                                     str_pad($course_id, 5, '0', STR_PAD_LEFT) . '-' . substr(md5(uniqid()), 0, 6);
                
                // Get course details
                $course_query = $conn->prepare("SELECT title FROM courses WHERE course_id = ?");
                $course_query->bind_param("i", $course_id);
                $course_query->execute();
                $course = $course_query->get_result()->fetch_assoc();
                
                $title = "Certificate of Completion: " . $course['title'];
                
                // Insert new certificate - include created_by field set to the current user
                $insert_cert = $conn->prepare(
                    "INSERT INTO certificates (user_id, course_id, certificate_number, issued_date, status, title, created_by) 
                     VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'valid', ?, ?)"
                );
                $insert_cert->bind_param("iissi", $student_id, $course_id, $certificate_number, $title, $student_id);
                
                if (!$insert_cert->execute()) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $_SESSION['success'] = "Certificate generated successfully!";
            } else {
                throw new Exception("You are not enrolled in this course.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
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
        cert.title,
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
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">My Certificates</h4>
                </div>
                <div class="card-body">
                    <?php if ($certificates->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Certificate</th>
                                        <th>Course</th>
                                        <th>Instructor</th>
                                        <th>Issue Date</th>
                                        <th>Certificate Number</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($certificate = $certificates->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($certificate['title'] ?? 'Certificate of Completion'); ?></td>
                                            <td><?php echo htmlspecialchars($certificate['course_title']); ?></td>
                                            <td><?php echo htmlspecialchars($certificate['instructor_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($certificate['issued_date'])); ?></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($certificate['certificate_number']); ?></small></td>
                                            <td>
                                                <a href="view_certificate.php?id=<?php echo $certificate['certificate_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="#" class="btn btn-sm btn-success print-cert" 
                                                   data-cert-id="<?php echo $certificate['certificate_id']; ?>">
                                                    <i class="fas fa-print"></i> Print
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
                                                    <button type="submit" name="generate_certificate" class="btn btn-primary">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add print functionality for certificates
        const printButtons = document.querySelectorAll('.print-cert');
        printButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const certId = this.getAttribute('data-cert-id');
                // Open certificate in new window for printing
                window.open('view_certificate.php?id=' + certId + '&print=true', '_blank');
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>
