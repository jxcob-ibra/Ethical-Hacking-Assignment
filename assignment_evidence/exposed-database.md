# Exposed Database Vulnerability

## Purpose
This vulnerability demonstrates how exposed database access points can lead to unauthorized data access. When enabled, a PHP page is created that exposes user data including passwords, and database ports are made accessible without proper authentication.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Exposed Database" checkbox
- **Database Key**: `exposed_database` in `security_settings` table
- **Environment Variable**: `EXPOSED_DATABASE_ENABLED` in `.env` file
- **Implementation Files**:
  - `app/security/functions.php` (lines 785-822) - syncDatabaseExposure function
  - `admin/user-database-exposure.php` - Dynamically created exposure page
  - `docker-compose.yml` - MySQL port mapping (3307) and phpMyAdmin port (8081)

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Exposed Database" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `exposed_database` row
6. The `applyVulnerabilitySideEffects()` function in `functions.php` calls `syncDatabaseExposure()`

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('exposed_database')` returns true:

**Database Exposure Sync Function (app/security/functions.php, lines 785-822)**:
```php
function syncDatabaseExposure($enabled) {
    $root = dirname(__DIR__, 2);
    $exposureFile = $root . '/admin/user-database-exposure.php';
    
    if ($enabled) {
        // VULNERABLE - Create exposure page
        $content = '<?php
require_once "../app/config/config.php";
require_once "../app/config/database.php";

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT user_id, email, password, first_name, last_name, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json");
echo json_encode(["users" => $users], JSON_PRETTY_PRINT);
?>';
        
        file_put_contents($exposureFile, $content);
        logAudit('DATABASE_EXPOSED', 'system', null, 'User database exposure page created');
        return true;
    } else {
        // SECURE - Remove exposure page
        if (file_exists($exposureFile)) {
            unlink($exposureFile);
            logAudit('DATABASE_SECURED', 'system', null, 'User database exposure page removed');
            return true;
        }
    }
    return false;
}
```
Creates `admin/user-database-exposure.php` that returns all user data including passwords in JSON format.

**Exposed URL**: `http://localhost:8080/admin/user-database-exposure.php`

**Docker Configuration (docker-compose.yml)**:
```yaml
mysql:
  ports:
    - "3307:3306"  # MySQL exposed on host port 3307

phpmyadmin:
  ports:
    - "8081:80"  # phpMyAdmin exposed on host port 8081
```
MySQL and phpMyAdmin ports are mapped to host ports, making them accessible.

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('exposed_database')` returns false:

**Database Exposure Sync Function**:
```php
if ($enabled) {
    // Create exposure page
} else {
    // SECURE - Remove exposure page
    if (file_exists($exposureFile)) {
        unlink($exposureFile);
        logAudit('DATABASE_SECURED', 'system', null, 'User database exposure page removed');
        return true;
    }
}
```
Deletes `admin/user-database-exposure.php` if it exists.

**Note**: Docker port mappings remain in place even in secure mode. To fully secure the database, you would need to modify `docker-compose.yml` to remove port mappings or restrict access via firewall rules.

## Testing Procedures

### Test 1: Access Database Exposure Page (Vulnerable Mode)
**Prerequisites**: Exposed Database CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Exposed Database"
3. Click "Save Security Settings"
4. Access URL: `http://localhost:8080/admin/user-database-exposure.php`
5. **Expected Vulnerable Result**: JSON response containing all user data including email addresses and password hashes
6. Verify data includes: user_id, email, password, first_name, last_name, role

### Test 2: Access Database Exposure Page (Secure Mode)
**Prerequisites**: Exposed Database UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Exposed Database"
3. Click "Save Security Settings"
4. Access URL: `http://localhost:8080/admin/user-database-exposure.php`
5. **Expected Secure Result**: 404 Not Found error
6. Verify file does not exist: `ls -la admin/user-database-exposure.php`

### Test 3: Access MySQL Directly (Vulnerable Mode)
**Prerequisites**: Docker container running, Exposed Database CHECKED

1. Connect to MySQL via exposed port: `mysql -h 127.0.0.1 -P 3307 -u root -prootpassword myeduconnect`
2. **Expected Result**: Successful connection to database
3. Query: `SELECT * FROM users;`
4. **Expected Result**: All user data returned

### Test 4: Access phpMyAdmin (Vulnerable Mode)
**Prerequisites**: Docker container running

1. Access phpMyAdmin: `http://localhost:8081`
2. Login with: root / rootpassword
3. **Expected Result**: Full database access via web interface
4. Navigate to `myeduconnect` database
5. Browse `users` table
6. **Expected Result**: Can view all user data including passwords

### Test 5: Toggle Verification
**Prerequisites**: Docker container running

1. Check if exposure file exists: `ls -la admin/user-database-exposure.php`
2. Navigate to `/admin/security-settings.php`
3. Check "Exposed Database"
4. Click "Save Security Settings"
5. Check if exposure file exists: `ls -la admin/user-database-exposure.php`
6. **Expected Result**: File now exists
7. Access URL: `http://localhost:8080/admin/user-database-exposure.php`
8. **Expected Result**: JSON data returned
9. Uncheck "Exposed Database"
10. Click "Save Security Settings"
11. Check if exposure file exists: `ls -la admin/user-database-exposure.php`
12. **Expected Result**: File removed

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of JSON response from `admin/user-database-exposure.php`
- Screenshot showing user emails and password hashes exposed
- Screenshot of security settings with checkbox checked
- Screenshot of phpMyAdmin access showing user data
- Screenshot of MySQL command-line access

### Secure Mode Evidence
- Screenshot of 404 error when accessing `admin/user-database-exposure.php`
- Screenshot of security settings with checkbox unchecked
- Verification that exposure file does not exist
- Note: Docker port mappings remain (would need docker-compose.yml modification for full security)

## Known Dependencies
**Docker Port Mappings**: The MySQL (3307) and phpMyAdmin (8081) port mappings in `docker-compose.yml` are always active regardless of the toggle state. The toggle only controls the PHP exposure page creation.

**File Write Permissions**: The web server user must have write permissions to the `admin/` directory to create/delete the exposure page.

## Remediation
Never expose database access points without proper authentication:
- Remove or restrict database port mappings in docker-compose.yml
- Use VPN or SSH tunneling for database access
- Implement strong authentication for database tools
- Never create pages that dump sensitive data
- Use firewall rules to restrict database access
- Implement database connection whitelisting
- Use database-specific user accounts with least privilege
- Enable SSL/TLS for database connections
- Regularly audit database access logs
- Remove phpMyAdmin from production environments
