<?php
// generate_certificate.php - Generate a PDF certificate
session_start();
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'vendor/fpdf.php'; // Path ke FPDF di Hostinger

requireLogin();
requireRole('student');

try {
    $certificateId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($certificateId === false || $certificateId <= 0) {
        throw new Exception('Invalid certificate ID');
    }

    $pdo = $GLOBALS['pdo'] ?? new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name AS recipient_name, co.title AS course_name
        FROM certificates c
        LEFT JOIN users u ON c.user_id = u.user_id
        LEFT JOIN courses co ON c.course_id = co.course_id
        WHERE c.certificate_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$certificateId, $_SESSION['user_id']]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        throw new Exception('Certificate not found or access denied');
    }

    // Buat PDF
    $pdf = new FPDF('L', 'mm', 'A4'); // Landscape A4
    $pdf->AddPage();
    
    // Border dekoratif
    $pdf->SetLineWidth(1);
    $pdf->Rect(10, 10, 277, 190, 'D'); // Border luar
    
    // Header
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(0, 102, 204); // Biru
    $pdf->Cell(0, 20, 'Certificate of Completion', 0, 1, 'C');
    
    // Subheader
    $pdf->SetFont('Arial', '', 16);
    $pdf->SetTextColor(0, 0, 0); // Hitam
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'This certifies that', 0, 1, 'C');
    
    // Nama penerima
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 15, $certificate['recipient_name'], 0, 1, 'C');
    
    // Detail kursus
    $pdf->SetFont('Arial', '', 16);
    $pdf->Cell(0, 10, 'has successfully completed the course', 0, 1, 'C');
    $pdf->SetFont('Arial', 'I', 18);
    $pdf->Cell(0, 15, $certificate['course_name'], 0, 1, 'C');
    
    // Info tambahan
    $pdf->SetFont('Arial', '', 12);
    $pdf->Ln(20);
    $pdf->Cell(0, 8, 'Certificate Number: ' . $certificate['certificate_number'], 0, 1, 'C');
    $pdf->Cell(0, 8, 'Issued Date: ' . date('d M Y', strtotime($certificate['issued_date'])), 0, 1, 'C');
    $pdf->Cell(0, 8, 'Status: ' . ucfirst($certificate['status']), 0, 1, 'C');

    // Footer (opsional)
    $pdf->SetY(180);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'Generated on ' . date('d M Y'), 0, 0, 'R');

    // Output sebagai download
    $pdf->Output('D', 'Certificate_' . $certificate['certificate_number'] . '.pdf');

} catch (Exception $e) {
    error_log("Error generating certificate: " . $e->getMessage());
    die("Oops, something went wrong: " . htmlspecialchars($e->getMessage()));
}
?>