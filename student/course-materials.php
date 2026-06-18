<?php
/**
 * MyEduConnect - Student Course Materials Page
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require student login
requireRole('student');
checkSessionTimeout();

// Get course ID
$courseId = $_GET['course_id'] ?? null;

if (!$courseId) {
    redirect('enrollments.php', 'Invalid course', 'error');
}

// Get course information
$course = getCourseById($courseId);

if (!$course) {
    redirect('enrollments.php', 'Course not found', 'error');
}

// Get student information
$student = getStudentByUserId(getCurrentUserId());

// Check if enrolled
$enrollment = dbSelectOne(
    "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?",
    [$student['student_id'], $courseId]
);

if (!$enrollment) {
    redirect('enrollments.php', 'You are not enrolled in this course', 'error');
}

// Get course materials
$materials = getCourseMaterials($courseId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link" href="dashboard.php">
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
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="enrollments.php">My Courses</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['title']); ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($course['title']); ?></h2>
                        <p class="text-muted mb-0">
                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                        </p>
                    </div>
                    <a href="enrollments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Courses
                    </a>
                </div>

                <!-- Progress Bar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Course Progress</span>
                            <span><?php echo $enrollment['progress']; ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Course Materials -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Course Materials</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($materials)): ?>
                            <?php foreach ($materials as $material): ?>
                                <div class="material-item">
                                    <div class="d-flex align-items-center">
                                        <div class="material-icon">
                                            <?php
                                            $icon = 'bi-file-earmark';
                                            switch ($material['material_type']) {
                                                case 'note':
                                                    $icon = 'bi-file-earmark-text';
                                                    break;
                                                case 'assignment':
                                                    $icon = 'bi-file-earmark-check';
                                                    break;
                                                case 'video':
                                                    $icon = 'bi-play-circle';
                                                    break;
                                                case 'resource':
                                                    $icon = 'bi-file-earmark-zip';
                                                    break;
                                            }
                                            ?>
                                            <i class="bi <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="material-info">
                                            <h6 class="material-title"><?php echo htmlspecialchars($material['title']); ?></h6>
                                            <p class="material-meta">
                                                <span class="badge bg-secondary"><?php echo ucfirst($material['material_type']); ?></span>
                                                <span class="ms-2">
                                                    <i class="bi bi-calendar"></i> <?php echo formatDate($material['upload_date']); ?>
                                                </span>
                                                <span class="ms-2">
                                                    <i class="bi bi-hdd"></i> <?php echo number_format($material['file_size'] / 1024, 2); ?> KB
                                                </span>
                                            </p>
                                            <?php if ($material['description']): ?>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($material['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="<?php echo APP_URL . $material['file_path']; ?>" class="btn btn-primary btn-sm" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder" style="font-size: 4rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Materials Available</h4>
                                <p class="text-muted">The instructor hasn't uploaded any materials yet.</p>
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
