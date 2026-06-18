<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('teacher');
checkSessionTimeout();

$teacher = getTeacherByUserId(getCurrentUserId());
$courseId = (int)($_GET['course_id'] ?? 0);
$course = getCourseById($courseId);
if (!$course) {
    redirect('courses.php', 'Course not found.', 'error');
}
if ($course['instructor_id'] != $teacher['teacher_id']) {
    redirect('courses.php', 'Not allowed.', 'error');
}

$students = dbSelect(
    "SELECT s.student_id, s.student_id_number, u.user_id, u.first_name, u.last_name, u.email
     FROM enrollments e
     JOIN students s ON e.student_id = s.student_id
     JOIN users u ON s.user_id = u.user_id
     WHERE e.course_id = ?
     ORDER BY u.last_name, u.first_name",
    [$courseId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Students - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3><?php echo htmlspecialchars($course['title']); ?> - Students</h3>
    <a class="btn btn-outline-secondary mb-3" href="courses.php">Back</a>
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Student ID</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><?php echo htmlspecialchars($s['student_id_number']); ?></td>
                        <td><a class="btn btn-sm btn-primary" href="student-details.php?student_id=<?php echo (int)$s['student_id']; ?>&course_id=<?php echo $courseId; ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
