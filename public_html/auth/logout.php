<?php
// auth/logout.php
session_start();

// Clear any active session data
function clear_session() {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Clear any remember me cookies if they exist
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();
}

// Optional: Log the logout activity
function log_logout($user_id) {
    global $conn;
    require_once '../config/database.php';
    
    $sql = "INSERT INTO user_logs (user_id, action, action_time) VALUES (?, 'logout', NOW())";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Store user ID before clearing session (for logging)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Clear all session data
clear_session();

// Log the logout if we have a user ID
if ($user_id) {
    log_logout($user_id);
}

// Set logout message
$_SESSION['message'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: login.php");
exit;
?>