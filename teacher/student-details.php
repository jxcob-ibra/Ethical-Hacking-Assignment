<?php
require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

requireRole('teacher');
checkSessionTimeout();

$teacher = getTeacherByUserId(getCurrentUserId());
$courseId = (int)($_GET['course_id'] ?? 0);
$studentId = (int)($_GET['student_id'] ?? 0);

$course = getCourseById($courseId);
if (!$course || $course['instructor_id'] != $teacher['teacher_id']) {
    redirect('courses.php', 'Invalid course.', 'error');
}

$student = dbSelectOne(
    "SELECT s.*, u.* FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.student_id = ?",
    [$studentId]
);
if (!$student) {
    redirect('course-students.php?course_id=' . $courseId, 'Student not found.', 'error');
}

// IDOR demo behavior:
// VULNERABLE MODE: a teacher can directly access any student_id.
// SECURE MODE: teacher must have that student enrolled in this teacher-owned course.
if (!isVulnerabilityEnabled('idor')) {
    $allowed = dbSelectOne(
        "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1",
        [$studentId, $courseId]
    );
    if (!$allowed) {
        redirect('course-students.php?course_id=' . $courseId, 'Access denied.', 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3>Student Details</h3>
    <a class="btn btn-outline-secondary mb-3" href="course-students.php?course_id=<?php echo $courseId; ?>">Back</a>
    <div class="card">
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id_number']); ?></p>
            <p><strong>Grade:</strong> <?php echo htmlspecialchars($student['grade_level'] ?? 'N/A'); ?></p>
            <p><strong>About:</strong> <?php echo !isVulnerabilityEnabled('stored_xss') ? htmlspecialchars($student['about_me'] ?? '') : ($student['about_me'] ?? ''); ?></p>
        </div>
    </div>
</div>
</body>
</html>
