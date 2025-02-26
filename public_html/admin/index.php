<?php
// admin/index.php
require_once '../includes/header.php';
requireRole('admin');

// Get total counts for dashboard
$query_users = $conn->query("SELECT COUNT(*) as total, role FROM users GROUP BY role");
$users_count = [];
while ($row = $query_users->fetch_assoc()) {
    $users_count[$row['role']] = $row['total'];
}

$total_courses = $conn->query("SELECT COUNT(*) as total FROM courses")->fetch_assoc()['total'];
$total_active_courses = $conn->query("SELECT COUNT(*) as total FROM courses WHERE status = 'published'")->fetch_assoc()['total'];
$total_quizzes = $conn->query("SELECT COUNT(*) as total FROM quizzes")->fetch_assoc()['total'];

// Get recent activities
$recent_users = $conn->query("
    SELECT username, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

$recent_courses = $conn->query("
    SELECT c.title, c.status, u.username as instructor, c.created_at
    FROM courses c
    JOIN users u ON c.instructor_id = u.user_id
    ORDER BY c.created_at DESC
    LIMIT 5
");
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo isset($users_count['student']) ? $users_count['student'] : 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Courses</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_active_courses; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-book fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Instructors</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo isset($users_count['instructor']) ? $users_count['instructor'] : 0; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Quizzes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_quizzes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <!-- Recent Users -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Courses -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Courses</h6>
                <a href="courses.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Instructor</th>
                                <th>Status</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $recent_courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['title']); ?></td>
                                <td><?php echo htmlspecialchars($course['instructor']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>