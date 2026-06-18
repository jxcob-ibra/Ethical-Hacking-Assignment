# Backup File Exposure Testing Guide

## STEP 1: Start the Project

Start the Docker containers:

```bash
docker compose down
docker compose build
docker compose up -d
```

Wait for all containers to be healthy (approximately 30-60 seconds).

Verify containers are running:

```bash
docker compose ps
```

Expected output:
- myeduconnect-web: Up
- myeduconnect-mysql: Up
- myeduconnect-phpmyadmin: Up

## STEP 2: Verify Application Loads

Open your browser and navigate to:

```
http://localhost:8080
```

Expected result:
- MyEduConnect homepage loads successfully
- No errors displayed

## STEP 3: Login as Administrator

Navigate to the login page:

```
http://localhost:8080/login.php
```

Login with admin credentials:
- **Email:** admin@myeduconnect.com
- **Password:** password

Expected result:
- Successfully logged in as administrator
- Redirected to admin dashboard

## STEP 4: Access Security Settings

Navigate to Security Settings:

**Navigation Path:**
1. Admin Dashboard
2. Click "Security Settings" in the sidebar (or dropdown menu)

**URL:**
```
http://localhost:8080/admin/security-settings.php
```

Expected result:
- Security Vulnerability Manager page loads
- Shows toggle switches for all vulnerabilities
- "Backup File Exposure" toggle is visible

## STEP 5: Disable Security (Enable Vulnerability)

**Current State Check:**
- Verify "Backup File Exposure" toggle is OFF (unchecked)
- This means Security is ON (vulnerability disabled)

**Enable the Vulnerability:**
1. Check the "Backup File Exposure" toggle
2. Click "Save Security Settings" button

Expected result:
- Success message: "Security Vulnerability Manager updated."
- "Backup File Exposure" toggle is now ON (checked)
- File is copied to web-accessible location

**Verify File Placement:**

Check if file exists in web-accessible directory:

```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

Expected output:
```
-rwxrwxrwx 1 root root  455 [timestamp] backup.sql
```

## STEP 6: Exploit Vulnerability (Security OFF)

Open your browser and navigate to:

```
http://localhost:8080/backups/backup.sql
```

Expected result:
- Browser displays or downloads the backup.sql file
- File content is visible:

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

**Why This is a Vulnerability:**

1. **No Authentication Required:** Anyone can access the file without logging in
2. **Sensitive Data Exposure:** Email addresses and password hashes are visible
3. **Offline Attack Potential:** Attackers can download the file and perform offline brute-force attacks on password hashes
4. **Data Breach:** All user data in the backup is compromised
5. **Predictable Location:** Attackers can guess common backup file names and locations

## STEP 7: Enable Security (Disable Vulnerability)

Return to Security Settings:

```
http://localhost:8080/admin/security-settings.php
```

Disable the vulnerability:
1. Uncheck the "Backup File Exposure" toggle
2. Click "Save Security Settings" button

Expected result:
- Success message: "Security Vulnerability Manager updated."
- "Backup File Exposure" toggle is now OFF (unchecked)
- File is removed from web-accessible location

**Verify File Removal:**

Check if file is removed from web-accessible directory:

```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

Expected output:
```
-rwxrwxrwx 1 root root    0 [timestamp] .gitkeep
```

The `backup.sql` file should be gone.

## STEP 8: Retest (Security ON)

Open your browser and navigate to:

```
http://localhost:8080/backups/backup.sql
```

Expected result:
- **404 Not Found** error
- OR **403 Forbidden** error
- File is not accessible

**Why the Attack Now Fails:**

1. **File Removed:** The backup file is no longer in the web-accessible directory
2. **Secure Storage:** The safe copy remains in `storage/backups/backup.sql` which is not accessible via web server
3. **Access Denied:** Web server returns 404/403 because the file doesn't exist in the web root
4. **Protection Enabled:** The security toggle successfully prevents unauthorized access

## STEP 9: Verify Other Vulnerabilities Unchanged

Verify that other vulnerabilities still work as before:

1. **SQL Injection:** Test login with `' OR '1'='1` (if enabled)
2. **XSS:** Test stored XSS in user profile (if enabled)
3. **IDOR:** Test accessing other users' data (if enabled)

Expected result:
- Other vulnerabilities behave exactly as before
- No changes to unrelated features
- Only Backup File Exposure is controlled by the toggle

## STEP 10: Verify Database State

Check the security_settings table:

```bash
docker exec myeduconnect-mysql mysql -uroot -prootpassword myeduconnect -e "SELECT vulnerability_name, enabled FROM security_settings WHERE vulnerability_name = 'backup_file_exposure';"
```

Expected output when disabled:
```
vulnerability_name    enabled
backup_file_exposure  0
```

Expected output when enabled:
```
vulnerability_name    enabled
backup_file_exposure  1
```

## Assignment Evidence Checklist

### Required Screenshots

**Screenshot 1: Security Toggle OFF (Vulnerability Enabled)**
- Navigate to: http://localhost:8080/admin/security-settings.php
- Enable "Backup File Exposure" toggle
- Save settings
- Capture the page showing the toggle is ON

**Screenshot 2: Accessible backup.sql**
- Navigate to: http://localhost:8080/backups/backup.sql
- Capture the browser showing the backup file content
- Show the SQL file with email addresses and password hashes

**Screenshot 3: Sensitive Data Visible**
- Zoom in on the sensitive data in the backup file
- Highlight the email addresses and password hashes
- Show the INSERT statements with user credentials

**Screenshot 4: Security Toggle ON (Vulnerability Disabled)**
- Navigate to: http://localhost:8080/admin/security-settings.php
- Disable "Backup File Exposure" toggle
- Save settings
- Capture the page showing the toggle is OFF

**Screenshot 5: Access Denied**
- Navigate to: http://localhost:8080/backups/backup.sql
- Capture the 404 Not Found or 403 Forbidden error
- Show that the file is no longer accessible

**Screenshot 6: Before/After Comparison**
- Create a side-by-side comparison
- Left side: File accessible with sensitive data
- Right side: Access denied error
- Clearly label "Vulnerability Enabled" vs "Vulnerability Disabled"

### Additional Evidence

**Terminal Output - File Exists (Enabled):**
```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

**Terminal Output - File Removed (Disabled):**
```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

**Database State:**
```bash
docker exec myeduconnect-mysql mysql -uroot -prootpassword myeduconnect -e "SELECT * FROM security_settings WHERE vulnerability_name = 'backup_file_exposure';"
```

## Troubleshooting

### File Not Accessible When Enabled

If the file is not accessible after enabling the toggle:

1. Check if the file was copied:
```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

2. Check file permissions:
```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/backup.sql
```

3. Manually trigger sync (if needed):
```bash
docker exec myeduconnect-web php -r "require_once 'app/security/functions.php'; syncBackupFileExposure(true);"
```

### File Still Accessible When Disabled

If the file is still accessible after disabling the toggle:

1. Check if the file was removed:
```bash
docker exec myeduconnect-web ls -la /var/www/html/backups/
```

2. Manually remove the file:
```bash
docker exec myeduconnect-web rm /var/www/html/backups/backup.sql
```

3. Clear browser cache to ensure you're not seeing a cached version

### Toggle Not Working

If the toggle doesn't seem to work:

1. Check database state:
```bash
docker exec myeduconnect-mysql mysql -uroot -prootpassword myeduconnect -e "SELECT * FROM security_settings;"
```

2. Check PHP error logs:
```bash
docker exec myeduconnect-web tail -f /var/log/apache2/error.log
```

3. Verify the syncBackupFileExposure function is being called by checking the code in app/security/functions.php