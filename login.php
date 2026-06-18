<?php
/**
 * MyEduConnect - Login Page
 * Learning Management System
 */

require_once 'app/config/config.php';
require_once 'app/security/functions.php';
require_once 'app/security/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirectUrl = APP_URL;
    switch (getCurrentUserRole()) {
        case 'student':
            $redirectUrl .= '/student/dashboard.php';
            break;
        case 'teacher':
            $redirectUrl .= '/teacher/dashboard.php';
            break;
        case 'admin':
            $redirectUrl .= '/admin/dashboard.php';
            break;
    }
    redirect($redirectUrl);
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            redirect($result['redirect'], 'Login successful!', 'success');
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="text-center mb-4">
                <i class="bi bi-mortarboard-fill" style="font-size: 3rem; color: var(--primary-color);"></i>
                <h2 class="auth-title mt-3">Welcome Back</h2>
                <p class="auth-subtitle">Sign in to your account</p>
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
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="text" class="form-control" id="email" name="email" required 
                               placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
                
                <div class="text-center">
                    <a href="<?php echo APP_URL; ?>/forgot-password.php" class="text-muted">Forgot Password?</a>
                </div>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-0">Don't have an account? <a href="<?php echo APP_URL; ?>/register.php" class="fw-bold">Register here</a></p>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?php echo APP_URL; ?>" class="text-muted"><i class="bi bi-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
