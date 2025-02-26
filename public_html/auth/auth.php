<?php
// auth/auth.php
require_once '../config/database.php'; // Pastiin koneksi database

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $_SESSION['error'] = "Kamu harus login terlebih dahulu!";
        header("Location: ../auth/login.php");
        exit;
    }
}

function requireRole($requiredRole) {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        die("Kamu harus login terlebih dahulu!");
    }

    try {
        $stmt = $GLOBALS['pdo']->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['role'] !== $requiredRole) {
            $_SESSION['error'] = "Akses ditolak! Kamu harus jadi $requiredRole.";
            header("Location: ../auth/login.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error cek role: " . $e->getMessage());
        die("Error database: " . htmlspecialchars($e->getMessage()));
    }
}
?>