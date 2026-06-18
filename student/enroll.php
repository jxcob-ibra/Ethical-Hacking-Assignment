<?php
/**
 * MyEduConnect - Student Enrollment Page
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
    redirect(APP_URL . '/courses.php', 'Invalid course', 'error');
}

// Get course information
$course = getCourseById($courseId);

if (!$course) {
    redirect(APP_URL . '/courses.php', 'Course not found', 'error');
}

// Get student information
$student = getStudentByUserId(getCurrentUserId());

// Check if already enrolled
$existingEnrollment = dbSelectOne(
    "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?",
    [$student['student_id'], $courseId]
);

if ($existingEnrollment) {
    redirect('enrollments.php', 'You are already enrolled in this course', 'warning');
}

$error = '';
$success = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            // Start transaction
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // Create enrollment
            $enrollmentQuery = "INSERT INTO enrollments (student_id, course_id, status, progress) VALUES (?, ?, 'active', 0)";
            dbInsert($enrollmentQuery, [$student['student_id'], $courseId]);
            
            // Create payment record
            $transactionId = 'TXN' . strtoupper(generateRandomString(8));
            $paymentQuery = "INSERT INTO payments (student_id, course_id, amount, payment_method, transaction_id, status) 
                            VALUES (?, ?, ?, ?, ?, 'completed')";
            dbInsert($paymentQuery, [
                $student['student_id'],
                $courseId,
                $course['price'],
                $_POST['payment_method'] ?? 'Credit Card',
                $transactionId
            ]);
            
            // Update course enrollment count
            dbUpdate("UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE course_id = ?", [$courseId]);
            
            // Commit transaction
            $db->commit();
            
            // Log audit
            logAudit('ENROLL', 'enrollments', null, null, ['course_id' => $courseId]);
            
            redirect('enrollments.php', 'Enrollment successful! You can now access the course materials.', 'success');
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Enrollment failed: ' . $e->getMessage();
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
    <title>Enroll in Course - <?php echo APP_NAME; ?></title>
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

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Enroll in Course</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div style="width: 100%; height: 200px; background: var(--primary-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 4rem;">
                                    <i class="bi bi-book"></i>
                                </div>
                            </div>
                            <div class="col-md-8 mb-4">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($course['category']); ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-clock"></i> <?php echo $course['duration_weeks']; ?> weeks
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-people"></i> <?php echo $course['enrollment_count']; ?> students enrolled
                                </p>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Course Description</h5>
                                <p><?php echo htmlspecialchars($course['description']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-summary">
                                    <h5 class="mb-3">Payment Summary</h5>
                                    <div class="summary-row">
                                        <span>Course Price</span>
                                        <span><?php echo formatCurrency($course['price']); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Discount</span>
                                        <span>$0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Tax</span>
                                        <span>$0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Total</span>
                                        <span><?php echo formatCurrency($course['price']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-4">
                                <h5 class="mb-3">Select Payment Method</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="Credit Card" checked>
                                            <label class="form-check-label" for="credit_card">
                                                <i class="bi bi-credit-card"></i> Credit Card
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="PayPal">
                                            <label class="form-check-label" for="paypal">
                                                <i class="bi bi-paypal"></i> PayPal
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="Bank Transfer">
                                            <label class="form-check-label" for="bank_transfer">
                                                <i class="bi bi-bank"></i> Bank Transfer
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> This is a mock payment system. No actual payment will be processed.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-credit-card"></i> Complete Enrollment
                                </button>
                                <a href="<?php echo APP_URL; ?>/courses.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
