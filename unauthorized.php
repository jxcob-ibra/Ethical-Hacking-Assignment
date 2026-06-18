<?php
require_once 'app/config/config.php';
require_once 'app/security/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="alert alert-danger">
            <h4 class="mb-2">Access denied</h4>
            <p class="mb-3">You are not authorized to access this resource.</p>
            <a class="btn btn-primary" href="<?php echo APP_URL; ?>">Back to Home</a>
        </div>
    </div>
</body>
</html>
