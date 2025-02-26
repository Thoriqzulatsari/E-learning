<?php
// student/view_material.php
require_once '../includes/header.php';
requireRole('student');

$material_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student_id = $_SESSION['user_id'];

// Fetch material details with course enrollment verification
$material_query = $conn->prepare("
    SELECT m.*, c.title as course_title, c.course_id, e.enrollment_id
    FROM materials m
    JOIN courses c ON m.course_id = c.course_id
    JOIN enrollments e ON c.course_id = e.course_id
    WHERE m.material_id = ? AND e.user_id = ?
");
$material_query->bind_param("ii", $material_id, $student_id);
$material_query->execute();
$material = $material_query->get_result()->fetch_assoc();

if (!$material) {
    $_SESSION['error'] = "Material not found or you are not enrolled in this course.";
    header("Location: courses.php");
    exit;
}

// Mark material as viewed/completed
$progress_check = $conn->prepare("
    INSERT INTO material_progress (user_id, course_id, material_id, completed) 
    VALUES (?, ?, ?, 1) 
    ON DUPLICATE KEY UPDATE completed = 1
");
$progress_check->bind_param("iii", $student_id, $material['course_id'], $material_id);
$progress_check->execute();

// Fetch course progress
$progress_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM materials WHERE course_id = ?) as total_materials,
        (SELECT COUNT(*) FROM material_progress WHERE user_id = ? AND course_id = ? AND completed = 1) as completed_materials
");
$progress_query->bind_param("iii", $material['course_id'], $student_id, $material['course_id']);
$progress_query->execute();
$progress = $progress_query->get_result()->fetch_assoc();
$course_progress = $progress['total_materials'] > 0 
    ? round(($progress['completed_materials'] / $progress['total_materials']) * 100, 2) 
    : 0;

// Update enrollment progress
$update_enrollment = $conn->prepare("
    UPDATE enrollments 
    SET progress = ? 
    WHERE user_id = ? AND course_id = ?
");
$update_enrollment->bind_param("dii", $course_progress, $student_id, $material['course_id']);
$update_enrollment->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($material['title']); ?> - Material View</title>
    <style>
        .material-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            max-width: 100%;
            background: #000;
        }
        
        .video-container video, 
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .pdf-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="view_course.php?id=<?php echo $material['course_id']; ?>">
                            <?php echo htmlspecialchars($material['course_title']); ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($material['title']); ?>
                    </li>
                </ol>
            </nav>

            <div class="card material-container">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($material['title']); ?></h4>
                </div>
                
                <div class="card-body">
                    <?php switch($material['content_type']): 
                        case 'video': ?>
                            <div class="video-container">
                                <video controls>
                                    <source src="../uploads/materials/<?php echo htmlspecialchars($material['file_path']); ?>" 
                                            type="video/<?php echo pathinfo($material['file_path'], PATHINFO_EXTENSION); ?>">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <?php break; 
                        
                        case 'pdf': ?>
                            <div class="pdf-viewer">
                                <object 
                                    data="../uploads/materials/<?php echo htmlspecialchars($material['file_path']); ?>" 
                                    type="application/pdf" 
                                    width="100%" 
                                    height="600px">
                                    <p>Your browser doesn't support PDF viewing. 
                                    <a href="../uploads/materials/<?php echo htmlspecialchars($material['file_path']); ?>" 
                                       target="_blank">Download PDF</a>
                                    </p>
                                </object>
                            </div>
                            <?php break; 
                        
                        case 'link': ?>
                            <div class="alert alert-info">
                                <h5>External Resource</h5>
                                <p>Click the link below to access the external resource:</p>
                                <a href="<?php echo htmlspecialchars($material['content']); ?>" 
                                   target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt"></i> Open Link
                                </a>
                            </div>
                            <?php break; 
                        
                        case 'text': ?>
                            <div class="markdown-content">
                                <?php echo nl2br(htmlspecialchars($material['content'])); ?>
                            </div>
                            <?php break; 
                    endswitch; ?>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-0">
                                <strong>Course Progress:</strong> 
                                <?php echo $course_progress; ?>%
                            </p>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: <?php echo $course_progress; ?>%;" 
                                     aria-valuenow="<?php echo $course_progress; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo $course_progress; ?>%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="view_course.php?id=<?php echo $material['course_id']; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Course
                            </a>
                            <?php if ($course_progress == 100): ?>
                                <a href="../certificates/generate.php?course_id=<?php echo $material['course_id']; ?>" 
                                   class="btn btn-success">
                                    <i class="fas fa-award"></i> Get Certificate
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Optional: Track video/audio completion
    document.addEventListener('DOMContentLoaded', function() {
        const videoElement = document.querySelector('video');
        if (videoElement) {
            videoElement.addEventListener('ended', function() {
                // Optional: Send AJAX request to mark material as completed
                fetch('mark_completed.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        material_id: <?php echo $material_id; ?>,
                        course_id: <?php echo $material['course_id']; ?>
                    })
                });
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>