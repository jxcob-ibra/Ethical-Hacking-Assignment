<?php
require_once 'app/config/config.php';
require_once 'app/security/functions.php';

$courseId = $_GET['id'] ?? null;
if (!$courseId) {
    redirect(APP_URL . '/courses.php', 'Course not found.', 'error');
}
$course = getCourseById($courseId);
if (!$course) {
    redirect(APP_URL . '/courses.php', 'Course not found.', 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-body">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($course['category'] ?? ''); ?></p>
                <p><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                <p><strong>Price:</strong> <?php echo formatCurrency((float)$course['price']); ?></p>
                <?php if (isLoggedIn() && isStudent()): ?>
                    <a class="btn btn-primary" href="<?php echo APP_URL; ?>/student/enroll.php?course_id=<?php echo (int)$course['course_id']; ?>">Enroll</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary" href="<?php echo APP_URL; ?>/courses.php">Back</a>
            </div>
        </div>
    </div>
</body>
</html>
