# Weak File Permissions Vulnerability

## Purpose
This vulnerability demonstrates how weak file permissions on sensitive files can lead to unauthorized access. When enabled, sensitive files have overly permissive permissions (0666), allowing any user to read or modify them.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Weak File Permissions" checkbox
- **Database Key**: `weak_file_permissions` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `app/security/functions.php` (lines 747-783) - syncFilePermissions function
  - `app/security/functions.php` (lines 959-987) - getFilePermissionsStatus function
  - `admin/test-file-permissions.php` - Testing and diagnostic page

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Weak File Permissions" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `weak_file_permissions` row
6. The `applyVulnerabilitySideEffects()` function in `functions.php` calls `syncFilePermissions()`

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('weak_file_permissions')` returns true:

**File Permissions Sync Function (app/security/functions.php, lines 747-783)**:
```php
function syncFilePermissions($enabled) {
    $root = dirname(__DIR__, 2);
    $files = [
        $root . '/storage/backups/backup.sql',
        $root . '/storage/student_records.csv'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            if ($enabled) {
                // VULNERABLE - Set world-readable/writable permissions
                @chmod($file, 0666);
            } else {
                // SECURE - Restrict to owner/group read-only
                @chmod($file, 0640);
            }
        }
    }
    
    logAudit('FILE_PERMISSIONS_CHANGE', 'system', null, 
        $enabled ? 'Weak permissions applied (0666)' : 'Secure permissions applied (0640)');
}
```
Sets file permissions to 0666 (readable and writable by all users).

**Affected Files**:
- `storage/backups/backup.sql` - Database backup file
- `storage/student_records.csv` - Student records file

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('weak_file_permissions')` returns false:

**File Permissions Sync Function**:
```php
if ($enabled) {
    @chmod($file, 0666);
} else {
    // SECURE - Restrict to owner/group read-only
    @chmod($file, 0640);
}
```
Sets file permissions to 0640 (readable by owner and group only, not writable by others).

**Permission Status Function (app/security/functions.php, lines 959-987)**:
```php
function getFilePermissionsStatus() {
    $root = dirname(__DIR__, 2);
    $status = [];
    
    $files = [
        'backup.sql' => $root . '/storage/backups/backup.sql',
        'student_records.csv' => $root . '/storage/student_records.csv'
    ];
    
    foreach ($files as $name => $path) {
        if (file_exists($path)) {
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $expected = isVulnerabilityEnabled('weak_file_permissions') ? '0666' : '0640';
            $status[$name] = [
                'exists' => true,
                'permissions' => $perms,
                'expected' => $expected,
                'matches_expected' => $perms === $expected
            ];
        } else {
            $status[$name] = [
                'exists' => false,
                'permissions' => 'N/A',
                'expected' => 'N/A',
                'matches_expected' => false
            ];
        }
    }
    
    return $status;
}
```
Returns current permission status for monitoring.

## Testing Procedures

### Test 1: Check File Permissions (Vulnerable Mode)
**Prerequisites**: Weak File Permissions CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Weak File Permissions"
3. Click "Save Security Settings"
4. Navigate to `/admin/test-file-permissions.php`
5. **Expected Vulnerable Result**: Permissions show 0666 for sensitive files
6. Check permissions via command: `ls -la storage/backups/backup.sql`
7. **Expected Result**: `-rw-rw-rw-` (0666)

### Test 2: Check File Permissions (Secure Mode)
**Prerequisites**: Weak File Permissions UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Weak File Permissions"
3. Click "Save Security Settings"
4. Navigate to `/admin/test-file-permissions.php`
5. **Expected Secure Result**: Permissions show 0640 for sensitive files
6. Check permissions via command: `ls -la storage/backups/backup.sql`
7. **Expected Result**: `-rw-r-----` (0640)

### Test 3: Access Sensitive Files (Vulnerable Mode)
**Prerequisites**: Weak File Permissions CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Weak File Permissions"
3. Click "Save Security Settings"
4. Try to read backup file as non-owner user: `cat storage/backups/backup.sql`
5. **Expected Vulnerable Result**: File is readable by any user
6. Try to modify file: `echo "test" >> storage/backups/backup.sql`
7. **Expected Vulnerable Result**: File is writable by any user

### Test 4: Access Sensitive Files (Secure Mode)
**Prerequisites**: Weak File Permissions UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Weak File Permissions"
3. Click "Save Security Settings"
4. Try to read backup file as non-owner user: `cat storage/backups/backup.sql`
5. **Expected Secure Result**: Permission denied (if not owner or in group)
6. Try to modify file: `echo "test" >> storage/backups/backup.sql`
7. **Expected Secure Result**: Permission denied

### Test 5: Diagnostic Page Verification
**Prerequisites**: Any toggle state

1. Navigate to `/admin/test-file-permissions.php`
2. Review "Current Status" section
3. **Expected Result**: Shows current permissions for all monitored files
4. Review "BEFORE FIX" and "AFTER FIX" comparison
5. **Expected Result**: Shows expected vs actual permissions

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of `/admin/test-file-permissions.php` showing 0666 permissions
- Screenshot of terminal showing `-rw-rw-rw-` permissions
- Screenshot of security settings with checkbox checked
- Successful file read/write by non-owner user

### Secure Mode Evidence
- Screenshot of `/admin/test-file-permissions.php` showing 0640 permissions
- Screenshot of terminal showing `-rw-r-----` permissions
- Screenshot of security settings with checkbox unchecked
- Permission denied when non-owner tries to access files

## Known Dependencies
**Docker/Windows Limitations**: On Windows or certain Docker configurations, the `chmod()` function may not work as expected due to filesystem differences. The implementation includes `@` error suppression to handle these cases gracefully.

**File Existence Required**: The vulnerability only affects files that exist. If `storage/backups/backup.sql` or `storage/student_records.csv` don't exist, no permission changes will occur.

## Remediation
Always use restrictive file permissions for sensitive files:
- Use 0600 for files readable/writable only by owner
- Use 0640 for files readable by owner and group, writable only by owner
- Use 0400 for files readable only by owner (e.g., private keys)
- Never use world-writable permissions (0666, 0777) on sensitive files
- Store sensitive files outside web root
- Implement regular permission audits
- Use umask to set default restrictive permissions
- Apply principle of least privilege to file access
