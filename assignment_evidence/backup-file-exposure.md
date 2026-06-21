# Backup File Exposure Vulnerability

## Purpose
This vulnerability demonstrates how exposed backup files can lead to data breaches. When enabled, a database backup file is copied to a web-accessible directory, allowing unauthorized download of sensitive data.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Backup File Exposure" checkbox
- **Database Key**: `backup_file_exposure` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `app/security/functions.php` (lines 718-745) - syncBackupFileExposure function
  - `database/schema.sql` - Contains backup data structure
  - `backups/` directory - Web-accessible backup location

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Backup File Exposure" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `backup_file_exposure` row
6. The `applyVulnerabilitySideEffects()` function in `functions.php` calls `syncBackupFileExposure()`

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('backup_file_exposure')` returns true:

**Backup File Exposure Function (app/security/functions.php, lines 718-745)**:
```php
function syncBackupFileExposure($enabled) {
    $root = dirname(__DIR__, 2);
    $sourceFile = $root . '/storage/backups/backup.sql';
    $exposedFile = $root . '/backups/backup.sql';
    
    if ($enabled) {
        // VULNERABLE - Copy backup to web-accessible location
        if (file_exists($sourceFile)) {
            copy($sourceFile, $exposedFile);
            logAudit('BACKUP_EXPOSED', 'system', null, 'Backup file exposed to web');
            return true;
        }
    } else {
        // SECURE - Remove backup from web-accessible location
        if (file_exists($exposedFile)) {
            unlink($exposedFile);
            logAudit('BACKUP_SECURED', 'system', null, 'Backup file removed from web');
            return true;
        }
    }
    return false;
}
```
Copies `storage/backups/backup.sql` to `backups/backup.sql` (web-accessible).

**Exposed Location**: `http://localhost:8080/backups/backup.sql`

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('backup_file_exposure')` returns false:

**Backup File Removal**:
```php
if (file_exists($exposedFile)) {
    unlink($exposedFile);
    logAudit('BACKUP_SECURED', 'system', null, 'Backup file removed from web');
    return true;
}
```
Deletes `backups/backup.sql` from web-accessible location.

**Secure Storage**: Backup remains in `storage/backups/backup.sql` (not web-accessible).

## Testing Procedures

### Test 1: Access Exposed Backup File
**Prerequisites**: Backup File Exposure CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Backup File Exposure"
3. Click "Save Security Settings"
4. Access URL: `http://localhost:8080/backups/backup.sql`
5. **Expected Vulnerable Result**: Backup file downloads successfully, containing sensitive database data
6. **Expected Secure Result**: 404 Not Found error

### Test 2: Verify Backup File Content
**Prerequisites**: Backup File Exposure CHECKED (vulnerable mode)

1. Download backup file from: `http://localhost:8080/backups/backup.sql`
2. Open file in text editor
3. **Expected Vulnerable Result**: File contains SQL statements with user data, passwords (hashed), course data, etc.
4. Search for sensitive data: email addresses, password hashes, personal information

### Test 3: Secure Mode Verification
**Prerequisites**: Backup File Exposure UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Backup File Exposure"
3. Click "Save Security Settings"
4. Access URL: `http://localhost:8080/backups/backup.sql`
5. **Expected Result**: 404 Not Found error
6. Verify file exists in secure location: `storage/backups/backup.sql`

### Test 4: Toggle Verification
**Prerequisites**: Docker container running

1. Check if backup file exists: `ls -la backups/backup.sql`
2. Navigate to `/admin/security-settings.php`
3. Check "Backup File Exposure"
4. Click "Save Security Settings"
5. Check if backup file exists: `ls -la backups/backup.sql`
6. **Expected Result**: File now exists in backups/ directory
7. Uncheck "Backup File Exposure"
8. Click "Save Security Settings"
9. Check if backup file exists: `ls -la backups/backup.sql`
10. **Expected Result**: File removed from backups/ directory

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of successful download from `http://localhost:8080/backups/backup.sql`
- Screenshot of backup file content showing sensitive data
- Screenshot of security settings with checkbox checked
- Terminal output showing file copied to web-accessible location

### Secure Mode Evidence
- Screenshot of 404 error when accessing `http://localhost:8080/backups/backup.sql`
- Screenshot of security settings with checkbox unchecked
- Terminal output showing file removed from web-accessible location
- Verification that backup still exists in secure storage location

## Known Dependencies
**Backup File Required**: The vulnerability requires a backup file to exist in `storage/backups/backup.sql`. If this file doesn't exist, the exposure will not work.

**Web Server Configuration**: The `backups/` directory must be accessible via the web server. In this project, it's at the web root level.

## Remediation
Never store backup files in web-accessible directories:
- Store backups outside web root (e.g., `/var/backups/` or `storage/backups/`)
- Use proper file permissions (0640 or 0600) on backup files
- Encrypt backup files containing sensitive data
- Implement access controls on backup storage directories
- Regularly audit web-accessible directories for exposed files
- Use .htaccess or web server configuration to deny access to backup directories
- Implement automated backup rotation and secure deletion
