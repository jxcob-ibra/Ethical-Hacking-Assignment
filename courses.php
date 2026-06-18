<?php
/**
 * MyEduConnect - Course Catalog Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';

// Get search keyword
$keyword = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Get courses
if (!empty($keyword)) {
    $courses = searchCourses($keyword);
} elseif (!empty($category)) {
    $courses = dbSelect("SELECT c.*, t.first_name, t.last_name, CONCAT(t.first_name, ' ', t.last_name) as instructor_name 
                        FROM courses c 
                        JOIN teachers t ON c.instructor_id = t.teacher_id 
                        WHERE c.status = 'published' AND c.category = ? 
                        ORDER BY c.created_at DESC", [$category]);
} else {
    $courses = getAllCourses('published');
}

// Get unique categories
$categories = dbSelect("SELECT DISTINCT category FROM courses WHERE status = 'published' AND category IS NOT NULL ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link active" href="<?php echo APP_URL; ?>/courses.php">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/faq.php">FAQ</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isStudent()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/student/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif (isTeacher()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/teacher/dashboard.php">Dashboard</a>
                            </li>
                        <?php elseif (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo APP_URL; ?>/admin/dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="<?php echo APP_URL; ?>/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="py-5" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
        <div class="container">
            <h1 class="fw-bold">Course Catalog</h1>
            <p class="mb-0">Explore our comprehensive range of courses</p>
        </div>
    </section>

    <!-- Search and Filter -->
    <section class="py-4 bg-white">
        <div class="container">
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search courses..." 
                                   value="<?php echo htmlspecialchars($keyword); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                        <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Courses Grid -->
    <section class="py-5">
        <div class="container">
            <?php if (!empty($courses)): ?>
                <div class="row g-4">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-md-4">
                            <div class="course-card h-100">
                                <div class="course-image">
                                    <i class="bi bi-book" style="font-size: 4rem;"></i>
                                </div>
                                <div class="course-info">
                                    <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($course['category']); ?></span>
                                    <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="course-instructor">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </p>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="course-price"><?php echo formatCurrency($course['price']); ?></span>
                                        <small class="text-muted">
                                            <i class="bi bi-people"></i> <?php echo $course['enrollment_count']; ?> enrolled
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo APP_URL; ?>/course.php?id=<?php echo $course['course_id']; ?>" 
                                           class="btn btn-primary flex-grow-1">View Course</a>
                                        <?php if (isLoggedIn() && isStudent()): ?>
                                            <?php 
                                            $student = getStudentByUserId(getCurrentUserId());
                                            $enrolled = dbSelectOne("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?", 
                                                [$student['student_id'], $course['course_id']]);
                                            ?>
                                            <?php if (!$enrolled): ?>
                                                <a href="<?php echo APP_URL; ?>/student/enroll.php?course_id=<?php echo $course['course_id']; ?>" 
                                                   class="btn btn-success">Enroll</a>
                                            <?php else: ?>
                                                <span class="badge bg-success">Enrolled</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-search" style="font-size: 4rem; color: var(--gray-color);"></i>
                    <h3 class="mt-3">No Courses Found</h3>
                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                    <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-primary">View All Courses</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><?php echo APP_NAME; ?></h5>
                    <p>Your gateway to quality education. Learn from industry experts and advance your career.</p>
                </div>
                <div class="col-md-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/courses.php">Courses</a></li>
                        <li><a href="<?php echo APP_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo APP_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo APP_URL; ?>/faq.php">FAQ</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p><i class="bi bi-geo-alt"></i> 123 Education Street, Learning City</p>
                    <p><i class="bi bi-envelope"></i> info@myeduconnect.com</p>
                    <p><i class="bi bi-telephone"></i> +1 (555) 123-4567</p>
                </div>
            </div>
            <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
