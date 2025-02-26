<?php
// auth/register.php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Validate input
    $errors = [];
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    // Validate password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mini E-Learning</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Enhanced CSS for Register Page */
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
            max-width: 700px;
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
            margin-bottom: 30px;
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
        
        .register-title {
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
        
        .form-control, .form-select {
            border: 2px solid #eaecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            height: auto;
            background-color: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
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
        
        .btn-register {
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
        
        .btn-register:before {
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
        
        .btn-register:hover:before {
            left: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(78, 84, 200, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(1px);
            box-shadow: 0 3px 8px rgba(78, 84, 200, 0.3);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .login-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-link:hover {
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
        
        .alert-icon {
            margin-right: 10px;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        /* Progress bar for password strength */
        .password-strength-meter {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .password-strength-meter .strength-meter {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .strength-weak {
            background-color: #dc3545;
            width: 25% !important;
        }
        
        .strength-medium {
            background-color: #ffc107;
            width: 50% !important;
        }
        
        .strength-good {
            background-color: #17a2b8;
            width: 75% !important;
        }
        
        .strength-strong {
            background-color: #28a745;
            width: 100% !important;
        }
        
        .password-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }
        
        /* Modal styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-bottom: none;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 20px 25px;
        }
        
        .modal-title {
            color: #fff;
            font-weight: 600;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            border-top: none;
            padding: 15px 25px 25px;
        }
        
        .btn-secondary {
            background-color: #f8f9fa;
            color: #555;
            border: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }
        
        .btn-modal-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: #fff;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-modal-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(78, 84, 200, 0.3);
        }
        
        /* Role cards */
        .role-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        
        .role-card {
            flex: 1;
            border: 2px solid #eaecef;
            border-radius: 15px;
            padding: 15px;
            margin: 0 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .role-card:first-child {
            margin-left: 0;
        }
        
        .role-card:last-child {
            margin-right: 0;
        }
        
        .role-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.15);
        }
        
        .role-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(78, 84, 200, 0.05);
        }
        
        .role-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .role-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .role-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .role-radio {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        /* Steps indicator */
        .steps-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        
        .steps-container::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 40px;
            right: 40px;
            height: 2px;
            background-color: #e9ecef;
            z-index: 1;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #fff;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background-color: var(--primary-color);
        }
        
        .step.completed {
            background-color: var(--success-color);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .brand-logo {
                font-size: 2rem;
            }
            
            .brand-slogan {
                font-size: 0.9rem;
            }
            
            .register-title {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 30px 20px;
            }
            
            .role-cards {
                flex-direction: column;
            }
            
            .role-card {
                margin: 8px 0;
            }
            
            .role-card:first-child {
                margin-top: 0;
            }
            
            .role-card:last-child {
                margin-bottom: 0;
            }
        }
        
        @media (max-width: 576px) {
            .form-control, .form-select {
                font-size: 16px; /* Prevents iOS zoom */
            }
            
            .steps-container::before {
                left: 20px;
                right: 20px;
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
            <p class="brand-slogan">Start your learning journey today</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="register-title">Create Your Account</h2>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle alert-icon"></i>
                        <?php foreach($errors as $error): ?>
                            <p class="mb-1"><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" class="form-control form-control-icon" id="username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                       placeholder="Create a username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" class="form-control form-control-icon" id="email" name="email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       placeholder="Your email address" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" class="form-control form-control-icon" id="full_name" name="full_name"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="Your full name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group password-toggle">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control form-control-icon" id="password" name="password" 
                                       placeholder="Create a password" required onkeyup="checkPasswordStrength()">
                                <span class="toggle-icon" onclick="togglePassword('password', 'toggleIcon1')">
                                    <i class="far fa-eye" id="toggleIcon1"></i>
                                </span>
                            </div>
                            <div class="password-strength-meter">
                                <div class="strength-meter" id="passwordStrengthMeter"></div>
                            </div>
                            <span class="password-feedback" id="passwordFeedback">Password should be at least 8 characters long</span>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group password-toggle">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" class="form-control form-control-icon" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required>
                                <span class="toggle-icon" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                    <i class="far fa-eye" id="toggleIcon2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 mb-4">
                        <label class="form-label">Select your role:</label>
                        <div class="role-cards">
                            <label class="role-card" id="studentCard">
                                <input type="radio" name="role" value="student" class="role-radio" 
                                       <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'checked' : ''; ?> 
                                       required>
                                <div class="role-icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="role-title">Student</div>
                                <div class="role-desc">Access courses and learn at your own pace</div>
                            </label>
                            
                            <label class="role-card" id="instructorCard">
                                <input type="radio" name="role" value="instructor" class="role-radio"
                                       <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'checked' : ''; ?>>
                                <div class="role-icon">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="role-title">Instructor</div>
                                <div class="role-desc">Create courses and share your knowledge</div>
                            </label>
                            
                            <label class="role-card" id="adminCard">
                                <input type="radio" name="role" value="admin" class="role-radio"
                                       <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'checked' : ''; ?>>
                                <div class="role-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                                <div class="role-title">Admin</div>
                                <div class="role-desc">Manage the platform and users</div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                    
                    <div class="register-footer">
                        <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing or using the Mini E-Learning platform, you agree to be bound by these Terms and Conditions.</p>
                    
                    <h6>2. User Accounts</h6>
                    <p>You are responsible for maintaining the confidentiality of your account information and password.</p>
                    
                    <h6>3. Code of Conduct</h6>
                    <p>Users must behave respectfully and not engage in any harmful activities on the platform.</p>
                    
                    <h6>4. Intellectual Property</h6>
                    <p>All content provided on the platform is protected by copyright and other intellectual property laws.</p>
                    
                    <h6>5. Privacy Policy</h6>
                    <p>Your use of the platform is also governed by our Privacy Policy.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-modal-primary" id="agreeButton" data-bs-dismiss="modal">I Agree</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Password Toggle
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
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
        
        // Password Strength Checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const meter = document.getElementById('passwordStrengthMeter');
            const feedback = document.getElementById('passwordFeedback');
            
            // Remove all classes
            meter.classList.remove('strength-weak', 'strength-medium', 'strength-good', 'strength-strong');
            
            if (password.length === 0) {
                meter.style.width = '0%';
                feedback.textContent = 'Password should be at least 8 characters long';
                return;
            }
            
            // Check strength
            let strength = 0;
            
            // Length check
            if (password.length > 7) strength += 1;
            if (password.length > 10) strength += 1;
            
            // Character type checks
            if (/[A-Z]/.test(password)) strength += 1; // Has uppercase
            if (/[a-z]/.test(password)) strength += 1; // Has lowercase
            if (/[0-9]/.test(password)) strength += 1; // Has number
            if (/[^A-Za-z0-9]/.test(password)) strength += 1; // Has special char
            
            // Set meter and feedback based on strength
            if (strength < 2) {
                meter.classList.add('strength-weak');
                feedback.textContent = 'Weak password';
                feedback.style.color = '#dc3545';
            } else if (strength < 4) {
                meter.classList.add('strength-medium');
                feedback.textContent = 'Medium strength password';
                feedback.style.color = '#ffc107';
            } else if (strength < 6) {
                meter.classList.add('strength-good');
                feedback.textContent = 'Good password';
                feedback.style.color = '#17a2b8';
            } else {
                meter.classList.add('strength-strong');
                feedback.textContent = 'Strong password';
                feedback.style.color = '#28a745';
            }
        }
        
        // Role Card Selection
        const roleCards = document.querySelectorAll('.role-card');
        roleCards.forEach(card => {
            const radio = card.querySelector('.role-radio');
            
            // Set initial state
            if (radio.checked) {
                card.classList.add('selected');
            }
            
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                roleCards.forEach(c => c.classList.remove('selected'));
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // Check the radio button
                radio.checked = true;
            });
        });
        
        // Agree to Terms Button
        document.getElementById('agreeButton').addEventListener('click', function() {
            document.getElementById('terms').checked = true;
        });
        
        // Form Validation
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const termsCheckbox = document.getElementById('terms');
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                event.preventDefault();
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                event.preventDefault();
                return;
            }
            
            if (!termsCheckbox.checked) {
                alert('You must agree to the Terms and Conditions');
                event.preventDefault();
                return;
            }
        });
        
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