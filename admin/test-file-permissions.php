<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('admin');
checkSessionTimeout();

$vulnerabilityEnabled = isVulnerabilityEnabled('weak_file_permissions');
$fileStatus = getFilePermissionsStatus();

$testResults = [];
$beforeFix = [];
$afterFix = [];

// Perform permission tests
foreach ($fileStatus as $fileName => $status) {
    if (!$status['exists']) {
        continue;
    }
    
    $filePath = $status['path'];
    $perms = $status['permissions'];
    $isVulnerable = $status['is_vulnerable'];
    $readable = $status['readable'];
    $writable = $status['writable'];
    $expectedCurrent = $status['expected_current'];
    $matchesExpected = $status['matches_expected'];
    
    // Test 1: Check if file is readable by others
    $readableByOthers = ($perms == '0666' || $perms == '0777' || substr($perms, -1) >= '4');
    
    // Test 2: Check if file is writable by others
    $writableByOthers = ($perms == '0666' || $perms == '0777' || substr($perms, -1) >= '6');
    
    // Test 3: Check if file is executable by others
    $executableByOthers = ($perms == '0777' || substr($perms, -1) == '7' || substr($perms, -1) == '5' || substr($perms, -1) == '1');
    
    $testResults[$fileName] = [
        'permissions' => $perms,
        'is_vulnerable' => $isVulnerable,
        'readable_by_others' => $readableByOthers,
        'writable_by_others' => $writableByOthers,
        'executable_by_others' => $executableByOthers,
        'security_status' => $isVulnerable ? 'VULNERABLE' : 'SECURE',
        'risk_level' => $isVulnerable ? 'HIGH' : 'LOW',
        'readable' => $readable,
        'writable' => $writable,
        'expected_current' => $expectedCurrent,
        'matches_expected' => $matchesExpected
    ];
    
    // Store before/fix data
    $beforeFix[$fileName] = [
        'permissions' => $perms,
        'status' => $isVulnerable ? 'VULNERABLE' : 'SECURE',
        'can_read' => $readableByOthers ? 'YES' : 'NO',
        'can_write' => $writableByOthers ? 'YES' : 'NO',
    ];
    
    // Simulate after fix (what it would be if toggled)
    $afterFix[$fileName] = [
        'permissions' => $isVulnerable ? '0640' : '0666',
        'status' => $isVulnerable ? 'SECURE' : 'VULNERABLE',
        'can_read' => $isVulnerable ? 'NO' : 'YES',
        'can_write' => $isVulnerable ? 'NO' : 'YES',
    ];
}

// Get recent log entries
$logFile = dirname(__DIR__) . '/storage/file_permissions.log';
$recentLogs = [];
if (file_exists($logFile)) {
    $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $recentLogs = array_slice(array_reverse($logLines), 0, 10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Permissions Test - <?php echo APP_NAME; ?></title>
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
                            <li><a class="dropdown-item" href="security-settings.php">Security Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="fw-bold mb-4">
                    <i class="bi bi-shield-lock"></i> File Permissions Vulnerability Test
                </h1>
                
                <div class="alert alert-<?php echo $vulnerabilityEnabled ? 'danger' : 'success'; ?>">
                    <i class="bi bi-<?php echo $vulnerabilityEnabled ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                    <strong>Current Mode:</strong> <?php echo $vulnerabilityEnabled ? 'VULNERABLE (Security OFF)' : 'SECURE (Security ON)'; ?>
                </div>

                <!-- Test Results -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current File Permissions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Permissions</th>
                                        <th>Security Status</th>
                                        <th>Readable by Others</th>
                                        <th>Writable by Others</th>
                                        <th>Risk Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testResults as $fileName => $result): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fileName); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($result['permissions']); ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php echo $result['is_vulnerable'] ? 'danger' : 'success'; ?>">
                                                    <?php echo htmlspecialchars($result['security_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($result['readable_by_others']): ?>
                                                    <span class="text-danger"><i class="bi bi-check"></i> YES</span>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="bi bi-x"></i> NO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($result['writable_by_others']): ?>
                                                    <span class="text-danger"><i class="bi bi-check"></i> YES</span>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="bi bi-x"></i> NO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $result['risk_level'] == 'HIGH' ? 'danger' : 'success'; ?>">
                                                    <?php echo htmlspecialchars($result['risk_level']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Before/After Comparison -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">BEFORE FIX / AFTER FIX Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-danger fw-bold">BEFORE FIX (Current State)</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Permissions</th>
                                            <th>Status</th>
                                            <th>Read</th>
                                            <th>Write</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($beforeFix as $fileName => $data): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fileName); ?></td>
                                                <td><code><?php echo htmlspecialchars($data['permissions']); ?></code></td>
                                                <td class="<?php echo $data['status'] == 'VULNERABLE' ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo htmlspecialchars($data['status']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['can_read']); ?></td>
                                                <td><?php echo htmlspecialchars($data['can_write']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success fw-bold">AFTER FIX (After Toggle)</h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Permissions</th>
                                            <th>Status</th>
                                            <th>Read</th>
                                            <th>Write</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($afterFix as $fileName => $data): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($fileName); ?></td>
                                                <td><code><?php echo htmlspecialchars($data['permissions']); ?></code></td>
                                                <td class="<?php echo $data['status'] == 'VULNERABLE' ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo htmlspecialchars($data['status']); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($data['can_read']); ?></td>
                                                <td><?php echo htmlspecialchars($data['can_write']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Command Reference -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Docker Container Commands for Verification</h5>
                    </div>
                    <div class="card-body">
                        <h6>Enter the Docker container:</h6>
                        <pre><code>docker exec -it myeduconnect-web bash</code></pre>
                        
                        <h6 class="mt-3">Check file permissions:</h6>
                        <pre><code>ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv</code></pre>
                        
                        <h6 class="mt-3">View detailed file stats:</h6>
                        <pre><code>stat /var/www/html/storage/backups/backup.sql
stat /var/www/html/storage/student_records.csv</code></pre>
                        
                        <h6 class="mt-3">Read file content (if vulnerable):</h6>
                        <pre><code>cat /var/www/html/storage/backups/backup.sql
cat /var/www/html/storage/student_records.csv</code></pre>
                        
                        <h6 class="mt-3">Attempt to write to file (if vulnerable):</h6>
                        <pre><code>echo "test" >> /var/www/html/storage/backups/backup.sql</code></pre>
                        
                        <h6 class="mt-3">Change permissions manually:</h6>
                        <pre><code>chmod 640 /var/www/html/storage/backups/backup.sql
chmod 640 /var/www/html/storage/student_records.csv</code></pre>
                    </div>
                </div>

                <!-- Recent Logs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Permission Change Logs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentLogs)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tbody>
                                        <?php foreach ($recentLogs as $log): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($log); ?></code></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No recent permission change logs found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Diagnostic Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">File Permissions Diagnostics (Actual Filesystem Values)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Current Owner</th>
                                        <th>Current Group</th>
                                        <th>Current Permission Octal</th>
                                        <th>Symbolic</th>
                                        <th>Readable</th>
                                        <th>Writable</th>
                                        <th>Expected State</th>
                                        <th>Actual State</th>
                                        <th>Pass/Fail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fileStatus as $fileName => $status): ?>
                                        <?php if (!$status['exists']): continue; endif; ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($fileName); ?></strong></td>
                                            <td><code><?php echo htmlspecialchars($status['owner']); ?></code></td>
                                            <td><code><?php echo htmlspecialchars($status['group']); ?></code></td>
                                            <td><code><?php echo htmlspecialchars($status['permissions']); ?></code></td>
                                            <td><code><?php echo htmlspecialchars($status['symbolic']); ?></code></td>
                                            <td>
                                                <?php if ($status['readable']): ?>
                                                    <span class="text-success"><i class="bi bi-check"></i> YES</span>
                                                <?php else: ?>
                                                    <span class="text-danger"><i class="bi bi-x"></i> NO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($status['writable']): ?>
                                                    <span class="text-success"><i class="bi bi-check"></i> YES</span>
                                                <?php else: ?>
                                                    <span class="text-danger"><i class="bi bi-x"></i> NO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($vulnerabilityEnabled): ?>
                                                    <span class="text-danger">VULNERABLE (0666)</span>
                                                <?php else: ?>
                                                    <span class="text-success">SECURE (0640)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($status['is_vulnerable']): ?>
                                                    <span class="text-danger">VULNERABLE</span>
                                                <?php else: ?>
                                                    <span class="text-success">SECURE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($status['matches_expected']): ?>
                                                    <span class="badge bg-success">PASS</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">FAIL</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (isset($_SESSION['file_permissions_error'])): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($_SESSION['file_permissions_error']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-2">
                    <a href="security-settings.php" class="btn btn-primary">
                        <i class="bi bi-gear"></i> Security Settings
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
