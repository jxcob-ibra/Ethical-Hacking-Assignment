<?php
/**
 * MyEduConnect - Teacher Create Course Page
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require teacher login
requireRole('teacher');
checkSessionTimeout();

// Get teacher information
$teacher = getTeacherByUserId(getCurrentUserId());

$error = '';
$success = '';

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $duration_weeks = intval($_POST['duration_weeks'] ?? 8);
        $max_students = intval($_POST['max_students'] ?? 50);
        $status = sanitize($_POST['status'] ?? 'draft');
        
        if (empty($title) || empty($description) || empty($category)) {
            $error = 'Title, description, and category are required.';
        } else {
            try {
                $query = "INSERT INTO courses (title, description, instructor_id, category, price, duration_weeks, max_students, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $courseId = dbInsert($query, [
                    $title,
                    $description,
                    $teacher['teacher_id'],
                    $category,
                    $price,
                    $duration_weeks,
                    $max_students,
                    $status
                ]);
                
                // Log audit
                logAudit('CREATE', 'courses', $courseId);
                
                redirect('courses.php', 'Course created successfully!', 'success');
                
            } catch (Exception $e) {
                $error = 'Failed to create course: ' . $e->getMessage();
            }
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
    <title>Create Course - <?php echo APP_NAME; ?></title>
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
                            <li><a class="dropdown-item" href="courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="students.php">Students</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="mb-4">
                    <div class="text-center mb-3">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1)) . strtoupper(substr($teacher['last_name'], 0, 1)); ?>
                        </div>
                        <h6 class="mt-2"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($teacher['teacher_id_number']); ?></small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> My Courses
                    </a>
                    <a class="nav-link active" href="create-course.php">
                        <i class="bi bi-plus-circle"></i> Create Course
                    </a>
                    <a class="nav-link" href="students.php">
                        <i class="bi bi-people"></i> Students
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 py-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <h2 class="fw-bold mb-4">Create New Course</h2>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Course Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       placeholder="Enter course title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Course Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required 
                                          placeholder="Enter course description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select category</option>
                                        <option value="Cybersecurity" <?php echo ($_POST['category'] ?? '') === 'Cybersecurity' ? 'selected' : ''; ?>>Cybersecurity</option>
                                        <option value="Web Development" <?php echo ($_POST['category'] ?? '') === 'Web Development' ? 'selected' : ''; ?>>Web Development</option>
                                        <option value="Data Science" <?php echo ($_POST['category'] ?? '') === 'Data Science' ? 'selected' : ''; ?>>Data Science</option>
                                        <option value="Programming" <?php echo ($_POST['category'] ?? '') === 'Programming' ? 'selected' : ''; ?>>Programming</option>
                                        <option value="Networking" <?php echo ($_POST['category'] ?? '') === 'Networking' ? 'selected' : ''; ?>>Networking</option>
                                        <option value="Database" <?php echo ($_POST['category'] ?? '') === 'Database' ? 'selected' : ''; ?>>Database</option>
                                        <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price ($)</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? '0'); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="duration_weeks" class="form-label">Duration (Weeks)</label>
                                    <input type="number" class="form-control" id="duration_weeks" name="duration_weeks" min="1" max="52" 
                                           value="<?php echo htmlspecialchars($_POST['duration_weeks'] ?? '8'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="max_students" class="form-label">Maximum Students</label>
                                    <input type="number" class="form-control" id="max_students" name="max_students" min="1" max="500" 
                                           value="<?php echo htmlspecialchars($_POST['max_students'] ?? '50'); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                </select>
                                <small class="text-muted">Draft courses are not visible to students</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Create Course
                                </button>
                                <a href="courses.php" class="btn btn-outline-secondary">Cancel</a>
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
