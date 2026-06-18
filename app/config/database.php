<?php
/**
 * MyEduConnect - Database Connection Class
 * PDO Database Wrapper
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get database instance (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Database query helper functions
 */

/**
 * Execute a SELECT query
 */
function dbSelect($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a SELECT query and return single row
 */
function dbSelectOne($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Execute an INSERT query
 */
function dbInsert($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $db->lastInsertId();
}

/**
 * Execute an UPDATE query
 */
function dbUpdate($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    return $stmt->execute($params);
}

/**
 * Execute a DELETE query
 */
function dbDelete($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    return $stmt->execute($params);
}

/**
 * Execute any query
 */
function dbExecute($query, $params = []) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare($query);
    return $stmt->execute($params);
}
