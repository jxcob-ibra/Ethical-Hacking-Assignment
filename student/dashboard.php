<?php
/**
 * MyEduConnect - Student Dashboard
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require student login
requireRole('student');
checkSessionTimeout();

// Get student information
$student = getStudentByUserId(getCurrentUserId());
$enrolledCourses = getEnrolledCourses($student['student_id']);
$payments = getPaymentsByStudent($student['student_id']);
$announcements = getAnnouncements(5);

// Calculate student statistics
$totalEnrollments = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, function($c) { return $c['status'] === 'completed'; }));
$totalSpent = array_sum(array_column($payments, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="bi bi-mortarboard-fill"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/courses.php">Courses</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="enrollments.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="payments.php">Payment History</a></li>
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
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1)) . strtoupper(substr($student['last_name'], 0, 1)); ?>
                        </div>
                        <h6 class="mt-2"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($student['student_id_number']); ?></small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="enrollments.php">
                        <i class="bi bi-book"></i> My Courses
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-credit-card"></i> Payment History
                    </a>
                    <a class="nav-link" href="<?php echo APP_URL; ?>/courses.php">
                        <i class="bi bi-search"></i> Browse Courses
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

                <h2 class="fw-bold mb-4">Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>!</h2>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                            <div class="stat-value"><?php echo $totalEnrollments; ?></div>
                            <div class="stat-label">Enrolled Courses</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="stat-value"><?php echo $completedCourses; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                            <div class="stat-value"><?php echo formatCurrency($totalSpent); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                            <div class="stat-value"><?php echo count($payments); ?></div>
                            <div class="stat-label">Transactions</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Enrolled Courses -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">My Enrolled Courses</h5>
                                <a href="enrollments.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($enrolledCourses)): ?>
                                    <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
                                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                            <div style="width: 60px; height: 60px; background: var(--primary-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; margin-right: 1rem;">
                                                <i class="bi bi-book"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-<?php echo $course['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">You haven't enrolled in any courses yet.</p>
                                    <div class="text-center">
                                        <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-primary">Browse Courses</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Announcements</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($announcements)): ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?php echo formatDate($announcement['created_at']); ?>
                                            </small>
                                            <p class="mb-0 mt-2 small"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100); ?>...</p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No announcements at this time.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Payments</h5>
                                <a href="payments.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($payments)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Transaction ID</th>
                                                    <th>Course</th>
                                                    <th>Amount</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($payments, 0, 5) as $payment): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($payment['course_title'] ?? 'N/A'); ?></td>
                                                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                                                        <td><?php echo formatDate($payment['payment_date']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($payment['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">No payment history found.</p>
                                <?php endif; ?>
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
