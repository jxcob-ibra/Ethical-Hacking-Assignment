<?php
/**
 * MyEduConnect - Admin Payments Page
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

// Get all payments
$payments = dbSelect("SELECT p.*, c.title as course_title, s.student_id_number, u.first_name, u.last_name 
                    FROM payments p 
                    LEFT JOIN courses c ON p.course_id = c.course_id 
                    LEFT JOIN students s ON p.student_id = s.student_id 
                    LEFT JOIN users u ON s.user_id = u.user_id 
                    ORDER BY p.payment_date DESC");

// Calculate totals
$totalRevenue = array_sum(array_column($payments, 'amount'));
$completedPayments = count(array_filter($payments, function($p) { return $p['status'] === 'completed'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> Courses
                    </a>
                    <a class="nav-link active" href="payments.php">
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
                <h2 class="fw-bold mb-4">Payment Management</h2>

                <!-- Summary Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-credit-card-fill"></i></div>
                            <div class="stat-value"><?php echo count($payments); ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                            <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="stat-value"><?php echo $completedPayments; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($payments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Student</th>
                                            <th>Course</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($payment['transaction_id']); ?></code></td>
                                                <td>
                                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($payment['student_id_number']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['course_title'] ?? 'N/A'); ?></td>
                                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-credit-card" style="font-size: 5rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Payments Found</h4>
                                <p class="text-muted">No payment transactions have been recorded yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
