<?php
// student/download_certificate.php
// This file generates a certificate PDF for download

// Don't include the header as it might send HTML content
// We only want to output the PDF file
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

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

// Create a simple HTML-based certificate instead of PDF
// This is a fallback solution if TCPDF is not available
$filename = 'certificate_' . $certificate['certificate_number'] . '.html';

// Set headers for file download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Generate the certificate HTML
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .certificate-container {
            width: 100%;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .certificate {
            background-color: #fff;
            border: 5px solid #4a90e2;
            padding: 50px;
            text-align: center;
            color: #333;
            position: relative;
        }
        .certificate h1 {
            font-size: 36px;
            font-weight: bold;
            color: #4a90e2;
            margin-bottom: 20px;
        }
        .certificate .recipient {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin: 30px 0;
        }
        .certificate .course {
            font-size: 22px;
            margin: 20px 0;
        }
        .certificate .date {
            font-size: 16px;
            margin: 30px 0;
        }
        .certificate .signature {
            margin-top: 50px;
            display: inline-block;
            width: 200px;
        }
        .signature-line {
            border-top: 1px solid #333;
            padding-top: 10px;
            width: 200px;
            display: inline-block;
            margin: 0 30px;
        }
        .certificate .number {
            font-size: 12px;
            color: #666;
            margin-top: 40px;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .certificate-container {
                margin: 0;
                padding: 0;
                width: 100%;
            }
            .certificate {
                border: 5px solid #4a90e2;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate">
            <h1>CERTIFICATE OF COMPLETION</h1>
            <p>This is to certify that</p>
            <div class="recipient">' . htmlspecialchars($certificate['student_name']) . '</div>
            <p>has successfully completed the course</p>
            <div class="course">' . htmlspecialchars($certificate['course_title']) . '</div>
            <div class="date">Issued on: ' . date('F d, Y', strtotime($certificate['issued_date'])) . '</div>
            
            <div class="signatures">
                <div class="signature-line">
                    ' . htmlspecialchars($certificate['instructor_name']) . '<br>
                    <small>Instructor</small>
                </div>
                <div class="signature-line">
                    Mini E-Learning<br>
                    <small>Platform Director</small>
                </div>
            </div>
            
            <div class="number">Certificate ID: ' . htmlspecialchars($certificate['certificate_number']) . '</div>
        </div>
    </div>
    <script>
        // Auto print when opened
        window.onload = function() {
            // Uncomment the next line if you want automatic printing
            // window.print();
        }
    </script>
</body>
</html>';

echo $html;
exit;
?>