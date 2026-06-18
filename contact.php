<?php
/**
 * MyEduConnect - Contact Us Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';

$error = '';
$success = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'All fields are required.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            // In a real application, you would send an email here
            // For now, we'll just show a success message
            $success = 'Thank you for your message! We will get back to you soon.';
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link" href="<?php echo APP_URL; ?>/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo APP_URL; ?>/contact.php">Contact</a>
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
            <h1 class="fw-bold">Contact Us</h1>
            <p class="mb-0">Get in touch with our team</p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="fw-bold mb-4">Contact Information</h3>
                            
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-geo-alt" style="font-size: 1.5rem; color: var(--primary-color); margin-right: 1rem;"></i>
                                    <div>
                                        <h6 class="mb-0">Address</h6>
                                        <p class="mb-0 text-muted">123 Education Street, Learning City, LC 12345</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-envelope" style="font-size: 1.5rem; color: var(--primary-color); margin-right: 1rem;"></i>
                                    <div>
                                        <h6 class="mb-0">Email</h6>
                                        <p class="mb-0 text-muted">info@myeduconnect.com</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-telephone" style="font-size: 1.5rem; color: var(--primary-color); margin-right: 1rem;"></i>
                                    <div>
                                        <h6 class="mb-0">Phone</h6>
                                        <p class="mb-0 text-muted">+1 (555) 123-4567</p>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock" style="font-size: 1.5rem; color: var(--primary-color); margin-right: 1rem;"></i>
                                    <div>
                                        <h6 class="mb-0">Business Hours</h6>
                                        <p class="mb-0 text-muted">Monday - Friday: 9:00 AM - 5:00 PM</p>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Follow Us</h5>
                            <div class="d-flex gap-3">
                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-facebook"></i></a>
                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="fw-bold mb-4">Send us a Message</h3>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Your Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required 
                                               placeholder="Enter your name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" required 
                                               placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required 
                                           placeholder="Enter subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required 
                                              placeholder="Enter your message"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
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
