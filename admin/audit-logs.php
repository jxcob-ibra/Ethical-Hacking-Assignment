<?php
/**
 * MyEduConnect - Admin Audit Logs Page
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

// Get filter parameters
$actionFilter = $_GET['action'] ?? '';
$tableFilter = $_GET['table'] ?? '';

// Build query
$query = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if ($actionFilter) {
    $query .= " AND action LIKE ?";
    $params[] = "%$actionFilter%";
}

if ($tableFilter) {
    $query .= " AND table_name = ?";
    $params[] = $tableFilter;
}

$query .= " ORDER BY created_at DESC LIMIT 100";

$auditLogs = dbSelect($query, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-credit-card"></i> Payments
                    </a>
                    <a class="nav-link" href="announcements.php">
                        <i class="bi bi-megaphone"></i> Announcements
                    </a>
                    <a class="nav-link active" href="audit-logs.php">
                        <i class="bi bi-journal-text"></i> Audit Logs
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <h2 class="fw-bold mb-4">Audit Logs</h2>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Action</span>
                                        <input type="text" class="form-control" name="action" placeholder="Search action..." 
                                               value="<?php echo htmlspecialchars($actionFilter); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Table</span>
                                        <select class="form-select" name="table">
                                            <option value="">All Tables</option>
                                            <option value="users" <?php echo $tableFilter === 'users' ? 'selected' : ''; ?>>Users</option>
                                            <option value="courses" <?php echo $tableFilter === 'courses' ? 'selected' : ''; ?>>Courses</option>
                                            <option value="enrollments" <?php echo $tableFilter === 'enrollments' ? 'selected' : ''; ?>>Enrollments</option>
                                            <option value="payments" <?php echo $tableFilter === 'payments' ? 'selected' : ''; ?>>Payments</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Audit Logs Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($auditLogs)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 5%;" class="text-center">ID</th>
                                            <th style="width: 25%;">Action</th>
                                            <th style="width: 8%;">User Type</th>
                                            <th style="width: 10%;">Table</th>
                                            <th style="width: 15%;" class="text-center">Record ID</th>
                                            <th style="width: 10%;">IP Address</th>
                                            <th style="width: 10%;">Date</th>
                                            <th style="width: 20%;">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($auditLogs as $log): ?>
                                            <tr>
                                                <td class="text-center"><?php echo $log['log_id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['user_type'] ?? 'System'); ?></span></td>
                                                <td><span class="badge bg-info"><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></span></td>
                                                <td class="text-center"><?php echo $log['record_id'] ?? 'N/A'; ?></td>
                                                <td><code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                                                <td><?php echo formatDate($log['created_at']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?php echo $log['log_id']; ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="collapse" id="details-<?php echo $log['log_id']; ?>">
                                                <td colspan="8" class="bg-light">
                                                    <div class="p-3">
                                                        <h6 class="fw-bold mb-2">Old Values:</h6>
                                                        <pre class="bg-white p-2 rounded border" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;"><?php echo htmlspecialchars($log['old_values'] ?? 'N/A'); ?></pre>
                                                        <h6 class="fw-bold mb-2 mt-3">New Values:</h6>
                                                        <pre class="bg-white p-2 rounded border" style="max-height: 200px; overflow-y: auto; font-size: 0.85rem;"><?php echo htmlspecialchars($log['new_values'] ?? 'N/A'); ?></pre>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-journal-text" style="font-size: 5rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Audit Logs Found</h4>
                                <p class="text-muted">No audit logs match the current filter criteria.</p>
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
