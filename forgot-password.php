<?php
require_once 'app/config/config.php';
require_once 'app/security/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-body">
                <h4>Forgot password</h4>
                <p>This training platform uses admin-assisted password reset for lab simplicity.</p>
                <p class="mb-0">Please contact an administrator to reset your password.</p>
                <a class="btn btn-primary mt-3" href="<?php echo APP_URL; ?>/login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
