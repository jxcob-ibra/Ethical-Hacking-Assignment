<?php
/**
 * MyEduConnect - About Us Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo APP_NAME; ?></title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo APP_URL; ?>/about.php">About Us</a>
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
            <h1 class="fw-bold">About Us</h1>
            <p class="mb-0">Learn more about our mission and values</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Our Mission</h2>
                    <p class="lead">At <?php echo APP_NAME; ?>, we believe that quality education should be accessible to everyone. Our mission is to provide a comprehensive learning platform that empowers students to achieve their academic and professional goals.</p>
                    <p>We are committed to creating an engaging and supportive learning environment where students can develop the skills and knowledge they need to succeed in today's competitive world.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-lightbulb" style="font-size: 10rem; color: var(--primary-color);"></i>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-lg-6 order-lg-2">
                    <h2 class="fw-bold mb-4">Our Vision</h2>
                    <p class="lead">To be the leading educational platform that transforms how people learn and grow, making education more accessible, engaging, and effective for learners worldwide.</p>
                    <p>We envision a future where technology and education work together to create personalized learning experiences that adapt to each student's unique needs and learning style.</p>
                </div>
                <div class="col-lg-6 order-lg-1 text-center">
                    <i class="bi bi-rocket" style="font-size: 10rem; color: var(--primary-color);"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Core Values</h2>
                <p class="text-muted">The principles that guide everything we do</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-shield-check" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Integrity</h4>
                            <p class="card-text">We maintain the highest standards of honesty and transparency in all our interactions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-stars" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Excellence</h4>
                            <p class="card-text">We strive for excellence in everything we do, from course content to student support.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="bi bi-heart" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="card-title mt-3">Passion</h4>
                            <p class="card-text">We are passionate about education and dedicated to helping students succeed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Our Team</h2>
                <p class="text-muted">Meet the people behind <?php echo APP_NAME; ?></p>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div style="width: 100px; height: 100px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                JD
                            </div>
                            <h5 class="card-title">John Doe</h5>
                            <p class="card-text text-muted">CEO & Founder</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div style="width: 100px; height: 100px; background: var(--secondary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                JS
                            </div>
                            <h5 class="card-title">Jane Smith</h5>
                            <p class="card-text text-muted">Chief Academic Officer</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div style="width: 100px; height: 100px; background: var(--success-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                MJ
                            </div>
                            <h5 class="card-title">Mike Johnson</h5>
                            <p class="card-text text-muted">Head of Technology</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <div style="width: 100px; height: 100px; background: var(--warning-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                EW
                            </div>
                            <h5 class="card-title">Emily Wilson</h5>
                            <p class="card-text text-muted">Student Success Manager</p>
                        </div>
                    </div>
                </div>
            </div>
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
