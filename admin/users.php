<?php
/**
 * MyEduConnect - Admin Users Page
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
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($roleFilter) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY created_at DESC";

$users = dbSelect($query, $params);

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = intval($_GET['delete_user']);
    try {
        $user = getUserById($userId);
        if ($user && $user['role'] !== 'admin') {
            dbDelete("DELETE FROM users WHERE user_id = ?", [$userId]);
            logAudit('DELETE_USER', 'users', $userId);
            redirect('users.php', 'User deleted successfully!', 'success');
        } else {
            redirect('users.php', 'Cannot delete admin users', 'error');
        }
    } catch (Exception $e) {
        redirect('users.php', 'Failed to delete user: ' . $e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="users.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">User Management</h2>
                    <a href="create-user.php" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Add New User
                    </a>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role">
                                        <option value="">All Roles</option>
                                        <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
                                        <option value="teacher" <?php echo $roleFilter === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">All Status</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($users)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>About Me</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php
                                                    if (!isVulnerabilityEnabled('stored_xss')) {
                                                        echo htmlspecialchars($user['about_me'] ?? '');
                                                    } else {
                                                        echo $user['about_me'] ?? '';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'student' ? 'primary' : ($user['role'] === 'teacher' ? 'warning' : 'dark'); ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($user['created_at']); ?></td>
                                                <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit-user.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <?php if ($user['role'] !== 'admin'): ?>
                                                            <a href="users.php?delete_user=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this user?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people" style="font-size: 5rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Users Found</h4>
                                <p class="text-muted">No users match the current filter criteria.</p>
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
