<?php
/**
 * MyEduConnect - Authentication Functions
 * Login, Logout, Registration, Session Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * User login
 */
function login($email, $password) {
    if (!isVulnerabilityEnabled('sql_injection')) {
        // SECURE MODE: prepared statements + password verification.
        $email = sanitize($email);
        if (!validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        $user = dbSelectOne(
            "SELECT * FROM users WHERE email = ? AND status = 'active'",
            [$email]
        );
        if (!$user || !verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        // Migrate weak hashes to bcrypt after successful login in secure mode.
        if (preg_match('/^[a-f0-9]{32}$/i', $user['password']) || $password === $user['password']) {
            dbUpdate(
                "UPDATE users SET password = ? WHERE user_id = ?",
                [password_hash($password, PASSWORD_BCRYPT), $user['user_id']]
            );
        }
    } else {
        // VULNERABLE MODE: raw SQL concatenation and weak auth logic.
        $query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query($query);
        $user = $stmt->fetch();
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        // Keep vulnerable behavior so SQLi login bypass can succeed in labs.
    }
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    session_regenerate_id(true);
    
    // Update last login
    dbUpdate("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$user['user_id']]);
    
    // Log audit
    logAudit('LOGIN', 'users', $user['user_id']);
    
    // Redirect based on role
    $redirectUrl = APP_URL;
    switch ($user['role']) {
        case 'student':
            $redirectUrl .= '/student/dashboard.php';
            break;
        case 'teacher':
            $redirectUrl .= '/teacher/dashboard.php';
            break;
        case 'admin':
            $redirectUrl .= '/admin/dashboard.php';
            break;
    }
    
    return ['success' => true, 'redirect' => $redirectUrl];
}

/**
 * User logout
 */
function logout() {
    // Log audit before destroying session
    if (isLoggedIn()) {
        logAudit('LOGOUT', 'users', getCurrentUserId());
    }
    
    // Destroy session
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    
    return ['success' => true];
}

/**
 * Student registration
 */
function registerStudent($data) {
    // Validate required fields
    $required = ['email', 'password', 'confirm_password', 'first_name', 'last_name', 'student_id_number'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Check if email already exists
    $existingUser = dbSelectOne("SELECT user_id FROM users WHERE email = ?", [$data['email']]);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Check if student ID number already exists
    $existingStudent = dbSelectOne("SELECT student_id FROM students WHERE student_id_number = ?", [$data['student_id_number']]);
    if ($existingStudent) {
        return ['success' => false, 'message' => 'Student ID number already registered'];
    }
    
    // Validate password
    if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
    }
    
    // Check password confirmation
    if ($data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Hash password
    $hashedPassword = hashPassword($data['password']);
    
    try {
        // Start transaction
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        // Insert user
        $userQuery = "INSERT INTO users (email, password, first_name, last_name, phone, role, status) 
                      VALUES (?, ?, ?, ?, ?, 'student', 'active')";
        $userId = dbInsert($userQuery, [
            sanitize($data['email']),
            $hashedPassword,
            sanitize($data['first_name']),
            sanitize($data['last_name']),
            sanitize($data['phone'] ?? '')
        ]);
        
        // Insert student
        $studentQuery = "INSERT INTO students (user_id, student_id_number, date_of_birth, grade_level, parent_name, parent_email, parent_phone) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        dbInsert($studentQuery, [
            $userId,
            sanitize($data['student_id_number']),
            $data['date_of_birth'] ?? null,
            sanitize($data['grade_level'] ?? ''),
            sanitize($data['parent_name'] ?? ''),
            sanitize($data['parent_email'] ?? ''),
            sanitize($data['parent_phone'] ?? '')
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Log audit
        logAudit('REGISTER', 'users', $userId);
        
        return ['success' => true, 'message' => 'Registration successful'];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Teacher registration (admin only)
 */
function registerTeacher($data) {
    // Validate required fields
    $required = ['email', 'password', 'first_name', 'last_name', 'teacher_id_number'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Check if email already exists
    $existingUser = dbSelectOne("SELECT user_id FROM users WHERE email = ?", [$data['email']]);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Check if teacher ID number already exists
    $existingTeacher = dbSelectOne("SELECT teacher_id FROM teachers WHERE teacher_id_number = ?", [$data['teacher_id_number']]);
    if ($existingTeacher) {
        return ['success' => false, 'message' => 'Teacher ID number already registered'];
    }
    
    // Hash password
    $hashedPassword = hashPassword($data['password']);
    
    try {
        // Start transaction
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        // Insert user
        $userQuery = "INSERT INTO users (email, password, first_name, last_name, phone, role, status) 
                      VALUES (?, ?, ?, ?, ?, 'teacher', 'active')";
        $userId = dbInsert($userQuery, [
            sanitize($data['email']),
            $hashedPassword,
            sanitize($data['first_name']),
            sanitize($data['last_name']),
            sanitize($data['phone'] ?? '')
        ]);
        
        // Insert teacher
        $teacherQuery = "INSERT INTO teachers (user_id, teacher_id_number, department, specialization, qualification, hire_date, bio) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        dbInsert($teacherQuery, [
            $userId,
            sanitize($data['teacher_id_number']),
            sanitize($data['department'] ?? ''),
            sanitize($data['specialization'] ?? ''),
            sanitize($data['qualification'] ?? ''),
            $data['hire_date'] ?? null,
            sanitize($data['bio'] ?? '')
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Log audit
        logAudit('CREATE_TEACHER', 'users', $userId);
        
        return ['success' => true, 'message' => 'Teacher created successfully'];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Failed to create teacher: ' . $e->getMessage()];
    }
}

/**
 * Admin registration (system only)
 */
function registerAdmin($data) {
    // Validate required fields
    $required = ['email', 'password', 'first_name', 'last_name', 'admin_id_number'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Field '$field' is required"];
        }
    }
    
    // Validate email
    if (!validateEmail($data['email'])) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Check if email already exists
    $existingUser = dbSelectOne("SELECT user_id FROM users WHERE email = ?", [$data['email']]);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $hashedPassword = hashPassword($data['password']);
    
    try {
        // Start transaction
        $db = Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        // Insert user
        $userQuery = "INSERT INTO users (email, password, first_name, last_name, phone, role, status) 
                      VALUES (?, ?, ?, ?, ?, 'admin', 'active')";
        $userId = dbInsert($userQuery, [
            sanitize($data['email']),
            $hashedPassword,
            sanitize($data['first_name']),
            sanitize($data['last_name']),
            sanitize($data['phone'] ?? '')
        ]);
        
        // Insert admin
        $adminQuery = "INSERT INTO admins (user_id, admin_id_number, department, permissions, hire_date) 
                       VALUES (?, ?, ?, ?, ?)";
        dbInsert($adminQuery, [
            $userId,
            sanitize($data['admin_id_number']),
            sanitize($data['department'] ?? ''),
            sanitize($data['permissions'] ?? ''),
            $data['hire_date'] ?? null
        ]);
        
        // Commit transaction
        $db->commit();
        
        // Log audit
        logAudit('CREATE_ADMIN', 'users', $userId);
        
        return ['success' => true, 'message' => 'Admin created successfully'];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Failed to create admin: ' . $e->getMessage()];
    }
}

/**
 * Update user profile
 */
function updateProfile($userId, $data) {
    try {
        $query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, about_me = ?  WHERE user_id = ?";
        dbUpdate($query, [
            sanitize($data['first_name']),
            sanitize($data['last_name']),
            sanitize($data['phone'] ?? ''),
            sanitize($data['address'] ?? ''),
            !isVulnerabilityEnabled('stored_xss') ? sanitize($data['about_me'] ?? '') : ($data['about_me'] ?? ''),
            $userId
        ]);
        
        // Log audit
        logAudit('UPDATE_PROFILE', 'users', $userId);
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
    }
}

/**
 * Change password
 */
function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
    // Get current user
    $user = getUserById($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Verify current password
    if (!verifyPassword($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Validate new password
    if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
    }
    
    // Check password confirmation
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    // Hash new password
    $hashedPassword = hashPassword($newPassword);
    
    try {
        $query = "UPDATE users SET password = ? WHERE user_id = ?";
        dbUpdate($query, [$hashedPassword, $userId]);
        
        // Log audit
        logAudit('CHANGE_PASSWORD', 'users', $userId);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to change password: ' . $e->getMessage()];
    }
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > SESSION_LIFETIME) {
            logout();
            redirect(APP_URL . '/login.php', 'Session expired. Please login again.', 'warning');
        }
    }
}
