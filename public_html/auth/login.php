<?php
// auth/login.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT user_id, username, password, role FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                switch($user['role']) {
                    case 'admin':
                        header("Location: ../admin/index.php");
                        break;
                    case 'instructor':
                        header("Location: ../instructor/index.php");
                        break;
                    case 'student':
                        header("Location: ../student/index.php");
                        break;
                }
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Username not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini E-Learning</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced CSS for Login Page */
        :root {
            --primary-color: #4e54c8;
            --secondary-color: #8f94fb;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --text-primary: #333;
            --text-secondary: #555;
            --card-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            --btn-shadow: 0 4px 6px rgba(78, 84, 200, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(-45deg, #8f94fb, #4e54c8, #5a67d8, #4e54c8);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s infinite ease-in-out;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
        
        .container {
            width: 100%;
            max-width: 450px;
            padding: 0 20px;
        }
        
        .brand-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand-logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .brand-slogan {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            font-weight: 300;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: card-appear 0.8s forwards;
        }
        
        @keyframes card-appear {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 20px;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -75px;
            right: -75px;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }
        
        .login-title {
            color: #fff;
            text-align: center;
            font-weight: 600;
            font-size: 1.8rem;
            margin: 0;
            position: relative;
            z-index: 10;
        }
        
        .card-body {
            padding: 40px 30px;
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #eaecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            height: auto;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(78, 84, 200, 0.1);
            background-color: #fff;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 15px;
            color: #7c83db;
            font-size: 1.1rem;
            z-index: 10;
        }
        
        .form-control-icon {
            padding-left: 45px;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .toggle-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .toggle-icon:hover {
            color: var(--primary-color);
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0.2rem;
            cursor: pointer;
            border: 2px solid #d1d5db;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-label {
            padding-left: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .forgot-password {
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .forgot-password:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: var(--btn-shadow);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
            z-index: -1;
        }
        
        .btn-login:hover:before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(78, 84, 200, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(1px);
            box-shadow: 0 3px 8px rgba(78, 84, 200, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .register-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .register-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            font-size: 0.95rem;
            animation: alert-appear 0.3s forwards;
        }
        
        @keyframes alert-appear {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: var(--danger-color);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.1);
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: var(--success-color);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.1);
        }
        
        .alert-icon {
            margin-right: 10px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .brand-logo {
                font-size: 2rem;
            }
            
            .brand-slogan {
                font-size: 0.9rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 30px 20px;
            }
            
            .form-control {
                font-size: 16px; /* Prevents iOS zoom */
            }
        }
    </style>
</head>
<body>
    <!-- Particle Background Effect -->
    <div class="particles" id="particles">
        <!-- Particles will be created by JavaScript -->
    </div>

    <div class="container">
        <div class="brand-container">
            <h1 class="brand-logo">Mini E-Learning</h1>
            <p class="brand-slogan">Expand your knowledge with us</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="login-title">Welcome Back</h2>
            </div>
            
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle alert-icon"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle alert-icon"></i>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" class="form-control form-control-icon" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Enter your username" required>
                    </div>
                    
                    <div class="input-group password-toggle">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control form-control-icon" id="password" name="password" 
                               placeholder="Enter your password" required>
                        <span class="toggle-icon" onclick="togglePassword()">
                            <i class="far fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                    
                    <div class="login-footer">
                        <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Password Toggle
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Create particles
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20; // Number of particles
            
            for (let i = 0; i < particleCount; i++) {
                let particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size between 10px and 30px
                const size = Math.random() * 20 + 10;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                
                // Random animation duration and delay
                const duration = Math.random() * 6 + 4; // 4-10 seconds
                const delay = Math.random() * 5; // 0-5 seconds delay
                particle.style.animation = `float ${duration}s infinite ease-in-out ${delay}s`;
                
                // Add to container
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>