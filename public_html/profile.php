<?php
// profile.php - User Profile Management Page
require_once 'includes/header.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_query = $conn->prepare("
    SELECT 
        u.user_id, 
        u.username, 
        u.email, 
        u.full_name, 
        u.role, 
        u.created_at,
        u.profile_picture,
        (SELECT COUNT(*) FROM enrollments WHERE user_id = u.user_id) as total_courses_enrolled,
        (SELECT COUNT(*) FROM certificates WHERE user_id = u.user_id) as total_certificates,
        (SELECT COUNT(*) FROM quiz_attempts WHERE user_id = u.user_id) as total_quizzes_taken
    FROM users u
    WHERE u.user_id = ?
");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Profile Picture Upload
    $profile_picture = $user['profile_picture']; // Keep existing picture by default
    $upload_error = ''; // Variable to store upload error messages

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // File size validation (5MB max)
        $max_file_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['profile_picture']['size'] > $max_file_size) {
            $upload_error = "File too large. Maximum size is 5MB.";
        }
        
        if (in_array($file_ext, $allowed) && empty($upload_error)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = 'uploads/profile_pictures/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!is_dir('uploads/profile_pictures/')) {
                mkdir('uploads/profile_pictures/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if ($profile_picture && file_exists('uploads/profile_pictures/' . $profile_picture)) {
                    unlink('uploads/profile_pictures/' . $profile_picture);
                }
                $profile_picture = $new_filename;
            } else {
                $upload_error = "Failed to upload file.";
            }
        } else {
            if (empty($upload_error)) {
                $upload_error = "Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.";
            }
        }
    }

    // Update profile information
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Prepare update statement
    $update_query = $conn->prepare("
        UPDATE users 
        SET 
            full_name = ?, 
            email = ?, 
            profile_picture = ?
        WHERE user_id = ?
    ");
    $update_query->bind_param("sssi", $full_name, $email, $profile_picture, $user_id);
    
    if ($update_query->execute()) {
        // Update session with new name if changed
        $_SESSION['full_name'] = $full_name;
        
        if (!empty($upload_error)) {
            $_SESSION['warning'] = $upload_error;
        } else {
            $_SESSION['success'] = "Profile updated successfully!";
        }
        
        header("Location: profile.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
        header("Location: profile.php");
        exit;
    }
}

// Fetch recent course activities
$activities_query = $conn->prepare("
    SELECT 
        c.title as course_title,
        e.enrolled_at,
        e.progress
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 5
");
$activities_query->bind_param("i", $user_id);
$activities_query->execute();
$recent_activities = $activities_query->get_result();

// Fetch recent quiz attempts
$quiz_attempts_query = $conn->prepare("
    SELECT 
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
$quiz_attempts_query->bind_param("i", $user_id);
$quiz_attempts_query->execute();
$recent_quiz_attempts = $quiz_attempts_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Mini E-Learning</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #6a11cb;
            --text-color: #333;
            --bg-light: #f8f9fa;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 0;
            text-align: center;
            position: relative;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .profile-stats {
            display: flex;
            justify-content: space-around;
            background-color: white;
            padding: 15px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .profile-section {
            background-color: var(--bg-light);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .profile-form {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .profile-picture-upload {
            position: relative;
            width: 200px;
            margin: 0 auto 20px;
        }

        .profile-picture-upload input[type="file"] {
            display: none;
        }

        .profile-picture-upload .upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-picture-upload .upload-btn:hover {
            background-color: #357abd;
        }

        /* Alert Styles */
        .alert {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        @media (max-width: 768px) {
            .profile-stats {
                flex-direction: column;
            }

            .stat-item {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Alert Messages -->
    <div class="container">
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

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['warning'];
                unset($_SESSION['warning']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <div class="profile-picture-upload">
                <img src="<?php 
                    echo $user['profile_picture'] 
                        ? 'uploads/profile_pictures/' . htmlspecialchars($user['profile_picture']) 
                        : 'https://via.placeholder.com/150'; 
                ?>" 
                     alt="Profile Picture" 
                     class="profile-avatar" 
                     id="profilePreview">
                <label for="profileUpload" class="upload-btn">
                    <i class="fas fa-camera"></i>
                    <input type="file" 
                           id="profileUpload" 
                           name="profile_picture" 
                           accept="image/jpeg,image/png,image/gif"
                           onchange="previewProfilePicture(event)">
                </label>
            </div>
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p><?php echo htmlspecialchars(ucfirst($user['role'])); ?> | Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
        </form>
    </div>

    <!-- Profile Stats -->
    <div class="container mt-4">
        <div class="profile-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $user['total_courses_enrolled']; ?></div>
                <div>Courses Enrolled</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $user['total_certificates']; ?></div>
                <div>Certificates</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $user['total_quizzes_taken']; ?></div>
                <div>Quizzes Taken</div>
            </div>
        </div>

        <!-- Profile Information and Recent Activities -->
        <div class="row mt-4">
            <!-- Profile Edit Form -->
            <div class="col-md-6">
                <div class="profile-section">
                    <h4>Edit Profile</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   readonly>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="role" 
                                   value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" 
                                   readonly>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-md-6">
                <!-- Recent Course Activities -->
                <div class="profile-section">
                    <h4>Recent Course Activities</h4>
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity['course_title']); ?></strong>
                                    <div class="small text-muted">
                                        Enrolled on <?php echo date('M d, Y', strtotime($activity['enrolled_at'])); ?>
                                    </div>
                                </div>
                                <div class="badge bg-primary">
                                    <?php echo $activity['progress']; ?>% Progress
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No recent course activities</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Quiz Attempts -->
                <div class="profile-section">
                    <h4>Recent Quiz Attempts</h4>
                    <?php if ($recent_quiz_attempts->num_rows > 0): ?>
                        <?php while ($attempt = $recent_quiz_attempts->fetch_assoc()): ?>
                            <div class="activity-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($attempt['quiz_title']); ?></strong>
                                    <div class="small text-muted">
                                        Course: <?php echo htmlspecialchars($attempt['course_title']); ?>
                                    </div>
                                </div>
                                <div class="badge bg-<?php echo $attempt['score'] >= 70 ? 'success' : 'warning'; ?>">
                                    <?php echo $attempt['score']; ?>%
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No recent quiz attempts</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function previewProfilePicture(event) {
        const file = event.target.files[0];
        
        // Validasi ukuran file
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            alert('File is too large. Maximum size is 5MB.');
            event.target.value = ''; // Clear the file input
            return;
        }

        // Validasi tipe file
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            event.target.value = ''; // Clear the file input
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            // Update preview image immediately
            document.getElementById('profilePreview').src = e.target.result;
        }

        reader.readAsDataURL(file);

        // Submit form automatically
        document.getElementById('profileForm').submit();
    }

    // Validasi form sebelum submit
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                const fullName = document.getElementById('full_name').value.trim();
                const email = document.getElementById('email').value.trim();

                if (fullName === '' || email === '') {
                    event.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>