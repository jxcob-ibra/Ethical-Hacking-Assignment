<?php
// 1. Set REST API delivery headers for JSON communication
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

// 2. Include your platform's core code relative to this api directory
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/security/functions.php';

enforceApiTransportPolicy();

// Start session to capture the active authenticated user context dynamically
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Establish the database connection using your native PDO structure
try {
    $database = Database::getInstance();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failure."]);
    exit();
}

$request_method = $_SERVER["REQUEST_METHOD"];

if ($request_method === 'GET') {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $force_mode = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : '';
    
    // 4. Extract active user session states (Defaults to Guest context if unauthenticated)
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : "Guest/Unauthenticated";
    $user_name  = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";
    $user_role  = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : "None";

    // 5. DYNAMIC LOOKUP: Crawl and fetch ALL vulnerabilities in the database table
    $all_vulnerabilities = [];
    $sqli_active = false;

    try {
        $stmt = $db->query("SELECT vulnerability_name, enabled, description FROM security_settings ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $name = $row['vulnerability_name'];
            $is_enabled = ((int)$row['enabled'] === 1);
            
            // Build a dynamic profile status mapping for every row item found
            $all_vulnerabilities[$name] = [
                "status" => $is_enabled ? "VULNERABLE (Enabled)" : "SECURE (Disabled)",
                "description" => $row['description']
            ];
            
            // Check if SQL injection specifically is active to fork database engines downstream
            if ($name === 'sql_injection' && $is_enabled) {
                $sqli_active = true;
            }
        }
    } catch (Exception $e) {
        // Fallback programmatic discovery loop if the schema description query fails
        $defined_keys = ['sql_injection', 'stored_xss', 'idor', 'weak_ssh_credentials', 'backup_file_exposure', 'weak_password_hashing', 'http_api_communication'];
        if (function_exists('isVulnerabilityEnabled')) {
            foreach ($defined_keys as $k) {
                $is_enabled = isVulnerabilityEnabled($k);
                $all_vulnerabilities[$k] = [
                    "status" => $is_enabled ? "VULNERABLE (Enabled)" : "SECURE (Disabled)"
                ];
                if ($k === 'sql_injection' && $is_enabled) {
                    $sqli_active = true;
                }
            }
        }
    }

    // 6. Master URL Parameter Override Logic (?mode=vulnerable / ?mode=secure)
    if ($force_mode === 'vulnerable') {
        $sqli_active = true;
        $all_vulnerabilities['sql_injection']['status'] = "VULNERABLE (Enabled - Overridden)";
    } elseif ($force_mode === 'secure') {
        $sqli_active = false;
        $all_vulnerabilities['sql_injection']['status'] = "SECURE (Disabled - Overridden)";
    }

    // 7. Route Data Query Extraction Layer based on SQL Injection State
    if ($sqli_active) {
        $sql_query_engine = "VULNERABLE (Concatenated Raw String Mode)";
        if (!empty($search)) {
            $query = "SELECT course_id, title, description, category, price FROM courses WHERE title LIKE '%" . $search . "%' AND status = 'published'";
        } else {
            $query = "SELECT course_id, title, description, category, price FROM courses WHERE status = 'published'";
        }
        
        try {
            $stmt = $db->query($query);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            http_response_code(400);
            echo json_encode([
                "api_component" => "REST API Engine v1.0",
                "current_user" => ["name" => $user_name, "email" => $user_email, "role" => $user_role],
                "error" => "SQL Syntax Exception logged under raw concatenation processing.",
                "message" => $ex->getMessage()
            ]);
            exit();
        }
    } else {
        $sql_query_engine = "SECURE (Parameterized Bound Parameter Statements)";
        if (!empty($search)) {
            $query = "SELECT course_id, title, description, category, price FROM courses WHERE title LIKE :search AND status = 'published'";
            $stmt = $db->prepare($query);
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        } else {
            $query = "SELECT course_id, title, description, category, price FROM courses WHERE status = 'published'";
            $stmt = $db->prepare($query);
        }
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 8. Output Structured Comprehensive Response Payload
    http_response_code(200);
    echo json_encode([
        "api_component" => "REST API Architecture Profile",
        "current_session_context" => [
            "authenticated_user" => $user_name,
            "account_email" => $user_email,
            "access_role" => $user_role
        ],
        "live_vulnerability_matrix" => [
            "total_vulnerabilities_monitored" => count($all_vulnerabilities),
            "vulnerability_profiles" => $all_vulnerabilities,
            "current_sql_engine_state" => $sql_query_engine
        ],
        "course_catalog_data" => [
            "record_count" => count($courses),
            "records" => $courses
        ]
    ], JSON_PRETTY_PRINT);

} else {
    http_response_code(405);
    echo json_encode(["message" => "HTTP Method Not Allowed"]);
}
?>