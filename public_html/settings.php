<?php
// settings.php - User account settings management page

// Enable error reporting for debugging (should be disabled in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session management - ensures user state is maintained across page requests
session_start();

// Database connection parameters - using placeholders for security
$db_host = 'localhost'; // Database host address - update based on hosting provider
$db_user = 'u287442801_mini_elearning'; // MySQL username
$db_pass = 'Jawabarat123_'; // MySQL password - should be in a separate config file in production
$db_name = 'u287442801_mini_elearning'; // Database name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection and handle errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to handle database queries safely with prepared statements
function safe_query($conn, $sql, $params = [], $types = '', $is_select = true) {
    if (!$conn) return false; // Return false if no connection
    
    try {
        $stmt = $conn->prepare($sql); // Prepare the SQL statement
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error); // Log error if preparation fails
            return false;
        }
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params); // Bind parameters with their types
        }
        $result = $stmt->execute(); // Execute the prepared statement
        if ($result === false) {
            error_log("Execute failed: " . $stmt->error); // Log error if execution fails
            $stmt->close();
            return false;
        }
        if ($is_select) {
            $output = $stmt->get_result(); // Get result set for SELECT queries
        } else {
            $output = true; // Return true for non-SELECT queries (INSERT, UPDATE, DELETE)
        }
        $stmt->close(); // Close the statement
        return $output;
    } catch (Exception $e) {
        error_log("Query error: " . $e->getMessage());
        return false;
    }
}

// Function to check if a table exists
function table_exists($conn, $table_name) {
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    return $result && $result->num_rows > 0;
}

// Function to check if a column exists in a table
function column_exists($conn, $table_name, $column_name) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to create required tables if they don't exist
function create_required_tables($conn) {
    $tables_created = true;
    
    // Check if users table exists and has required columns
    if (table_exists($conn, 'users')) {
        // Check if bio column exists in users table
        if (!column_exists($conn, 'users', 'bio')) {
            try {
                $sql = "ALTER TABLE users ADD COLUMN bio TEXT";
                if (!$conn->query($sql)) {
                    error_log("Error adding bio column to users table: " . $conn->error);
                }
            } catch (Exception $e) {
                error_log("Error adding bio column: " . $e->getMessage());
            }
        }
    }
    
    // Create user_preferences table if it doesn't exist
    if (!table_exists($conn, 'user_preferences')) {
        try {
            $sql = "CREATE TABLE user_preferences (
                preference_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email_notifications TINYINT(1) DEFAULT 1,
                course_updates TINYINT(1) DEFAULT 1,
                assignment_reminders TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY (user_id)
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating user_preferences table: " . $conn->error);
                $tables_created = false;
            }
        } catch (Exception $e) {
            error_log("Error creating user_preferences table: " . $e->getMessage());
            $tables_created = false;
        }
    }
    
    // Create instructor_details table if it doesn't exist
    if (!table_exists($conn, 'instructor_details')) {
        try {
            $sql = "CREATE TABLE instructor_details (
                instructor_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                specialization VARCHAR(255),
                teaching_experience TEXT,
                credentials TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY (user_id)
            )";
            
            if (!$conn->query($sql)) {
                error_log("Error creating instructor_details table: " . $conn->error);
                $tables_created = false;
            }
        } catch (Exception $e) {
            error_log("Error creating instructor_details table: " . $e->getMessage());
            $tables_created = false;
        }
    }
    
    return $tables_created;
}

// Try to create required tables - ignore errors if they occur
try {
    create_required_tables($conn);
} catch (Exception $e) {
    error_log("Error in table creation: " . $e->getMessage());
}

// Check if user is logged in - redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// User data initialization with default values
$user_id = $_SESSION['user_id']; // Get user ID from session
$user_role = $_SESSION['user_role'] ?? 'student'; // Get user role or default to 'student'

// Initialize messages and data containers
$success_message = '';
$error_message = '';
$user = [
    'first_name' => '', 
    'last_name' => '', 
    'email' => '', 
    'bio' => ''
];
$preferences = [
    'email_notifications' => 1, 
    'course_updates' => 1, 
    'assignment_reminders' => 1
];
$instructor_data = [
    'specialization' => '', 
    'teaching_experience' => '', 
    'credentials' => ''
];

// Process different form submissions based on the form type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process profile update form
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($conn) {
            try {
                // Update user profile in the database
                $result = safe_query($conn, "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ? WHERE user_id = ?", 
                    [$first_name, $last_name, $email, $bio, $user_id], "ssssi", false);
                    
                if ($result !== false) {
                    $success_message = "Profile updated successfully!";
                    $user = [
                        'first_name' => $first_name, 
                        'last_name' => $last_name, 
                        'email' => $email, 
                        'bio' => $bio
                    ];
                } else {
                    $error_message = "Error updating profile.";
                }
            } catch (Exception $e) {
                $error_message = "Error updating profile: " . $e->getMessage();
            }
        }
    // Process password change form
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate password match
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            try {
                // First, get the current hashed password
                $password_query = safe_query($conn, "SELECT password FROM users WHERE user_id = ?", [$user_id], "i", true);
                if ($password_query && $password_query->num_rows > 0) {
                    $user_data = $password_query->fetch_assoc();
                    
                    // Check if password column has data
                    if (isset($user_data['password']) && !empty($user_data['password'])) {
                        // Verify current password if hashed
                        if (password_verify($current_password, $user_data['password'])) {
                            // Hash the new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            // Update the password
                            $update_result = safe_query($conn, "UPDATE users SET password = ? WHERE user_id = ?", 
                                [$hashed_password, $user_id], "si", false);
                            if ($update_result !== false) {
                                $success_message = "Password changed successfully!";
                            } else {
                                $error_message = "Error updating password.";
                            }
                        } else {
                            $error_message = "Current password is incorrect.";
                        }
                    } else {
                        // If password is not hashed (or empty), just update with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_result = safe_query($conn, "UPDATE users SET password = ? WHERE user_id = ?", 
                            [$hashed_password, $user_id], "si", false);
                        if ($update_result !== false) {
                            $success_message = "Password set successfully!";
                        } else {
                            $error_message = "Error setting password.";
                        }
                    }
                } else {
                    $error_message = "Error retrieving user data.";
                }
            } catch (Exception $e) {
                // If password column does not exist or another error occurs
                $error_message = "Password update failed: " . $e->getMessage();
            }
        }
    // Process notification settings form
    } elseif (isset($_POST['notification_settings'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $course_updates = isset($_POST['course_updates']) ? 1 : 0;
        $assignment_reminders = isset($_POST['assignment_reminders']) ? 1 : 0;

        if ($conn && table_exists($conn, 'user_preferences')) {
            try {
                // Check if user preferences already exist
                $check = safe_query($conn, "SELECT * FROM user_preferences WHERE user_id = ?", [$user_id], "i", true);
                if ($check && $check->num_rows > 0) {
                    // Update existing preferences
                    $result = safe_query($conn, "UPDATE user_preferences SET email_notifications = ?, course_updates = ?, assignment_reminders = ? WHERE user_id = ?", 
                        [$email_notifications, $course_updates, $assignment_reminders, $user_id], "iiii", false);
                } else {
                    // Insert new preferences
                    $result = safe_query($conn, "INSERT INTO user_preferences (user_id, email_notifications, course_updates, assignment_reminders) VALUES (?, ?, ?, ?)", 
                        [$user_id, $email_notifications, $course_updates, $assignment_reminders], "iiii", false);
                }
                if ($result !== false) {
                    $success_message = "Notification settings updated successfully!";
                    $preferences = [
                        'email_notifications' => $email_notifications, 
                        'course_updates' => $course_updates, 
                        'assignment_reminders' => $assignment_reminders
                    ];
                } else {
                    $error_message = "Error updating notification settings.";
                }
            } catch (Exception $e) {
                $error_message = "Error updating preferences: " . $e->getMessage();
            }
        } else {
            $error_message = "Notification settings could not be saved. Please try again later.";
        }
    // Process instructor profile update form (only for instructors)
    } elseif ($user_role === 'instructor' && isset($_POST['update_instructor_profile'])) {
        $specialization = trim($_POST['specialization'] ?? '');
        $teaching_experience = trim($_POST['teaching_experience'] ?? '');
        $credentials = trim($_POST['credentials'] ?? '');

        if ($conn && table_exists($conn, 'instructor_details')) {
            try {
                // Check if instructor details already exist
                $check = safe_query($conn, "SELECT * FROM instructor_details WHERE user_id = ?", [$user_id], "i", true);
                if ($check && $check->num_rows > 0) {
                    // Update existing instructor details
                    $result = safe_query($conn, "UPDATE instructor_details SET specialization = ?, teaching_experience = ?, credentials = ? WHERE user_id = ?", 
                        [$specialization, $teaching_experience, $credentials, $user_id], "sssi", false);
                } else {
                    // Insert new instructor details
                    $result = safe_query($conn, "INSERT INTO instructor_details (user_id, specialization, teaching_experience, credentials) VALUES (?, ?, ?, ?)", 
                        [$user_id, $specialization, $teaching_experience, $credentials], "isss", false);
                }
                if ($result !== false) {
                    $success_message = "Instructor profile updated successfully!";
                    $instructor_data = [
                        'specialization' => $specialization, 
                        'teaching_experience' => $teaching_experience, 
                        'credentials' => $credentials
                    ];
                } else {
                    $error_message = "Error updating instructor profile.";
                }
            } catch (Exception $e) {
                $error_message = "Error updating instructor profile: " . $e->getMessage();
            }
        } else {
            $error_message = "Instructor settings could not be saved. Please try again later.";
        }
    }
}

// Fetch current user data if connection exists
if ($conn) {
    // Get user's basic information
    try {
        $result = safe_query($conn, "SELECT * FROM users WHERE user_id = ?", [$user_id], "i", true);
        if ($result && $result->num_rows > 0) {
            $fetched_user = $result->fetch_assoc();
            // Ensure all user fields are set, use defaults for missing values
            $user = [
                'first_name' => isset($fetched_user['first_name']) ? $fetched_user['first_name'] : '',
                'last_name' => isset($fetched_user['last_name']) ? $fetched_user['last_name'] : '',
                'email' => isset($fetched_user['email']) ? $fetched_user['email'] : '',
                'bio' => isset($fetched_user['bio']) ? $fetched_user['bio'] : ''
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching user data: " . $e->getMessage());
    }

    // Get user's notification preferences
    if (table_exists($conn, 'user_preferences')) {
        try {
            $result = safe_query($conn, "SELECT * FROM user_preferences WHERE user_id = ?", [$user_id], "i", true);
            if ($result && $result->num_rows > 0) {
                $fetched_preferences = $result->fetch_assoc();
                // Ensure all preference fields are set, use defaults for missing values
                $preferences = [
                    'email_notifications' => isset($fetched_preferences['email_notifications']) ? $fetched_preferences['email_notifications'] : 1,
                    'course_updates' => isset($fetched_preferences['course_updates']) ? $fetched_preferences['course_updates'] : 1,
                    'assignment_reminders' => isset($fetched_preferences['assignment_reminders']) ? $fetched_preferences['assignment_reminders'] : 1
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching user preferences: " . $e->getMessage());
        }
    }

    // Get instructor-specific data if user is an instructor
    if ($user_role === 'instructor' && table_exists($conn, 'instructor_details')) {
        try {
            $result = safe_query($conn, "SELECT * FROM instructor_details WHERE user_id = ?", [$user_id], "i", true);
            if ($result && $result->num_rows > 0) {
                $fetched_instructor_data = $result->fetch_assoc();
                // Ensure all instructor fields are set, use defaults for missing values
                $instructor_data = [
                    'specialization' => isset($fetched_instructor_data['specialization']) ? $fetched_instructor_data['specialization'] : '',
                    'teaching_experience' => isset($fetched_instructor_data['teaching_experience']) ? $fetched_instructor_data['teaching_experience'] : '',
                    'credentials' => isset($fetched_instructor_data['credentials']) ? $fetched_instructor_data['credentials'] : ''
                ];
            }
        } catch (Exception $e) {
            error_log("Error fetching instructor data: " . $e->getMessage());
        }
    }
}

// Helper function for safe HTML output to prevent XSS attacks
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
    
    <!-- Bootstrap CSS for styling and layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
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
        <!-- Header section with user info -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-3">Mini E-Learning</h1>
                <div class="user-info">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4>Welcome, <?php echo (trim($user['first_name'] . ' ' . $user['last_name']) != '') ? escape(trim($user['first_name'] . ' ' . $user['last_name'])) : 'User'; ?></h4>
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
        
        <!-- Success message display -->
        <?php if ($success_message): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Error message display -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left sidebar with navigation tabs -->
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
            
            <!-- Main content area with tab panels -->
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
                    
                    <!-- Security Tab (Password Change) -->
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
                    
                    <!-- Instructor Settings Tab (only visible for instructors) -->
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

    <!-- Bootstrap JS and dependencies for interactive elements -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // JavaScript for tab navigation functionality
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
