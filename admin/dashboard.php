<?php
/**
 * MyEduConnect - Admin Dashboard
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require admin login
requireRole('admin');
checkSessionTimeout();

// Get admin information
$admin = getAdminByUserId(getCurrentUserId());

// Get platform statistics
$stats = getPlatformStatistics();

// Get recent audit logs
$auditLogs = dbSelect("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10");

// Get recent users
$recentUsers = dbSelect("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="bi bi-mortarboard-fill"></i> <?php echo APP_NAME; ?> Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>">View Site</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="users.php">Users</a></li>
                            <li><a class="dropdown-item" href="courses.php">Courses</a></li>
                            <li><a class="dropdown-item" href="payments.php">Payments</a></li>
                            <li><a class="dropdown-item" href="audit-logs.php">Audit Logs</a></li>
                            <li><a class="dropdown-item" href="security-settings.php">Security Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="mb-4">
                    <div class="text-center mb-3">
                        <div style="width: 80px; height: 80px; background: var(--dark-color); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="bi bi-shield-fill"></i>
                        </div>
                        <h6 class="mt-2"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h6>
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> Courses
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-credit-card"></i> Payments
                    </a>
                    <a class="nav-link" href="announcements.php">
                        <i class="bi bi-megaphone"></i> Announcements
                    </a>
                    <a class="nav-link" href="audit-logs.php">
                        <i class="bi bi-journal-text"></i> Audit Logs
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h2 class="fw-bold mb-4">Admin Dashboard</h2>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="bi bi-person-fill"></i></div>
                            <div class="stat-value"><?php echo $stats['total_students']; ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                            <div class="stat-value"><?php echo $stats['total_teachers']; ?></div>
                            <div class="stat-label">Teachers</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card info">
                            <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                            <div class="stat-value"><?php echo $stats['total_courses']; ?></div>
                            <div class="stat-label">Courses</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                            <div class="stat-value"><?php echo $stats['total_enrollments']; ?></div>
                            <div class="stat-label">Enrollments</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                            <div class="stat-value"><?php echo number_format($stats['total_revenue']); ?></div>
                            <div class="stat-label">Revenue</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Users</h5>
                                <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentUsers)): ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                            <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem; margin-right: 1rem;">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php echo $user['role'] === 'student' ? 'primary' : ($user['role'] === 'teacher' ? 'warning' : 'dark'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No users registered yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Audit Logs -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Activity</h5>
                                <a href="audit-logs.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($auditLogs)): ?>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($log['action']); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($log['user_type'] ?? 'System'); ?>
                                                <?php if ($log['table_name']): ?>
                                                    | <i class="bi bi-table"></i> <?php echo htmlspecialchars($log['table_name']); ?>
                                                <?php endif; ?>
                                                | <i class="bi bi-calendar"></i> <?php echo formatDate($log['created_at']); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No recent activity.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <a href="create-user.php" class="btn btn-primary w-100">
                                            <i class="bi bi-person-plus"></i> Add User
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="users.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-people"></i> Manage Users
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="courses.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-book"></i> Manage Courses
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="payments.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-credit-card"></i> View Payments
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="announcements.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-megaphone"></i> Announcements
                                        </a>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="audit-logs.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-journal-text"></i> Audit Logs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
