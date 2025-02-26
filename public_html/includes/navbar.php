<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            Mini E-Learning
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <!-- Admin Navigation -->
                    <?php if (hasRole('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/users.php') !== false) ? 'active' : ''; ?>" href="/admin/users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/courses.php') !== false) ? 'active' : ''; ?>" href="/admin/courses.php">
                                <i class="fas fa-book-open"></i> Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/admin/reports.php') !== false) ? 'active' : ''; ?>" href="/admin/reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    
                    <!-- Instructor Navigation -->
                    <?php elseif (hasRole('instructor')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/instructor/courses/') !== false) ? 'active' : ''; ?>" href="/instructor/courses/">
                                <i class="fas fa-chalkboard-teacher"></i> My Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/instructor/quizzes/') !== false) ? 'active' : ''; ?>" href="/instructor/quizzes/">
                                <i class="fas fa-question-circle"></i> Quizzes
                            </a>
                        </li>
                    
                    <!-- Student Navigation -->
                    <?php elseif (hasRole('student')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/student/courses.php') !== false) ? 'active' : ''; ?>" href="/student/courses.php">
                                <i class="fas fa-graduation-cap"></i> Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/student/quizzes.php') !== false) ? 'active' : ''; ?>" href="/student/quizzes.php">
                                <i class="fas fa-clipboard-list"></i> Quizzes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/student/certificates.php') !== false) ? 'active' : ''; ?>" href="/student/certificates.php">
                                <i class="fas fa-certificate"></i> Certificates
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Common Navigation for all logged-in users -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/forum/') !== false) ? 'active' : ''; ?>" href="/forum/">
                            <i class="fas fa-comments"></i> Forum
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="/profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/settings.php">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-light me-2" href="/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light" href="/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
