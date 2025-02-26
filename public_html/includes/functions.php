<?php
// includes/functions.php

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /auth/login.php");
        exit;
    }
}

// Redirect if not authorized
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: /access-denied.php");
        exit;
    }
}

// Get user details
function getUserDetails($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Upload file function
function uploadFile($file, $destination) {
    $target_dir = "../uploads/" . $destination . "/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ["error" => "File is too large."];
    }
    
    // Allow certain file formats
    $allowed_extensions = ["jpg", "jpeg", "png", "pdf", "doc", "docx", "mp4"];
    if (!in_array($file_extension, $allowed_extensions)) {
        return ["error" => "Sorry, only JPG, JPEG, PNG, PDF, DOC, DOCX & MP4 files are allowed."];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $new_filename];
    } else {
        return ["error" => "Sorry, there was an error uploading your file."];
    }
}

// Calculate course progress
function calculateCourseProgress($user_id, $course_id) {
    global $conn;
    
    // Get total materials
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM materials WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get completed materials
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM material_progress WHERE user_id = ? AND course_id = ? AND completed = 1");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $completed = $stmt->get_result()->fetch_assoc()['completed'];
    
    return $total > 0 ? ($completed / $total) * 100 : 0;
}

// Generate certificate
function generateCertificate($user_id, $course_id) {
    global $conn;
    
    // Check if course is completed
    $progress = calculateCourseProgress($user_id, $course_id);
    if ($progress < 100) {
        return ["error" => "Course not completed yet"];
    }
    
    // Generate unique certificate number
    $certificate_number = 'CERT-' . date('Y') . '-' . uniqid();
    
    // Insert certificate record
    $stmt = $conn->prepare("INSERT INTO certificates (user_id, course_id, certificate_number) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $course_id, $certificate_number);
    
    if ($stmt->execute()) {
        return [
            "success" => true,
            "certificate_number" => $certificate_number,
            "certificate_id" => $stmt->insert_id
        ];
    } else {
        return ["error" => "Failed to generate certificate"];
    }
}