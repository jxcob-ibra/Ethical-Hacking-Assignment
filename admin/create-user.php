<?php
/**
 * MyEduConnect - Admin Create User Page
 * Learning Management System
 */

require_once '../app/config/config.php';
require_once '../app/security/functions.php';
require_once '../app/security/auth.php';

// Require admin login
requireRole('admin');
checkSessionTimeout();

// Get admin information
$admin = getAdminByUserId(getCurrentUserId());

$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $role = sanitize($_POST['role'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        
        if (empty($role) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $error = 'All required fields must be filled.';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email format.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } else {
            // Check if email already exists
            $existingUser = dbSelectOne("SELECT user_id FROM users WHERE email = ?", [$email]);
            if ($existingUser) {
                $error = 'Email already registered.';
            } else {
                try {
                    $db = Database::getInstance()->getConnection();
                    $db->beginTransaction();
                    
                    // Insert user
                    $userQuery = "INSERT INTO users (email, password, first_name, last_name, phone, role, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, 'active')";
                    $hashedPassword = hashPassword($password);
                    $userId = dbInsert($userQuery, [
                        $email,
                        $hashedPassword,
                        $firstName,
                        $lastName,
                        sanitize($_POST['phone'] ?? ''),
                        $role
                    ]);
                    
                    // Insert role-specific data
                    if ($role === 'student') {
                        $studentQuery = "INSERT INTO students (user_id, student_id_number, date_of_birth, grade_level) 
                                         VALUES (?, ?, ?, ?)";
                        dbInsert($studentQuery, [
                            $userId,
                            'STU' . str_pad($userId, 4, '0', STR_PAD_LEFT),
                            $_POST['date_of_birth'] ?? null,
                            sanitize($_POST['grade_level'] ?? '')
                        ]);
                    } elseif ($role === 'teacher') {
                        $teacherQuery = "INSERT INTO teachers (user_id, teacher_id_number, department, specialization, qualification) 
                                         VALUES (?, ?, ?, ?, ?)";
                        dbInsert($teacherQuery, [
                            $userId,
                            'TCH' . str_pad($userId, 4, '0', STR_PAD_LEFT),
                            sanitize($_POST['department'] ?? ''),
                            sanitize($_POST['specialization'] ?? ''),
                            sanitize($_POST['qualification'] ?? '')
                        ]);
                    } elseif ($role === 'admin') {
                        $adminQuery = "INSERT INTO admins (user_id, admin_id_number, department) 
                                      VALUES (?, ?, ?)";
                        dbInsert($adminQuery, [
                            $userId,
                            'ADM' . str_pad($userId, 4, '0', STR_PAD_LEFT),
                            sanitize($_POST['department'] ?? '')
                        ]);
                    }
                    
                    $db->commit();
                    
                    // Log audit
                    logAudit('CREATE_USER', 'users', $userId);
                    
                    redirect('users.php', 'User created successfully!', 'success');
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to create user: ' . $e->getMessage();
                }
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
    <title>Create User - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <i class="bi bi-mortarboard-fill"></i> <?php echo APP_NAME; ?> Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>">View Site</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="users.php">Users</a></li>
                            <li><a class="dropdown-item" href="courses.php">Courses</a></li>
                            <li><a class="dropdown-item" href="payments.php">Payments</a></li>
                            <li><a class="dropdown-item" href="audit-logs.php">Audit Logs</a></li>
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
                        <div style="width: 80px; height: 80px; background: var(--dark-color); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            <i class="bi bi-shield-fill"></i>
                        </div>
                        <h6 class="mt-2"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h6>
                        <small class="text-muted">Administrator</small>
                    </div>
                </div>
                
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a class="nav-link" href="courses.php">
                        <i class="bi bi-book"></i> Courses
                    </a>
                    <a class="nav-link" href="payments.php">
                        <i class="bi bi-credit-card"></i> Payments
                    </a>
                    <a class="nav-link" href="announcements.php">
                        <i class="bi bi-megaphone"></i> Announcements
                    </a>
                    <a class="nav-link" href="audit-logs.php">
                        <i class="bi bi-journal-text"></i> Audit Logs
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

                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Create User</li>
                    </ol>
                </nav>

                <h2 class="fw-bold mb-4">Create New User</h2>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <h5 class="mb-3">Basic Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required onchange="toggleRoleFields()">
                                        <option value="">Select role</option>
                                        <option value="student">Student</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           placeholder="Enter phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required 
                                           placeholder="Enter first name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required 
                                           placeholder="Enter last name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="Enter email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           data-strength="true" data-confirm="confirm_password">
                                    <small class="text-muted">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Role-Specific Information</h5>
                            
                            <div id="student-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="grade_level" class="form-label">Grade Level</label>
                                        <input type="text" class="form-control" id="grade_level" name="grade_level" 
                                               placeholder="e.g., Grade 12" value="<?php echo htmlspecialchars($_POST['grade_level'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div id="teacher-fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="department" class="form-label">Department</label>
                                        <input type="text" class="form-control" id="department" name="department" 
                                               placeholder="e.g., Computer Science" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="specialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" 
                                               placeholder="e.g., Cybersecurity" value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="qualification" class="form-label">Qualification</label>
                                        <input type="text" class="form-control" id="qualification" name="qualification" 
                                               placeholder="e.g., PhD" value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div id="admin-fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="admin_department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="admin_department" name="department" 
                                           placeholder="e.g., Administration" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Create User
                                </button>
                                <a href="users.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('role').value;
            document.getElementById('student-fields').style.display = role === 'student' ? 'block' : 'none';
            document.getElementById('teacher-fields').style.display = role === 'teacher' ? 'block' : 'none';
            document.getElementById('admin-fields').style.display = role === 'admin' ? 'block' : 'none';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
