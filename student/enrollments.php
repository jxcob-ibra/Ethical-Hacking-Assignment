<?php
/**
 * MyEduConnect - Student Enrollments Page
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link active" href="enrollments.php">
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
                <h2 class="fw-bold mb-4">My Enrolled Courses</h2>

                <?php if (!empty($enrolledCourses)): ?>
                    <div class="row g-4">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-<?php echo $course['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Progress</small>
                                                <small><?php echo $course['progress']; ?>%</small>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $course['progress']; ?>%"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> Enrolled: <?php echo formatDate($course['enrollment_date']); ?>
                                            </small>
                                            <a href="course-materials.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="bi bi-folder"></i> View Materials
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-book" style="font-size: 5rem; color: var(--gray-color);"></i>
                        <h3 class="mt-3">No Courses Enrolled</h3>
                        <p class="text-muted">You haven't enrolled in any courses yet. Start learning today!</p>
                        <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-primary btn-lg">Browse Courses</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
