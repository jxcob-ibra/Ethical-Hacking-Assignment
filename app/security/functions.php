<?php
/**
 * MyEduConnect - Common Functions
 * Utility functions for the application
 */

require_once __DIR__ . '/../config/database.php';

function ensureSecuritySettingsSeeded() {
    try {
        $needsRebuild = false;
        $tableExists = dbSelectOne("SHOW TABLES LIKE 'security_settings'");
        if ($tableExists) {
            $columns = dbSelect("SHOW COLUMNS FROM security_settings");
            $columnNames = array_column($columns, 'Field');
            if (!in_array('vulnerability_name', $columnNames, true) || !in_array('enabled', $columnNames, true)) {
                $needsRebuild = true;
            }
        }

        if ($needsRebuild) {
            dbExecute("DROP TABLE IF EXISTS security_settings");
        }

        dbExecute("CREATE TABLE IF NOT EXISTS security_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            vulnerability_name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $required = [
            'sql_injection' => 'Allows vulnerable SQL query concatenation and login bypass behavior.',
            'stored_xss' => 'Allows unescaped storage/rendering of attacker-controlled HTML/JS.',
            'idor' => 'Allows direct object access without ownership checks for non-admin users.',
            'weak_ssh_credentials' => 'Uses predictable SSH credentials for demonstration.',
            'sudo_misconfiguration' => 'Allows student user to escalate privileges without password.',
            'backup_file_exposure' => 'Exposes database backup file from web-accessible path.',
            'weak_password_hashing' => 'Stores passwords in plaintext instead of using bcrypt hashing.',
            'http_api_communication' => 'Uses HTTP API URL instead of HTTPS for traffic visibility.',
        ];
        foreach ($required as $name => $desc) {
            dbExecute(
                "INSERT IGNORE INTO security_settings (vulnerability_name, description, enabled) VALUES (?, ?, 0)",
                [$name, $desc]
            );
        }
        $backup = dbSelectOne("SELECT enabled FROM security_settings WHERE vulnerability_name = 'backup_file_exposure' LIMIT 1");
        syncBackupFileExposure($backup ? ((int)$backup['enabled'] === 1) : false);
        $ssh = dbSelectOne("SELECT enabled FROM security_settings WHERE vulnerability_name = 'weak_ssh_credentials' LIMIT 1");
        applyVulnerabilitySideEffects('weak_ssh_credentials', $ssh ? ((int)$ssh['enabled'] === 1) : false);
        $sudo = dbSelectOne("SELECT enabled FROM security_settings WHERE vulnerability_name = 'sudo_misconfiguration' LIMIT 1");
        applyVulnerabilitySideEffects('sudo_misconfiguration', $sudo ? ((int)$sudo['enabled'] === 1) : false);
        $weakPassword = dbSelectOne("SELECT enabled FROM security_settings WHERE vulnerability_name = 'weak_password_hashing' LIMIT 1");
        applyVulnerabilitySideEffects('weak_password_hashing', $weakPassword ? ((int)$weakPassword['enabled'] === 1) : false);

        $columns = dbSelect("SHOW COLUMNS FROM users LIKE 'about_me'");
        if (empty($columns)) {
            dbExecute("ALTER TABLE users ADD COLUMN about_me TEXT NULL AFTER address");
        }
    } catch (Exception $e) {
        // Keep app running even if DB seed fails during bootstrap.
    }
}
ensureSecuritySettingsSeeded();

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Hash password
 */
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        // VULNERABLE MODE: plaintext storage for demonstration.
        return $password;
    }
    // SECURE MODE: bcrypt hashing.
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    // Support plaintext, MD5, and bcrypt so existing users remain functional.
    if ($password === $hash) {
        // Plaintext match (vulnerable mode)
        return true;
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        // MD5 hash match (legacy support)
        return md5($password) === $hash;
    }
    // Bcrypt hash match (secure mode)
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

/**
 * Check if user is teacher
 */
function isTeacher() {
    return getCurrentUserRole() === 'teacher';
}

/**
 * Check if user is student
 */
function isStudent() {
    return getCurrentUserRole() === 'student';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (getCurrentUserRole() !== $role) {
        header('Location: ' . APP_URL . '/unauthorized.php');
        exit();
    }
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Format date
 */
function formatDate($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file with vulnerability toggle
 */
function uploadFile($file, $destination, $allowedTypes = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_FILE_TYPES;
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Keep file upload path secure for assignment scope.
    {
        // SECURE VERSION - Strict validation
        
        // Check file size
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit'];
        }
        
        // Check file type by extension
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        // Check MIME type
        $allowedMimes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'zip' => 'application/zip'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return ['success' => false, 'message' => 'Invalid file MIME type'];
        }
        
        // Generate unique filename to prevent overwrites
        $fileName = generateRandomString(16) . '.' . $fileExt;
        $filePath = $destination . $fileName;
        
        // Create directory if it doesn't exist
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Move file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => true, 'file_path' => $filePath, 'file_name' => $fileName];
        }
        
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Log audit trail
 */
function logAudit($action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $query = "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    dbExecute($query, [
        $userId,
        $userRole,
        $action,
        $tableName,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        $ipAddress,
        $userAgent
    ]);
}

/**
 * Get user by ID with IDOR protection check
 */
function getUserById($userId, $checkOwnership = false) {
    // IDOR Vulnerability
    if ($checkOwnership && !isVulnerabilityEnabled('idor')) {
        // SECURE - Check if current user owns the resource
        $currentUserId = getCurrentUserId();
        $currentUserRole = getCurrentUserRole();
        
        // Admins can view any user
        if ($currentUserRole === 'admin') {
            $query = "SELECT * FROM users WHERE user_id = ?";
            return dbSelectOne($query, [$userId]);
        }
        
        // Non-admins can only view their own profile
        if ($currentUserId != $userId) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE user_id = ?";
        return dbSelectOne($query, [$userId]);
    } else {
        // VULNERABLE - Allow access to any user ID
        $query = "SELECT * FROM users WHERE user_id = ?";
        return dbSelectOne($query, [$userId]);
    }
}

/**
 * Get student by user ID
 */
function getStudentByUserId($userId) {
    $query = "SELECT s.*, u.* FROM students s JOIN users u ON s.user_id = u.user_id WHERE s.user_id = ?";
    return dbSelectOne($query, [$userId]);
}

/**
 * Get teacher by user ID
 */
function getTeacherByUserId($userId) {
    $query = "SELECT t.*, u.* FROM teachers t JOIN users u ON t.user_id = u.user_id WHERE t.user_id = ?";
    return dbSelectOne($query, [$userId]);
}

/**
 * Get admin by user ID
 */
function getAdminByUserId($userId) {
    $query = "SELECT a.*, u.* FROM admins a JOIN users u ON a.user_id = u.user_id WHERE a.user_id = ?";
    return dbSelectOne($query, [$userId]);
}

/**
 * Get all courses
 */
function getAllCourses($status = 'published') {
    $query = "SELECT c.*, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as instructor_name 
              FROM courses c 
              JOIN teachers t ON c.instructor_id = t.teacher_id 
              JOIN users u ON t.user_id = u.user_id
              WHERE c.status = ? 
              ORDER BY c.created_at DESC";
    return dbSelect($query, [$status]);
}

/**
 * Get course by ID
 */
function getCourseById($courseId) {
    $query = "SELECT c.*, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as instructor_name 
              FROM courses c 
              JOIN teachers t ON c.instructor_id = t.teacher_id 
              JOIN users u ON t.user_id = u.user_id 
              WHERE c.course_id = ?";
    return dbSelectOne($query, [$courseId]);
}

/**
 * Get enrolled courses for student
 */
function getEnrolledCourses($studentId) {
    $query = "SELECT e.*, c.*, u.first_name, u.last_name, CONCAT(u.first_name, ' ', u.last_name) as instructor_name 
              FROM enrollments e 
              JOIN courses c ON e.course_id = c.course_id 
              JOIN teachers t ON c.instructor_id = t.teacher_id
              JOIN users u ON t.user_id = u.user_id  
              WHERE e.student_id = ? AND e.status = 'active'
              ORDER BY e.enrollment_date DESC";
    return dbSelect($query, [$studentId]);
}

/**
 * Get courses by teacher
 */
function getCoursesByTeacher($teacherId) {
    $query = "SELECT c.*, COUNT(e.enrollment_id) as enrollment_count 
              FROM courses c 
              LEFT JOIN enrollments e ON c.course_id = e.course_id 
              WHERE c.instructor_id = ? 
              GROUP BY c.course_id 
              ORDER BY c.created_at DESC";
    return dbSelect($query, [$teacherId]);
}

/**
 * Get course materials
 */
function getCourseMaterials($courseId) {
    $query = "SELECT * FROM course_materials WHERE course_id = ? AND status = 'active' ORDER BY upload_date DESC";
    return dbSelect($query, [$courseId]);
}

/**
 * Get announcements
 */
function getAnnouncements($limit = 10) {
    $query = "SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT ?";
    return dbSelect($query, [$limit]);
}

/**
 * Get payments by student
 */
function getPaymentsByStudent($studentId) {
    $query = "SELECT p.*, c.title as course_title 
              FROM payments p 
              LEFT JOIN courses c ON p.course_id = c.course_id 
              WHERE p.student_id = ? 
              ORDER BY p.payment_date DESC";
    return dbSelect($query, [$studentId]);
}

/**
 * Get platform statistics
 */
function getPlatformStatistics() {
    $stats = [];
    
    // Total users by role
    $stats['total_users'] = dbSelectOne("SELECT COUNT(*) as count FROM users")['count'];
    $stats['total_students'] = dbSelectOne("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
    $stats['total_teachers'] = dbSelectOne("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")['count'];
    $stats['total_admins'] = dbSelectOne("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")['count'];
    
    // Courses
    $stats['total_courses'] = dbSelectOne("SELECT COUNT(*) as count FROM courses WHERE status = 'published'")['count'];
    $stats['total_enrollments'] = dbSelectOne("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'")['count'];
    
    // Payments
    $stats['total_payments'] = dbSelectOne("SELECT COUNT(*) as count FROM payments WHERE status = 'completed'")['count'];
    $stats['total_revenue'] = dbSelectOne("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'")['total'] ?? 0;
    
    return $stats;
}

/**
 * Search courses
 */
function searchCourses($keyword)
{
    // SECURE MODE
    if (!isVulnerabilityEnabled('sql_injection'))
    {
        $query = "SELECT c.*, 
                         u.first_name,
                         u.last_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS instructor_name
                  FROM courses c
                  JOIN teachers t ON c.instructor_id = t.teacher_id
                  JOIN users u ON t.user_id = u.user_id
                  WHERE c.status = 'published'
                  AND (
                        c.title LIKE ?
                        OR c.description LIKE ?
                        OR c.category LIKE ?
                  )
                  ORDER BY c.created_at DESC";

        $param = "%$keyword%";

        return dbSelect(
            $query,
            [$param, $param, $param]
        );
    }

    // VULNERABLE MODE
    else
    {
        $query = "SELECT c.*, 
                         u.first_name,
                         u.last_name,
                         CONCAT(u.first_name, ' ', u.last_name) AS instructor_name
                  FROM courses c
                  JOIN teachers t ON c.instructor_id = t.teacher_id
                  JOIN users u ON t.user_id = u.user_id
                  WHERE c.status = 'published'
                  AND c.title = '$keyword'
                  ORDER BY c.created_at DESC";

        return dbSelect($query);
    }
}

/**
 * Pagination helper
 */
function paginate($query, $params, $page = 1, $perPage = ITEMS_PER_PAGE) {
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as temp";
    $total = dbSelectOne($countQuery, $params)['total'];
    
    // Get paginated results
    $paginatedQuery = $query . " LIMIT $perPage OFFSET $offset";
    $results = dbSelect($paginatedQuery, $params);
    
    return [
        'data' => $results,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ];
}

/**
 * Check if protection is enabled for a specific vulnerability
 * Uses the new environment-based toggle system
 */
function isVulnerabilityEnabled($name)
{
    $normalized = strtolower(trim($name));
    $aliases = [
        'sqli_enabled' => 'sql_injection',
        'xss_enabled' => 'stored_xss',
        'idor_enabled' => 'idor',
        'weak_auth_enabled' => 'sql_injection',
        'upload_enabled' => 'backup_file_exposure',
        'csrf_enabled' => 'http_api_communication',
    ];
    $key = $aliases[$normalized] ?? $normalized;

    $setting = dbSelectOne(
        "SELECT enabled FROM security_settings WHERE vulnerability_name = ? LIMIT 1",
        [$key]
    );
    return $setting ? ((int)$setting['enabled'] === 1) : false;
}

function isProtectionEnabled($name)
{
    return !isVulnerabilityEnabled($name);
}

function enableVulnerability($name)
{
    $updated = dbUpdate(
        "UPDATE security_settings SET enabled = 1 WHERE vulnerability_name = ?",
        [$name]
    );
    applyVulnerabilitySideEffects($name, true);
    error_log("enableVulnerability called for: $name");
    return $updated;
}

function disableVulnerability($name)
{
    $updated = dbUpdate(
        "UPDATE security_settings SET enabled = 0 WHERE vulnerability_name = ?",
        [$name]
    );
    applyVulnerabilitySideEffects($name, false);
    error_log("disableVulnerability called for: $name");
    return $updated;
}

function applyVulnerabilitySideEffects($name, $enabled)
{
    if ($name === 'backup_file_exposure') {
        syncBackupFileExposure($enabled);
    } elseif ($name === 'weak_ssh_credentials') {
        $script = $enabled ? '/var/www/html/scripts/enable_weak_ssh.sh' : '/var/www/html/scripts/disable_weak_ssh.sh';
        $logFile = '/var/www/html/storage/ssh_toggle.log';
        $logMsg = date('Y-m-d H:i:s') . " - Toggle: " . ($enabled ? 'ON' : 'OFF') . " - Script: $script\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);
        
        if (is_file($script)) {
            $output = [];
            $returnVar = 0;
            exec('sudo ' . escapeshellarg($script) . ' 2>&1', $output, $returnVar);
            $outputStr = implode("\n", $output);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Return: $returnVar - Output: " . $outputStr . "\n", FILE_APPEND);
        } else {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Script not found\n", FILE_APPEND);
        }
    } elseif ($name === 'sudo_misconfiguration') {
        $script = $enabled ? '/var/www/html/scripts/enable_weak_sudo.sh' : '/var/www/html/scripts/disable_weak_sudo.sh';
        $logFile = '/var/www/html/storage/sudo_toggle.log';
        $logMsg = date('Y-m-d H:i:s') . " - Toggle: " . ($enabled ? 'ON' : 'OFF') . " - Script: $script\n";
        @file_put_contents($logFile, $logMsg, FILE_APPEND);
        
        if (is_file($script)) {
            $output = [];
            $returnVar = 0;

            exec('sudo ' . escapeshellarg($script) . ' 2>&1', $output, $returnVar);

            $outputStr = implode("\n", $output);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Return: $returnVar - Output: " . $outputStr . "\n", FILE_APPEND);
        } else {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Script not found\n", FILE_APPEND);
        }
    } elseif ($name === 'weak_password_hashing') {
        syncDatabaseExposure($enabled);
    }
}

function syncBackupFileExposure($enabled)
{
    $root = dirname(__DIR__, 2);
    $webBackupDir = $root . '/backups';
    $webBackupFile = $webBackupDir . '/backup.sql';
    $safeBackupDir = $root . '/storage/backups';
    $safeBackupFile = $safeBackupDir . '/backup.sql';

    if (!is_dir($safeBackupDir)) {
        mkdir($safeBackupDir, 0755, true);
    }
    if (!file_exists($safeBackupFile)) {
        file_put_contents($safeBackupFile, "-- Demo backup placeholder\n");
    }

    if ($enabled) {
        if (!is_dir($webBackupDir)) {
            mkdir($webBackupDir, 0755, true);
        }
        copy($safeBackupFile, $webBackupFile);
    } else {
        if (file_exists($webBackupFile)) {
            unlink($webBackupFile);
        }
    }
}

function syncDatabaseExposure($enabled)
{
    $root = dirname(__DIR__, 2);
    $exposureFile = $root . '/admin/user-database-exposure.php';
    
    if ($enabled) {
        // Create exposure endpoint
        if (!file_exists($exposureFile)) {
            $content = '<?php
require_once "../app/config/config.php";
require_once "../app/security/functions.php";
require_once "../app/security/auth.php";

// Check if vulnerability is enabled
if (!isVulnerabilityEnabled("weak_password_hashing")) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>Access Denied</p>";
    exit;
}

// Get all users with passwords
$users = dbSelect("SELECT user_id, email, password, first_name, last_name, role, status FROM users ORDER BY user_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Database Exposure</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .warning { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1 class="warning">⚠️ VULNERABILITY: User Database Exposure</h1>
    <p>This page exposes sensitive user information including passwords.</p>
    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>Email</th>
                <th>Password</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Role</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user["user_id"]); ?></td>
                <td><?php echo htmlspecialchars($user["email"]); ?></td>
                <td class="warning"><?php echo htmlspecialchars($user["password"]); ?></td>
                <td><?php echo htmlspecialchars($user["first_name"]); ?></td>
                <td><?php echo htmlspecialchars($user["last_name"]); ?></td>
                <td><?php echo htmlspecialchars($user["role"]); ?></td>
                <td><?php echo htmlspecialchars($user["status"]); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>';
            file_put_contents($exposureFile, $content);
        }
    } else {
        // Remove exposure endpoint
        if (file_exists($exposureFile)) {
            unlink($exposureFile);
        }
    }
}

function getApiBaseUrl()
{
    $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
    if (isVulnerabilityEnabled('http_api_communication')) {
        return 'http://' . $host . ':8080/api';
    }
    return 'https://' . $host . ':8443/api';
}

function isRequestHttps()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    return false;
}

function enforceApiTransportPolicy()
{
    $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';

    $httpsRequired = !isVulnerabilityEnabled('http_api_communication');
    $isHttps = isRequestHttps();

    // SECURE MODE
    if ($httpsRequired && !$isHttps) {

        $target =
            'https://' .
            $host .
            ':8443' .
            $_SERVER['REQUEST_URI'];

        header("Location: $target");
        exit;
    }

    // VULNERABLE MODE
    if (!$httpsRequired && $isHttps) {

        $target =
            'http://' .
            $host .
            ':8080' .
            $_SERVER['REQUEST_URI'];

        header("Location: $target");
        exit;
    }
}

function enforceWebsiteTransportPolicy()
{
    $host = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';

    $httpsRequired = !isVulnerabilityEnabled('http_api_communication');
    $isHttps = isRequestHttps();

    // SECURE MODE
    if ($httpsRequired && !$isHttps) {

        $target =
            'https://' .
            $host .
            ':8443' .
            $_SERVER['REQUEST_URI'];

        header("Location: $target");
        exit;
    }

    // VULNERABLE MODE
    if (!$httpsRequired && $isHttps) {

        $target =
            'http://' .
            $host .
            ':8080' .
            $_SERVER['REQUEST_URI'];

        header("Location: $target");
        exit;
    }
}

// Apply website transport policy
if (
    isset($_SERVER['REQUEST_URI']) &&
    strpos($_SERVER['REQUEST_URI'], '/api/') === false
) {
    enforceWebsiteTransportPolicy();
}