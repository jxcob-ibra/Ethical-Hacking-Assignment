<?php
/**
 * MyEduConnect - Admin Announcements Page
 * Learning Management System
 */
//hamza test

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require admin login
requireRole('admin');
checkSessionTimeout();

// Get admin information
$admin = getAdminByUserId(getCurrentUserId());

// Get all announcements
$announcements = dbSelect("SELECT * FROM announcements ORDER BY created_at DESC");

$error = '';
$success = '';

// Handle announcement creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (!isVulnerabilityEnabled('stored_xss'))
        {
            $title = sanitize($_POST['title'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
        }
        else
        {
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
        }
        $targetAudience = sanitize($_POST['target_audience'] ?? 'all');
        $priority = sanitize($_POST['priority'] ?? 'medium');
        $status = sanitize($_POST['status'] ?? 'published');
        
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required.';
        } else {
            try {
                $query = "INSERT INTO announcements (title, content, author_id, author_type, target_audience, priority, status) 
                          VALUES (?, ?, ?, 'admin', ?, ?, ?)";
                dbInsert($query, [
                    $title,
                    $content,
                    $admin['admin_id'],
                    $targetAudience,
                    $priority,
                    $status
                ]);
                
                // Log audit
                logAudit('CREATE_ANNOUNCEMENT', 'announcements', null);
                
                redirect('announcements.php', 'Announcement created successfully!', 'success');
                
            } catch (Exception $e) {
                $error = 'Failed to create announcement: ' . $e->getMessage();
            }
        }
    }
}

// Handle announcement deletion
if (isset($_GET['delete_announcement'])) {
    $announcementId = intval($_GET['delete_announcement']);
    try {
        dbDelete("DELETE FROM announcements WHERE announcement_id = ?", [$announcementId]);
        logAudit('DELETE_ANNOUNCEMENT', 'announcements', $announcementId);
        redirect('announcements.php', 'Announcement deleted successfully!', 'success');
    } catch (Exception $e) {
        redirect('announcements.php', 'Failed to delete announcement: ' . $e->getMessage(), 'error');
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="announcements.php">
                        <i class="bi bi-megaphone"></i> Announcements
                    </a>
                    <a class="nav-link" href="audit-logs.php">
                        <i class="bi bi-journal-text"></i> Audit Logs
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h2 class="fw-bold mb-4">Announcement Management</h2>

                <!-- Create Announcement Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Create New Announcement</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="Enter announcement title">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">Content *</label>
                                <textarea class="form-control" id="content" name="content" rows="5" required 
                                          placeholder="Enter announcement content"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="target_audience" class="form-label">Target Audience</label>
                                    <select class="form-select" id="target_audience" name="target_audience">
                                        <option value="all">All Users</option>
                                        <option value="students">Students Only</option>
                                        <option value="teachers">Teachers Only</option>
                                        <option value="specific_course">Specific Course</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft">Draft</option>
                                        <option value="published" selected>Published</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-megaphone"></i> Create Announcement
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Announcements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card priority-<?php echo $announcement['priority']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="announcement-title">
                                                <?php
                                                if (!isVulnerabilityEnabled('stored_xss'))
                                                {
                                                    echo htmlspecialchars($announcement['title']);
                                                }
                                                else
                                                {
                                                    echo $announcement['title'];
                                                }
                                                ?>
                                             </h5>
                                            <p class="announcement-date">
                                                <i class="bi bi-calendar"></i> <?php echo formatDate($announcement['created_at']); ?>
                                                | <i class="bi bi-person"></i> <?php echo ucfirst($announcement['author_type']); ?>
                                                | <i class="bi bi-bullseye"></i> <?php echo ucfirst($announcement['target_audience']); ?>
                                            </p>
                                            <p>
                                                <?php
                                                if (!isVulnerabilityEnabled('stored_xss'))
                                                {
                                                    echo htmlspecialchars($announcement['content']);
                                                }
                                                else
                                                {
                                                    echo $announcement['content'];
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-<?php echo $announcement['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($announcement['status']); ?>
                                            </span>
                                            <a href="announcements.php?delete_announcement=<?php echo $announcement['announcement_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this announcement?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-megaphone" style="font-size: 5rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Announcements</h4>
                                <p class="text-muted">No announcements have been created yet.</p>
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
