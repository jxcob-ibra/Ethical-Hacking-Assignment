<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('admin');
checkSessionTimeout();

$courseId = (int)($_GET['course_id'] ?? 0);
$course = getCourseById($courseId);
if (!$course) {
    redirect('courses.php', 'Course not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('courses.php', 'Invalid request.', 'error');
    }
    dbUpdate(
        "UPDATE courses SET title = ?, description = ?, category = ?, price = ?, status = ? WHERE course_id = ?",
        [
            sanitize($_POST['title'] ?? ''),
            sanitize($_POST['description'] ?? ''),
            sanitize($_POST['category'] ?? ''),
            (float)($_POST['price'] ?? 0),
            sanitize($_POST['status'] ?? 'draft'),
            $courseId
        ]
    );
    logAudit('UPDATE_COURSE', 'courses', $courseId);
    redirect('courses.php', 'Course updated successfully.', 'success');
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h3>Edit Course</h3>
        <form method="POST" class="card p-3">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="mb-3"><label>Title</label><input class="form-control" name="title" value="<?php echo htmlspecialchars($course['title']); ?>"></div>
            <div class="mb-3"><label>Description</label><textarea class="form-control" name="description"><?php echo htmlspecialchars($course['description']); ?></textarea></div>
            <div class="row">
                <div class="col-md-4 mb-3"><label>Category</label><input class="form-control" name="category" value="<?php echo htmlspecialchars($course['category']); ?>"></div>
                <div class="col-md-4 mb-3"><label>Price</label><input class="form-control" type="number" step="0.01" name="price" value="<?php echo htmlspecialchars((string)$course['price']); ?>"></div>
                <div class="col-md-4 mb-3">
                    <label>Status</label>
                    <select class="form-select" name="status">
                        <option value="draft" <?php echo $course['status']==='draft'?'selected':''; ?>>Draft</option>
                        <option value="published" <?php echo $course['status']==='published'?'selected':''; ?>>Published</option>
                        <option value="archived" <?php echo $course['status']==='archived'?'selected':''; ?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="courses.php">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
