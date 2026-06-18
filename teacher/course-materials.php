<?php
/**
 * MyEduConnect - Teacher Course Materials Page
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require teacher login
requireRole('teacher');
checkSessionTimeout();

// Get course ID
$courseId = $_GET['course_id'] ?? null;

if (!$courseId) {
    redirect('courses.php', 'Invalid course', 'error');
}

// Get course information
$course = getCourseById($courseId);

if (!$course) {
    redirect('courses.php', 'Course not found', 'error');
}

// Get teacher information
$teacher = getTeacherByUserId(getCurrentUserId());

// Check if teacher owns this course
if ($course['instructor_id'] != $teacher['teacher_id']) {
    redirect('courses.php', 'You do not have permission to manage this course', 'error');
}

// Get course materials
$materials = getCourseMaterials($courseId);

$error = '';
$success = '';

// Handle material upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['material_file'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $material_type = sanitize($_POST['material_type'] ?? 'resource');
        
        if (empty($title) || empty($_FILES['material_file']['name'])) {
            $error = 'Title and file are required.';
        } else {
            // Upload file
            $uploadDir = UPLOAD_DIR . 'course' . $courseId . '/';
            $result = uploadFile($_FILES['material_file'], $uploadDir);
            
            if ($result['success']) {
                try {
                    $query = "INSERT INTO course_materials (course_id, title, description, file_name, file_path, file_type, file_size, uploaded_by, material_type) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    dbInsert($query, [
                        $courseId,
                        $title,
                        $description,
                        $result['file_name'],
                        '/uploads/course' . $courseId . '/' . $result['file_name'],
                        $_FILES['material_file']['type'],
                        $_FILES['material_file']['size'],
                        $teacher['teacher_id'],
                        $material_type
                    ]);
                    
                    // Log audit
                    logAudit('UPLOAD_MATERIAL', 'course_materials', null, null, ['course_id' => $courseId]);
                    
                    $success = 'Material uploaded successfully!';
                    $materials = getCourseMaterials($courseId);
                    
                } catch (Exception $e) {
                    $error = 'Failed to save material: ' . $e->getMessage();
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Handle material deletion
if (isset($_GET['delete_material'])) {
    $materialId = intval($_GET['delete_material']);
    try {
        $material = dbSelectOne("SELECT * FROM course_materials WHERE material_id = ?", [$materialId]);
        if ($material && $material['course_id'] == $courseId) {
            // Delete file from server
            $filePath = UPLOAD_DIR . 'course' . $courseId . '/' . $material['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            dbDelete("DELETE FROM course_materials WHERE material_id = ?", [$materialId]);
            
            // Log audit
            logAudit('DELETE_MATERIAL', 'course_materials', $materialId);
            
            redirect('course-materials.php?course_id=' . $courseId, 'Material deleted successfully!', 'success');
        }
    } catch (Exception $e) {
        $error = 'Failed to delete material: ' . $e->getMessage();
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - <?php echo APP_NAME; ?></title>
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
                    <a class="nav-link" href="create-course.php">
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
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="courses.php">My Courses</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($course['title']); ?></li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($course['title']); ?></h2>
                        <p class="text-muted mb-0">Course Materials</p>
                    </div>
                    <a href="edit-course.php?course_id=<?php echo $courseId; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil"></i> Edit Course
                    </a>
                </div>

                <!-- Upload Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Upload New Material</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Material Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           placeholder="Enter material title">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="material_type" class="form-label">Material Type</label>
                                    <select class="form-select" id="material_type" name="material_type">
                                        <option value="note">Note</option>
                                        <option value="assignment">Assignment</option>
                                        <option value="video">Video</option>
                                        <option value="resource">Resource</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Enter material description"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="material_file" class="form-label">File *</label>
                                <input type="file" class="form-control" id="material_file" name="material_file" required 
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.zip" data-upload="true">
                                <small class="text-muted">Allowed file types: PDF, DOC, DOCX, PPT, PPTX, TXT, ZIP (Max 5MB)</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Material
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Uploaded Materials</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($materials)): ?>
                            <?php foreach ($materials as $material): ?>
                                <div class="material-item">
                                    <div class="d-flex align-items-center">
                                        <div class="material-icon">
                                            <?php
                                            $icon = 'bi-file-earmark';
                                            switch ($material['material_type']) {
                                                case 'note':
                                                    $icon = 'bi-file-earmark-text';
                                                    break;
                                                case 'assignment':
                                                    $icon = 'bi-file-earmark-check';
                                                    break;
                                                case 'video':
                                                    $icon = 'bi-play-circle';
                                                    break;
                                                case 'resource':
                                                    $icon = 'bi-file-earmark-zip';
                                                    break;
                                            }
                                            ?>
                                            <i class="bi <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="material-info">
                                            <h6 class="material-title"><?php echo htmlspecialchars($material['title']); ?></h6>
                                            <p class="material-meta">
                                                <span class="badge bg-secondary"><?php echo ucfirst($material['material_type']); ?></span>
                                                <span class="ms-2">
                                                    <i class="bi bi-calendar"></i> <?php echo formatDate($material['upload_date']); ?>
                                                </span>
                                                <span class="ms-2">
                                                    <i class="bi bi-hdd"></i> <?php echo number_format($material['file_size'] / 1024, 2); ?> KB
                                                </span>
                                            </p>
                                            <?php if ($material['description']): ?>
                                                <p class="mb-0 text-muted small"><?php echo htmlspecialchars($material['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo APP_URL . $material['file_path']; ?>" class="btn btn-outline-primary btn-sm" download>
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="course-materials.php?course_id=<?php echo $courseId; ?>&delete_material=<?php echo $material['material_id']; ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this material?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder" style="font-size: 4rem; color: var(--gray-color);"></i>
                                <h4 class="mt-3">No Materials Uploaded</h4>
                                <p class="text-muted">Upload your first course material above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
