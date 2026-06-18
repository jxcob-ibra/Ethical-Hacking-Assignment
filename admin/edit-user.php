<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('admin');
checkSessionTimeout();

$userId = (int)($_GET['user_id'] ?? 0);
$user = getUserById($userId);
if (!$user) {
    redirect('users.php', 'User not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('users.php', 'Invalid request.', 'error');
    }
    dbUpdate(
        "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, status = ? WHERE user_id = ?",
        [
            sanitize($_POST['first_name'] ?? ''),
            sanitize($_POST['last_name'] ?? ''),
            sanitize($_POST['phone'] ?? ''),
            sanitize($_POST['address'] ?? ''),
            sanitize($_POST['status'] ?? 'active'),
            $userId
        ]
    );
    logAudit('UPDATE_USER', 'users', $userId);
    redirect('users.php', 'User updated successfully.', 'success');
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h3>Edit User</h3>
        <form method="POST" class="card p-3">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row">
                <div class="col-md-6 mb-3"><label>First Name</label><input class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
                <div class="col-md-6 mb-3"><label>Last Name</label><input class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
            </div>
            <div class="mb-3"><label>Phone</label><input class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
            <div class="mb-3"><label>Address</label><textarea class="form-control" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
            <div class="mb-3">
                <label>Status</label>
                <select class="form-select" name="status">
                    <option value="active" <?php echo $user['status']==='active'?'selected':''; ?>>Active</option>
                    <option value="inactive" <?php echo $user['status']==='inactive'?'selected':''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $user['status']==='suspended'?'selected':''; ?>>Suspended</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="users.php">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
