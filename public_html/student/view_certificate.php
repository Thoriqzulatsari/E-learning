<?php
// student/view_certificate.php
require_once '../includes/header.php';
requireRole('student');

$student_id = $_SESSION['user_id'];
$certificate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify certificate ownership
$certificate_query = $conn->prepare("
    SELECT 
        cert.*,
        c.title AS course_title,
        u.full_name AS student_name,
        u2.full_name AS instructor_name
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.course_id
    JOIN users u ON cert.user_id = u.user_id
    JOIN users u2 ON c.instructor_id = u2.user_id
    WHERE cert.certificate_id = ? AND cert.user_id = ?
");
$certificate_query->bind_param("ii", $certificate_id, $student_id);
$certificate_query->execute();
$certificate = $certificate_query->get_result()->fetch_assoc();

if (!$certificate) {
    $_SESSION['error'] = "Certificate not found or unauthorized access.";
    header("Location: certificates.php");
    exit;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Certificate Preview</h4>
                    <a href="certificates.php" class="btn btn-light btn-sm">Back to Certificates</a>
                </div>
                <div class="card-body p-0">
                    <div class="certificate-container">
                        <div class="certificate-inner">
                            <div class="certificate p-5 text-center border border-4 border-warning m-3">
                                <h1 class="display-4 fw-bold text-primary mb-4">CERTIFICATE OF COMPLETION</h1>
                                <p class="fs-4">This is to certify that</p>
                                <div class="fs-1 fw-bold my-4"><?php echo htmlspecialchars($certificate['student_name']); ?></div>
                                <p class="fs-4">has successfully completed the course</p>
                                <div class="fs-2 fw-bold my-4"><?php echo htmlspecialchars($certificate['course_title']); ?></div>
                                <div class="fs-5 my-4">Issued on: <?php echo date('F d, Y', strtotime($certificate['issued_date'])); ?></div>
                                
                                <div class="row mt-5">
                                    <div class="col-md-6">
                                        <div class="signature">
                                            <div class="border-top border-dark d-inline-block pt-2" style="width: 200px;">
                                                <?php echo htmlspecialchars($certificate['instructor_name']); ?><br>
                                                <small>Instructor</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="signature">
                                            <div class="border-top border-dark d-inline-block pt-2" style="width: 200px;">
                                                Mini E-Learning<br>
                                                <small>Platform Director</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-5 text-secondary">
                                    Certificate ID: <?php echo htmlspecialchars($certificate['certificate_number']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        <a href="download_certificate.php?id=<?php echo $certificate_id; ?>" class="btn btn-success btn-lg">
                            <i class="fas fa-download"></i> Download Certificate
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.certificate-container {
    overflow-x: auto;
}
.certificate-inner {
    min-width: 900px;
}
.certificate {
    background-color: #fff;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
@media print {
    .card-header, .card-footer, .navbar, .footer-modern {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .certificate {
        border: 5px solid #ffc107 !important;
    }
}
</style>

<script>
// Add print button
document.addEventListener('DOMContentLoaded', function() {
    const footer = document.querySelector('.card-footer .d-flex');
    const printBtn = document.createElement('button');
    printBtn.className = 'btn btn-primary btn-lg ms-2';
    printBtn.innerHTML = '<i class="fas fa-print"></i> Print Certificate';
    printBtn.addEventListener('click', function() {
        window.print();
    });
    footer.appendChild(printBtn);
});
</script>

<?php require_once '../includes/footer.php'; ?>