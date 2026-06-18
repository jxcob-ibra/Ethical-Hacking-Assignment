<?php
/**
 * MyEduConnect - Teacher Students Page
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require teacher login
requireRole('teacher');
checkSessionTimeout();

// Get teacher information
$teacher = getTeacherByUserId(getCurrentUserId());

// Get all students enrolled in teacher's courses
$students = dbSelect("
    SELECT DISTINCT s.*, u.email, u.first_name, u.last_name, COUNT(e.enrollment_id) as enrolled_courses
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    JOIN enrollments e ON s.student_id = e.student_id
    JOIN courses c ON e.course_id = c.course_id
    WHERE c.instructor_id = ?
    GROUP BY s.student_id
    ORDER BY u.last_name, u.first_name
", [$teacher['teacher_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?php echo APP_NAME; ?></title>
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
                            <li><a class="dropdown-item" href="courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="students.php">Students</a></li>
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
                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1)) . strtoupper(substr($teacher['last_name'], 0, 1)); ?>
                        </div>
                        <h6 class="mt-2"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($teacher['teacher_id_number']); ?></small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> My Courses
                    </a>
                    <a class="nav-link" href="create-course.php">
                        <i class="bi bi-plus-circle"></i> Create Course
                    </a>
                    <a class="nav-link active" href="students.php">
                        <i class="bi bi-people"></i> Students
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <h2 class="fw-bold mb-4">My Students</h2>

                <!-- Statistics -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                            <div class="stat-value"><?php echo count($students); ?></div>
                            <div class="stat-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                            <div class="stat-value"><?php echo count(getCoursesByTeacher($teacher['teacher_id'])); ?></div>
                            <div class="stat-label">Active Courses</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="stat-value"><?php echo array_sum(array_column($students, 'enrolled_courses')); ?></div>
                            <div class="stat-label">Total Enrollments</div>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Student List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($students)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Student ID</th>
                                            <th>Email</th>
                                            <th>Grade Level</th>
                                            <th>Enrolled Courses</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['student_id_number']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['grade_level'] ?? 'N/A'); ?></td>
                                                <td><?php echo $student['enrolled_courses']; ?></td>
                                                <td>
                                                    <a href="student-details.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-people" style="font-size: 5rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Students Found</h4>
                                <p class="text-muted">No students are enrolled in your courses yet.</p>
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
