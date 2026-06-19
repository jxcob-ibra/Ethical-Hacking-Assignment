<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('admin');
checkSessionTimeout();

$definitions = [
    'sql_injection' => 'SQL Injection',
    'stored_xss' => 'Stored XSS',
    'idor' => 'IDOR',
    'weak_ssh_credentials' => 'Weak SSH Credentials',
    'backup_file_exposure' => 'Backup File Exposure',
    'weak_password_hashing' => 'Weak Password Hashing',
    'http_api_communication' => 'HTTP API Communication',
    'weak_file_permissions' => 'Weak File Permissions',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($definitions) as $name) {
        if (isset($_POST[$name])) {
            enableVulnerability($name);
        } else {
            disableVulnerability($name);
        }
    }
    redirect('security-settings.php', 'Security Vulnerability Manager updated.', 'success');
}

$settings = dbSelect("SELECT * FROM security_settings ORDER BY id ASC");
$byName = [];
foreach ($settings as $row) {
    $byName[$row['vulnerability_name']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Vulnerability Manager - <?php echo APP_NAME; ?></title>
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
                            <li><a class="dropdown-item active" href="security-settings.php">Security Settings</a></li>
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
                        <h6 class="mt-2">Administrator</h6>
                        <small class="text-muted">Security Control</small>
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
                    <a class="nav-link" href="audit-logs.php">
                        <i class="bi bi-journal-text"></i> Audit Logs
                    </a>
                    <a class="nav-link active" href="security-settings.php">
                        <i class="bi bi-shield-lock"></i> Security Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <h2 class="fw-bold mb-4">Security Vulnerability Manager</h2>
                <?php $flash = getFlashMessage(); ?>
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Note:</strong> Each toggle switches between an actual vulnerable implementation and an actual secure implementation.
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Toggle Vulnerabilities</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <?php foreach ($definitions as $key => $label): ?>
                                    <div class="col-12 mb-3">
                                        <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                                            <label class="fw-bold mb-0" for="<?php echo $key; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </label>
                                            <div class="form-check form-switch ms-3">
                                                <input class="form-check-input" type="checkbox" name="<?php echo $key; ?>" id="<?php echo $key; ?>" <?php echo !empty($byName[$key]['enabled']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Security Settings
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lab Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="d-flex">
                                    <strong class="me-2" style="min-width: 200px;">API Base URL:</strong>
                                    <code class="flex-grow-1"><?php echo htmlspecialchars(getApiBaseUrl()); ?></code>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex">
                                    <strong class="me-2" style="min-width: 200px;">Weak SSH scripts:</strong>
                                    <span class="flex-grow-1">
                                        <code>scripts/enable_weak_ssh.sh</code> and <code>scripts/disable_weak_ssh.sh</code>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex">
                                    <strong class="me-2" style="min-width: 200px;">Backup URL (enabled mode):</strong>
                                    <code class="flex-grow-1"><?php echo APP_URL; ?>/backups/backup.sql</code>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex">
                                    <strong class="me-2" style="min-width: 200px;">Database Exposure URL (enabled mode):</strong>
                                    <code class="flex-grow-1"><?php echo APP_URL; ?>/admin/user-database-exposure.php</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">File Permissions Status</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $fileStatus = getFilePermissionsStatus(); 
                        $hasErrors = false;
                        foreach ($fileStatus as $status) {
                            if (!$status['matches_expected']) {
                                $hasErrors = true;
                                break;
                            }
                        }
                        ?>
                        
                        <?php if (isset($_SESSION['file_permissions_error'])): ?>
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($_SESSION['file_permissions_error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['file_permissions_error']); ?>
                        <?php endif; ?>
                        
                        <?php if ($hasErrors): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Warning:</strong> Some files do not match their expected security state. This may be due to Docker/Windows filesystem restrictions.
                            </div>
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <?php foreach ($fileStatus as $fileName => $status): ?>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded <?php echo !$status['matches_expected'] ? 'border-warning' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong><?php echo htmlspecialchars($fileName); ?></strong>
                                            <?php if ($status['exists']): ?>
                                                <?php if ($status['is_vulnerable']): ?>
                                                    <span class="badge bg-danger">VULNERABLE</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">SECURE</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">NOT FOUND</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            Permissions: <code><?php echo htmlspecialchars($status['permissions']); ?></code>
                                            <?php if (!$status['matches_expected']): ?>
                                                <span class="text-warning">(Expected: <?php echo htmlspecialchars($status['expected_current']); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted">
                                            Readable: <i class="bi bi-<?php echo $status['readable'] ? 'check text-success' : 'x text-danger'; ?>"></i>
                                            | Writable: <i class="bi bi-<?php echo $status['writable'] ? 'check text-success' : 'x text-danger'; ?>"></i>
                                        </div>
                                        <?php if ($status['error']): ?>
                                            <div class="small text-warning mt-1">
                                                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($status['error']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="test-file-permissions.php" class="btn btn-info btn-sm">
                                <i class="bi bi-gear"></i> Test File Permissions
                            </a>
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