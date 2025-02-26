<?php
// includes/header.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Viewport meta tag yang benar -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mini E-Learning</title>
    
    <!-- Gunakan CDN terbaru untuk Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    
    <!-- CSS Reset untuk kompatibilitas -->
    <link rel="stylesheet" href="/assets/css/reset.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Inline styles untuk memastikan elemen dasar terlihat dengan benar -->
    <style>
        body {
            padding: 0;
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: #212529;
            background-color: #f8f9fa;
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: hidden !important;
        }
        
        .navbar {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            padding: 0.5rem 1rem;
            width: 100% !important;
        }
        
        .container, .container-fluid {
            width: 100% !important;
            padding-right: 15px !important;
            padding-left: 15px !important;
            margin-right: auto !important;
            margin-left: auto !important;
            max-width: 100% !important;
        }
        
        @media (max-width: 768px) {
            .container, .container-fluid {
                padding-right: 10px !important;
                padding-left: 10px !important;
            }
            
            .row {
                margin-left: -5px !important;
                margin-right: -5px !important;
            }
            
            [class*="col-"] {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
            
            .navbar-collapse {
                background-color: #343a40;
                z-index: 1000;
            }
        }
    </style>

    <!-- Script Bootstrap untuk menjalankan dropdown -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-3">