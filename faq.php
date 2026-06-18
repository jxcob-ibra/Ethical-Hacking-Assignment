<?php
/**
 * MyEduConnect - FAQ Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';

// FAQ data
$faqs = [
    [
        'question' => 'How do I create an account?',
        'answer' => 'To create an account, click on the "Register" button on the homepage. Fill in the required information including your email, password, and student details. Once registered, you can log in and start exploring courses.'
    ],
    [
        'question' => 'What payment methods do you accept?',
        'answer' => 'We accept various payment methods including credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers. All payments are processed securely through our payment gateway.'
    ],
    [
        'question' => 'Can I access courses on mobile devices?',
        'answer' => 'Yes! Our platform is fully responsive and can be accessed on any device including smartphones, tablets, and desktop computers. Learn anytime, anywhere.'
    ],
    [
        'question' => 'How long do I have access to a course?',
        'answer' => 'Once you enroll in a course, you have lifetime access to the course materials. You can learn at your own pace and revisit the content whenever you need.'
    ],
    [
        'question' => 'Do I receive a certificate upon completion?',
        'answer' => 'Yes, upon successful completion of a course, you will receive a digital certificate that you can share on your resume or LinkedIn profile to showcase your new skills.'
    ],
    [
        'question' => 'What if I need help during a course?',
        'answer' => 'Our instructors and support team are available to help you. You can ask questions in the course discussion forum or contact our support team directly through the help center.'
    ],
    [
        'question' => 'Can I get a refund if I\'m not satisfied?',
        'answer' => 'We offer a 30-day money-back guarantee for all courses. If you\'re not satisfied with your purchase, contact our support team within 30 days for a full refund.'
    ],
    [
        'question' => 'How do I become an instructor?',
        'answer' => 'If you\'re interested in becoming an instructor, please contact us through the contact form with your credentials and course proposal. Our team will review your application and get back to you.'
    ],
    [
        'question' => 'Are there any prerequisites for courses?',
        'answer' => 'Some courses may have prerequisites which are listed on the course page. Make sure to check the course requirements before enrolling to ensure you have the necessary background knowledge.'
    ],
    [
        'question' => 'How do I reset my password?',
        'answer' => 'Click on "Forgot Password" on the login page. Enter your email address, and we\'ll send you a link to reset your password. Follow the instructions in the email to create a new password.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - <?php echo APP_NAME; ?></title>
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
                        <a class="nav-link" href="<?php echo APP_URL; ?>/contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo APP_URL; ?>/faq.php">FAQ</a>
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
            <h1 class="fw-bold">Frequently Asked Questions</h1>
            <p class="mb-0">Find answers to common questions about our platform</p>
        </div>
    </section>

    <!-- FAQ Content -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach ($faqs as $index => $faq): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                    <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $index; ?>" 
                                            aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                            aria-controls="collapse<?php echo $index; ?>">
                                        <?php echo htmlspecialchars($faq['question']); ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $index; ?>" 
                                     class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $index; ?>" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <?php echo htmlspecialchars($faq['answer']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <h3 class="fw-bold mb-3">Still have questions?</h3>
                <p class="text-muted mb-4">Can't find the answer you're looking for? Please reach out to our support team.</p>
                <a href="<?php echo APP_URL; ?>/contact.php" class="btn btn-primary btn-lg">Contact Support</a>
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
