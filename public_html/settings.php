<?php
// settings.php

// Enable error reporting for debugging (temporary)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session management
session_start();

// Database connection parameters - replace with your actual values
$db_host = 'localhost'; // Ganti dengan host yang diberikan oleh hosting (cek cPanel atau dokumentasi)
$db_user = 'u287442801_mini_elearning'; // Ganti dengan nama pengguna MySQL Anda
$db_pass = 'Jawabarat123_'; // Ganti dengan kata sandi MySQL Anda
$db_name = 'u287442801_mini_elearning'; // Ganti dengan nama database Anda

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection and handle errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to handle database queries safely
function safe_query($conn, $sql, $params = [], $types = '', $is_select = true) {
    if (!$conn) return false;
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $result = $stmt->execute();
    if ($result === false) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    if ($is_select) {
        $output = $stmt->get_result();
    } else {
        $output = true; // Return true for non-SELECT queries (INSERT, UPDATE, DELETE)
    }
    $stmt->close();
    return $output;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User data initialization with default values
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'student'; // Default to 'student' if not set

// Initialize messages and data
$success_message = '';
$error_message = '';
$user = ['first_name' => '', 'last_name' => '', 'email' => '', 'bio' => ''];
$preferences = ['email_notifications' => 1, 'course_updates' => 1, 'assignment_reminders' => 1];
$instructor_data = ['specialization' => '', 'teaching_experience' => '', 'credentials' => ''];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($conn) {
            $result = safe_query($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ? WHERE user_id = ?", 
                [$first_name, $last_name, $email, $bio, $user_id], "ssssi", false); // Non-SELECT query
            if ($result !== false) {
                $success_message = "Profile updated successfully!";
                $user = array_merge($user, ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'bio' => $bio]);
            } else {
                $error_message = "Error updating profile.";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $success_message = "Password changed successfully!"; // In production, verify and update in database
        }
    } elseif (isset($_POST['notification_settings'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $course_updates = isset($_POST['course_updates']) ? 1 : 0;
        $assignment_reminders = isset($_POST['assignment_reminders']) ? 1 : 0;

        if ($conn) {
            $check = safe_query($conn, "SELECT * FROM user_preferences WHERE user_id = ?", [$user_id], "i", true);
            if ($check && $check->num_rows > 0) {
                $result = safe_query($conn, "UPDATE user_preferences SET email_notifications = ?, course_updates = ?, assignment_reminders = ? WHERE user_id = ?", 
                    [$email_notifications, $course_updates, $assignment_reminders, $user_id], "iiii", false);
            } else {
                $result = safe_query($conn, "INSERT INTO user_preferences (user_id, email_notifications, course_updates, assignment_reminders) VALUES (?, ?, ?, ?)", 
                    [$user_id, $email_notifications, $course_updates, $assignment_reminders], "iiii", false);
            }
            if ($result !== false) {
                $success_message = "Notification settings updated successfully!";
                $preferences = ['email_notifications' => $email_notifications, 'course_updates' => $course_updates, 'assignment_reminders' => $assignment_reminders];
            } else {
                $error_message = "Error updating notification settings.";
            }
        }
    } elseif ($user_role === 'instructor' && isset($_POST['update_instructor_profile'])) {
        $specialization = trim($_POST['specialization'] ?? '');
        $teaching_experience = trim($_POST['teaching_experience'] ?? '');
        $credentials = trim($_POST['credentials'] ?? '');

        if ($conn) {
            $check = safe_query($conn, "SELECT * FROM instructor_details WHERE user_id = ?", [$user_id], "i", true);
            if ($check && $check->num_rows > 0) {
                $result = safe_query($conn, "UPDATE instructor_details SET specialization = ?, teaching_experience = ?, credentials = ? WHERE user_id = ?", 
                    [$specialization, $teaching_experience, $credentials, $user_id], "sssi", false);
            } else {
                $result = safe_query($conn, "INSERT INTO instructor_details (user_id, specialization, teaching_experience, credentials) VALUES (?, ?, ?, ?)", 
                    [$user_id, $specialization, $teaching_experience, $credentials], "isss", false);
            }
            if ($result !== false) {
                $success_message = "Instructor profile updated successfully!";
                $instructor_data = ['specialization' => $specialization, 'teaching_experience' => $teaching_experience, 'credentials' => $credentials];
            } else {
                $error_message = "Error updating instructor profile.";
            }
        }
    }
}

// Fetch current data if connection exists
if ($conn) {
    $result = safe_query($conn, "SELECT * FROM users WHERE user_id = ?", [$user_id], "i", true);
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }

    $result = safe_query($conn, "SELECT * FROM user_preferences WHERE user_id = ?", [$user_id], "i", true);
    if ($result && $result->num_rows > 0) {
        $preferences = $result->fetch_assoc();
    }

    if ($user_role === 'instructor') {
        $result = safe_query($conn, "SELECT * FROM instructor_details WHERE user_id = ?", [$user_id], "i", true);
        if ($result && $result->num_rows > 0) {
            $instructor_data = $result->fetch_assoc();
        }
    }
}

// Helper function for safe HTML output
function escape($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Mini E-Learning</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 40px;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 1rem 1.5rem;
            border-bottom: none;
        }
        .list-group-item {
            border: none;
            border-radius: 0;
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
            transition: background-color 0.3s;
        }
        .list-group-item.active {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .list-group-item:hover {
            background-color: #e9ecef;
            color: #007bff;
        }
        .form-control, .form-control:focus {
            border-radius: 8px;
            border-color: #ced4da;
            box-shadow: none;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
        }
        .user-info {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-check-input:checked + .form-check-label {
            color: #007bff;
        }
        @media (max-width: 768px) {
            .list-group, .tab-content {
                margin-top: 20px;
            }
            .card {
                margin: 0 10px;
            }
            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-3">Mini E-Learning</h1>
                <div class="user-info">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4>Welcome, <?php echo escape($user['first_name'] . ' ' . $user['last_name'] ?: 'Guest'); ?></h4>
                            <p><strong>Role:</strong> <?php echo ucfirst($user_role ?? 'Student'); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <a href="dashboard.php" class="btn btn-outline-primary me-2"><i class="fas fa-home"></i> Dashboard</a>
                            <a href="logout.php" class="btn btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="mb-4"><i class="fas fa-cog"></i> Account Settings</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="list-group">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i> Profile Settings
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-lock me-2"></i> Security
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                    <?php if ($user_role === 'instructor'): ?>
                        <a href="#instructor" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Instructor Settings
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Settings Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0"><i class="fas fa-id-card me-2"></i> Profile Information</h3>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label"><i class="fas fa-user me-2"></i> First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo escape($user['first_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label"><i class="fas fa-user me-2"></i> Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo escape($user['last_name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i> Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label"><i class="fas fa-info-circle me-2"></i> Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo escape($user['bio']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0"><i class="fas fa-key me-2"></i> Change Password</h3>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label"><i class="fas fa-lock me-2"></i> Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label"><i class="fas fa-lock me-2"></i> New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label"><i class="fas fa-lock me-2"></i> Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary"><i class="fas fa-key me-2"></i> Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications Tab -->
                    <div class="tab-pane fade" id="notifications">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0"><i class="fas fa-bell me-2"></i> Notification Preferences</h3>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="email_notifications" name="email_notifications" <?php echo $preferences['email_notifications'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="email_notifications"><i class="fas fa-envelope me-2"></i> Email Notifications</label>
                                            <small class="form-text text-muted">Receive notifications via email.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="course_updates" name="course_updates" <?php echo $preferences['course_updates'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="course_updates"><i class="fas fa-book me-2"></i> Course Updates</label>
                                            <small class="form-text text-muted">Receive notifications when course content is updated.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="assignment_reminders" name="assignment_reminders" <?php echo $preferences['assignment_reminders'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="assignment_reminders"><i class="fas fa-tasks me-2"></i> Assignment Reminders</label>
                                            <small class="form-text text-muted">Receive reminders about upcoming assignment deadlines.</small>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="notification_settings" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Preferences</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instructor Settings Tab -->
                    <?php if ($user_role === 'instructor'): ?>
                    <div class="tab-pane fade" id="instructor">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i> Instructor Profile</h3>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="specialization" class="form-label"><i class="fas fa-graduation-cap me-2"></i> Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo escape($instructor_data['specialization']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="teaching_experience" class="form-label"><i class="fas fa-clock me-2"></i> Teaching Experience</label>
                                        <textarea class="form-control" id="teaching_experience" name="teaching_experience" rows="3"><?php echo escape($instructor_data['teaching_experience']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="credentials" class="form-label"><i class="fas fa-certificate me-2"></i> Credentials</label>
                                        <textarea class="form-control" id="credentials" name="credentials" rows="3"><?php echo escape($instructor_data['credentials']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_instructor_profile" class="btn btn-primary"><i class="fas fa-save me-2"></i> Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // JavaScript for tab navigation
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.list-group-item');
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const target = this.getAttribute('href').substring(1);
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
                    document.getElementById(target).classList.add('show', 'active');
                });
            });
        });
    </script>
</body>
</html>