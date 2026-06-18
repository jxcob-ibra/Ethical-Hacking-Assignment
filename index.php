<?php
/**
 * MyEduConnect - Home Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';

// Get featured courses
$featuredCourses = getAllCourses('published');
$featuredCourses = array_slice($featuredCourses, 0, 6);

// Get announcements
$announcements = getAnnouncements(3);

// Get platform statistics
$stats = getPlatformStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link active" href="<?php echo APP_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/courses.php">Courses</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Welcome to <?php echo APP_NAME; ?></h1>
                    <p>Your gateway to quality education. Learn from industry experts, access comprehensive courses, and advance your career with our cutting-edge learning platform.</p>
                    <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-light btn-lg">Browse Courses</a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-outline-light btn-lg ms-2">Get Started</a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-book" style="font-size: 15rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success text-center">
                        <div class="stat-icon"><i class="bi bi-book-fill"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_courses']); ?></div>
                        <div class="stat-label">Courses</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning text-center">
                        <div class="stat-icon"><i class="bi bi-person-check-fill"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_enrollments']); ?></div>
                        <div class="stat-label">Enrollments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info text-center">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-value"><?php echo number_format($stats['total_revenue']); ?></div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Why Choose Us?</h2>
                <p class="text-muted">Discover the benefits of learning with our platform</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-laptop" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Learn Anywhere</h4>
                            <p class="card-text">Access courses from any device, anytime, anywhere. Flexible learning that fits your schedule.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-award-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Expert Instructors</h4>
                            <p class="card-text">Learn from industry professionals with years of experience in their respective fields.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-certificate-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Certified Courses</h4>
                            <p class="card-text">Earn certificates upon completion to showcase your skills and knowledge to employers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Featured Courses</h2>
                <p class="text-muted">Explore our most popular courses</p>
            </div>
            <div class="row g-4">
                <?php foreach ($featuredCourses as $course): ?>
                    <div class="col-md-4">
                        <div class="course-card h-100">
                            <div class="course-image">
                                <i class="bi bi-book" style="font-size: 4rem;"></i>
                            </div>
                            <div class="course-info">
                                <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                <p class="course-instructor">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                </p>
                                <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="course-price"><?php echo formatCurrency($course['price']); ?></span>
                                    <a href="<?php echo APP_URL; ?>/course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-primary btn-sm">View Course</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-outline-primary">View All Courses</a>
            </div>
        </div>
    </section>

    <!-- Announcements -->
    <?php if (!empty($announcements)): ?>
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Latest Announcements</h2>
                <p class="text-muted">Stay updated with the latest news and updates</p>
            </div>
            <div class="row">
                <?php foreach ($announcements as $announcement): ?>
                    <div class="col-md-4">
                        <div class="announcement-card priority-<?php echo $announcement['priority']; ?>">
                            <h5 class="announcement-title"><?php
                                if (!isVulnerabilityEnabled('stored_xss')) {
                                    echo htmlspecialchars($announcement['title']);
                                } else {
                                    echo $announcement['title'];
                                }
                            ?></h5>
                            <p class="announcement-date">
                                <i class="bi bi-calendar"></i> <?php echo formatDate($announcement['created_at']); ?>
                            </p>
                            <p><?php
                                $content = $announcement['content'];
                                if (!isVulnerabilityEnabled('stored_xss')) {
                                    $content = htmlspecialchars($content);
                                }
                                echo substr($content, 0, 150);
                            ?>...</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section class="py-5" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); color: white;">
        <div class="container text-center">
            <h2 class="fw-bold mb-3">Ready to Start Learning?</h2>
            <p class="mb-4">Join thousands of students already learning with our platform</p>
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo APP_URL; ?>/register.php" class="btn btn-light btn-lg">Create Free Account</a>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-light btn-lg">Browse Courses</a>
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
