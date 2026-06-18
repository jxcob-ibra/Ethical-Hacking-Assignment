<?php
/**
 * MyEduConnect - Registration Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';
require_once 'app/security/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL);
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $data = [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'student_id_number' => $_POST['student_id_number'] ?? '',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'grade_level' => $_POST['grade_level'] ?? '',
            'parent_name' => $_POST['parent_name'] ?? '',
            'parent_email' => $_POST['parent_email'] ?? '',
            'parent_phone' => $_POST['parent_phone'] ?? ''
        ];
        
        $result = registerStudent($data);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
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
    <title>Register - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card" style="max-width: 600px;">
            <div class="text-center mb-4">
                <i class="bi bi-mortarboard-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
                <h2 class="auth-title mt-3">Create Account</h2>
                <p class="auth-subtitle">Join our learning platform today</p>
            </div>
            
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
                <div class="text-center mt-3">
                    <a href="<?php echo APP_URL; ?>/login.php" class="btn btn-primary">Proceed to Login</a>
                </div>
            <?php else: ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <h5 class="mb-3">Personal Information</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required 
                               placeholder="First name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required 
                               placeholder="Last name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="Create password" data-strength="true" data-confirm="confirm_password">
                        <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm password">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Student Information</h5>
                
                <div class="mb-3">
                    <label for="student_id_number" class="form-label">Student ID Number</label>
                    <input type="text" class="form-control" id="student_id_number" name="student_id_number" required 
                           placeholder="Enter your student ID" value="<?php echo htmlspecialchars($_POST['student_id_number'] ?? ''); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="grade_level" class="form-label">Grade Level</label>
                        <input type="text" class="form-control" id="grade_level" name="grade_level" 
                               placeholder="e.g., Grade 12" value="<?php echo htmlspecialchars($_POST['grade_level'] ?? ''); ?>">
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Parent/Guardian Information</h5>
                
                <div class="mb-3">
                    <label for="parent_name" class="form-label">Parent/Guardian Name</label>
                    <input type="text" class="form-control" id="parent_name" name="parent_name" 
                           placeholder="Parent name" value="<?php echo htmlspecialchars($_POST['parent_name'] ?? ''); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="parent_email" class="form-label">Parent Email</label>
                        <input type="email" class="form-control" id="parent_email" name="parent_email" 
                               placeholder="Parent email" value="<?php echo htmlspecialchars($_POST['parent_email'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="parent_phone" class="form-label">Parent Phone</label>
                        <input type="tel" class="form-control" id="parent_phone" name="parent_phone" 
                               placeholder="Parent phone" value="<?php echo htmlspecialchars($_POST['parent_phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="terms" required>
                    <label class="form-check-label" for="terms">
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="<?php echo APP_URL; ?>/login.php" class="fw-bold">Sign in here</a></p>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="<?php echo APP_URL; ?>" class="text-muted"><i class="bi bi-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
