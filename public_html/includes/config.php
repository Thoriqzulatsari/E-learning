<?php
// includes/config.php
session_start();

// Database configuration
$db_host = 'localhost';
$db_name = 'u287442801_mini_elearning';
$db_user = 'u287442801_mini_elearning'; // Ganti dengan username database Anda
$db_pass = 'Jawabarat123_'; // Ganti dengan password database Anda

// Try to establish database connection
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Untuk pengembangan, tampilkan pesan error
    // Untuk produksi, log error dan tampilkan pesan yang lebih umum
    die("Database connection failed: " . $e->getMessage());
}

// Helper function to display messages
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to get current user ID
function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Function to get current username
function get_username() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
}

// Function to escape HTML for safe output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to get count of topics in a forum
function get_topics_count($forum_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM topics WHERE forum_id = ?");
    $stmt->execute([$forum_id]);
    return $stmt->fetchColumn();
}

// Function to get count of replies in a forum
function get_replies_count($forum_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM replies r JOIN topics t ON r.topic_id = t.id WHERE t.forum_id = ?");
    $stmt->execute([$forum_id]);
    return $stmt->fetchColumn();
}

// Function to get last post in a forum
function get_last_post($forum_id) {
    global $db;
    
    // Try to get latest reply in this forum
    $stmt = $db->prepare("
        SELECT r.created_at 
        FROM replies r 
        JOIN topics t ON r.topic_id = t.id 
        WHERE t.forum_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$forum_id]);
    $latest_reply = $stmt->fetch();
    
    if ($latest_reply) {
        return format_date_relative($latest_reply['created_at']);
    }
    
    // If no replies, get latest topic
    $stmt = $db->prepare("
        SELECT created_at 
        FROM topics 
        WHERE forum_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$forum_id]);
    $latest_topic = $stmt->fetch();
    
    if ($latest_topic) {
        return format_date_relative($latest_topic['created_at']);
    }
    
    return 'No posts yet';
}

// Function to format date in relative format (e.g., "2 hours ago")
function format_date_relative($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        return date('F j, Y', $timestamp);
    }
}