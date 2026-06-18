# Backup File Exposure Vulnerability

## Purpose

This vulnerability demonstrates the security risk of exposing sensitive database backup files in web-accessible directories. Attackers can discover and download these files through directory enumeration or direct URL access, potentially gaining access to credentials, user data, and other sensitive information.

## How It Works

The vulnerability is controlled by the `backup_file_exposure` security toggle in the Admin Panel:

- **Security OFF (Vulnerability Enabled):** The backup file `backup.sql` is copied from the secure storage location (`storage/backups/backup.sql`) to the web-accessible directory (`backups/backup.sql`), making it downloadable via browser.

- **Security ON (Vulnerability Disabled):** The backup file is removed from the web-accessible directory, preventing unauthorized access while keeping the safe copy in storage.

## Implementation Details

### File Locations

- **Safe Storage:** `storage/backups/backup.sql` (not web-accessible)
- **Web-Accessible:** `backups/backup.sql` (only exists when vulnerability is enabled)
- **Access URL:** `http://localhost:8080/backups/backup.sql`

### Control Function

The `syncBackupFileExposure()` function in `app/security/functions.php` (lines 611-636) handles the file placement:

```php
function syncBackupFileExposure($enabled)
{
    $root = dirname(__DIR__, 2);
    $webBackupDir = $root . '/backups';
    $webBackupFile = $webBackupDir . '/backup.sql';
    $safeBackupDir = $root . '/storage/backups';
    $safeBackupFile = $safeBackupDir . '/backup.sql';

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
```

### Database Integration

The vulnerability is stored in the `security_settings` table:

```sql
INSERT INTO security_settings (vulnerability_name, description, enabled) VALUES
('backup_file_exposure', 'Exposes database backup file from web-accessible path.', 0);
```

## Security OFF Behavior (Vulnerability Enabled)

When the `backup_file_exposure` toggle is enabled:

1. The file `storage/backups/backup.sql` is copied to `backups/backup.sql`
2. The file becomes accessible at: `http://localhost:8080/backups/backup.sql`
3. Anyone with the URL can download the backup file
4. The backup contains sensitive data including email addresses and password hashes

### Example Backup File Content

```sql
-- Demo database backup (assignment lab artifact)
-- Contains sample credentials for demonstration only.

CREATE TABLE IF NOT EXISTS users_backup_demo (
  email VARCHAR(255),
  password_hash VARCHAR(255)
);

INSERT INTO users_backup_demo (email, password_hash) VALUES
('admin@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

## Security ON Behavior (Vulnerability Disabled)

When the `backup_file_exposure` toggle is disabled:

1. The file `backups/backup.sql` is removed from the web-accessible directory
2. Accessing `http://localhost:8080/backups/backup.sql` returns 404 Not Found
3. The safe copy remains in `storage/backups/backup.sql` for legitimate backup purposes
4. Attackers cannot download the backup file through the browser

## Impact of Exploitation

If an attacker discovers and downloads the exposed backup file:

1. **Credential Exposure:** Email addresses and password hashes are revealed
2. **Offline Attacks:** Attackers can perform offline brute-force or rainbow table attacks on password hashes
3. **Data Breach:** All user data in the backup is compromised
4. **System Compromise:** Depending on backup contents, could lead to full system compromise
5. **Compliance Violations:** Violates data protection regulations (GDPR, HIPAA, etc.)

## Mitigation Strategy

### Secure Implementation

1. **Store Backups Outside Web Root:** Keep backup files in directories not accessible via web server
2. **Use Web Server Configuration:** Configure Apache/Nginx to deny access to backup directories
3. **Encrypt Backups:** Encrypt backup files at rest
4. **Access Controls:** Implement proper authentication and authorization for backup access
5. **Regular Audits:** Regularly scan for exposed backup files
6. **File Naming:** Use non-obvious filenames for backup files
7. **Access Logging:** Log and monitor backup file access attempts

### Apache Configuration Example

```apache
<Directory "/var/www/html/backups">
    Require all denied
</Directory>
```

### Nginx Configuration Example

```nginx
location /backups {
    deny all;
    return 404;
}
```

## Testing Instructions

See the complete testing guide in `BACKUP_FILE_EXPOSURE_TESTING_GUIDE.md`.
