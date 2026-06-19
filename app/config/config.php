<?php
/**
 * MyEduConnect - Configuration File
 * Learning Management System - Security Training Platform
 */

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'myeduconnect');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Application Configuration
define('APP_NAME', $_ENV['APP_NAME'] ?? 'MyEduConnect');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '2.0.0');

// Security Configuration
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'myeduconnect_session');
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600);
define('CSRF_TOKEN_NAME', $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token');
define('PASSWORD_MIN_LENGTH', $_ENV['PASSWORD_MIN_LENGTH'] ?? 8);

// Security Mode (GLOBAL)
define('SECURITY_MODE', $_ENV['SECURITY_MODE'] ?? 'vulnerable');

// Individual Vulnerability Toggles
define('SQLI_ENABLED', filter_var($_ENV['SQLI_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('XSS_ENABLED', filter_var($_ENV['XSS_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('IDOR_ENABLED', filter_var($_ENV['IDOR_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('UPLOAD_ENABLED', filter_var($_ENV['UPLOAD_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('WEAK_AUTH_ENABLED', filter_var($_ENV['WEAK_AUTH_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('CSRF_ENABLED', filter_var($_ENV['CSRF_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// File Upload Configuration
define('UPLOAD_DIR', $_ENV['UPLOAD_DIR'] ?? __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 5242880);
define('ALLOWED_FILE_TYPES', explode(',', $_ENV['ALLOWED_FILE_TYPES'] ?? 'pdf,doc,docx,ppt,pptx,txt,zip'));

// Email Configuration (for future use)
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM', $_ENV['SMTP_FROM'] ?? 'noreply@myeduconnect.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'MyEduConnect');

// Pagination
define('ITEMS_PER_PAGE', $_ENV['ITEMS_PER_PAGE'] ?? 10);

// Timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

// Error Reporting
error_reporting($_ENV['ERROR_REPORTING'] ?? E_ALL);
ini_set('display_errors', $_ENV['DISPLAY_ERRORS'] ?? 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

