# Weak File Permissions Vulnerability

## ROOT CAUSE ANALYSIS: Why "Permission Denied" Occurs

### The Problem

When testing the Weak File Permissions vulnerability, users reported that commands like `cat backup.sql` or `echo "test" >> backup.sql` produce "Permission denied" errors even when:
- The UI reports permissions as 0777 or 0666
- The vulnerability is marked as enabled
- The log file shows "Success: YES"

### Root Cause

The issue is **NOT** a bug in the chmod() function or the vulnerability implementation. The issue is **incorrect testing methodology**.

#### 1. Testing as Root User (False Positives)

Docker exec commands run as `root` by default. Root can bypass all Unix permission checks:

```bash
# INCORRECT - This will ALWAYS succeed, even in secure mode
docker exec myeduconnect-web cat /var/www/html/storage/backups/backup.sql
docker exec myeduconnect-web echo "test" >> /var/www/html/storage/backups/backup.sql
```

Root has UID 0 and can read/write ANY file regardless of permissions. Testing as root gives false positive results.

#### 2. Testing as Wrong User (False Negatives)

The README originally instructed users to use `su - nobody`, but the `nobody` user does not have a default shell configured in the container. This command fails:

```bash
# INCORRECT - This will fail with "This account is currently not available"
su - nobody
```

#### 3. The Correct Testing Approach

To accurately test the vulnerability, you must:

1. **Switch to a non-root, non-owner user** (e.g., `nobody` with explicit shell)
2. **Verify the vulnerability is actually enabled** in the database
3. **Verify the actual permissions on disk** match the expected state

```bash
# CORRECT - Test as nobody user with explicit shell
docker exec -it myeduconnect-web bash
su -s /bin/bash nobody
cat /var/www/html/storage/backups/backup.sql
echo "test" >> /var/www/html/storage/backups/backup.sql
```

### Verification Results

The comprehensive audit confirmed:

1. **chmod() function works correctly**: When called from PHP CLI or web context, it successfully changes file permissions
2. **Permissions persist on disk**: Changes to 0666 and 0640 are correctly applied and persist
3. **Vulnerability works as designed**:
   - When files are 0666, the `nobody` user can read and write them
   - When files are 0640, the `nobody` user gets "Permission denied"
4. **No Docker/Windows filesystem issues**: The bind mount from Windows to Linux container properly supports Unix permissions
5. **No ownership issues**: Files are owned by `www-data` (UID 33) and the web server runs as this user
6. **No parent directory permission issues**: All directories in the path have 0777 permissions, allowing traversal

### The Actual Vulnerability Behavior

When the vulnerability is enabled (toggle ON):
- Database flag: `enabled = 1`
- File permissions: `0666` (rw-rw-rw-)
- `nobody` user can: **READ** and **WRITE** files
- Risk: **HIGH** - Any user on the system can access sensitive data

When the vulnerability is disabled (toggle OFF):
- Database flag: `enabled = 0`
- File permissions: `0640` (rw-r-----)
- `nobody` user can: **NEITHER READ NOR WRITE** files (Permission denied)
- Risk: **LOW** - Only owner and group members can access files

### Summary

The "Permission denied" error occurs because:
1. Users were testing as `root` (which bypasses permissions) OR
2. Users were testing when the vulnerability was disabled (files at 0640) OR
3. Users were using incorrect `su` syntax for the `nobody` user

The vulnerability implementation is **correct and functional**. The issue was entirely in the testing methodology documented in the README.

## 1. Overview

### What is the Weak File Permissions Vulnerability?

The Weak File Permissions vulnerability is a security weakness that occurs when sensitive files on a Linux/Unix system are configured with overly permissive access rights. In this educational platform, the vulnerability is intentionally implemented to demonstrate how improper file permission settings can expose sensitive data to unauthorized access, modification, or deletion.

### Why Insecure File Permissions Are Dangerous

In Linux/Unix operating systems, file permissions control which users and groups can read, write, or execute files. These permissions are represented by a three-digit octal number (e.g., 644, 755, 777) where each digit represents permissions for the owner, group, and others respectively.

When files are configured with weak permissions (such as 666 or 777), they become accessible to all users on the system, including:
- Low-privileged user accounts
- Web server processes
- Automated scripts
- Malicious actors who have gained any level of system access

This violates the principle of least privilege, which states that every user and process should only have the minimum permissions necessary to perform its intended function.

### Real-World Risks

In production environments, weak file permissions can lead to:

1. **Unauthorized Data Access**: Attackers can read sensitive files containing passwords, API keys, database credentials, personal information, or proprietary business data.

2. **Data Integrity Breaches**: Unauthorized users can modify configuration files, inject malicious content into data files, or corrupt critical system files.

3. **Privilege Escalation**: Attackers can modify scripts, binaries, or configuration files to execute code with higher privileges.

4. **Data Loss**: Unauthorized deletion of critical backup files, logs, or system data.

5. **Compliance Violations**: Weak permissions violate security standards such as PCI DSS, HIPAA, GDPR, and SOC 2, which require strict access controls.

6. **Supply Chain Attacks**: Compromised build artifacts or configuration files can propagate malicious changes throughout an organization.

### Educational Purpose

This vulnerability was intentionally included in the MyEduConnect cybersecurity educational platform to teach students and security professionals about:

- The importance of proper file permission management in Linux/Unix systems
- How to identify weak file permissions using security assessment tools
- The security implications of overly permissive access controls
- How to exploit weak permissions for unauthorized access
- How to remediate weak file permissions using proper security practices
- The difference between secure (640, 644) and vulnerable (666, 777) permission modes
- How to implement and test security toggles in educational lab environments

This vulnerability is part of an academic cybersecurity training environment where vulnerabilities can be toggled on and off for demonstration, testing, and assessment purposes. It provides a safe, controlled environment for learning about file permission security without risking real production systems.

## 2. Vulnerability Location

### File Paths Monitored by the Feature

The Weak File Permissions vulnerability monitors and controls the following sensitive files:

1. **storage/backups/backup.sql**
   - Full path: `/var/www/html/storage/backups/backup.sql`
   - Purpose: Database backup file containing sensitive user credentials
   - Contains: Email addresses, password hashes, user account information
   - Security classification: Confidential

2. **storage/student_records.csv**
   - Full path: `/var/www/html/storage/student_records.csv`
   - Purpose: Student personal information file
   - Contains: Student IDs, email addresses, names, grade levels, parent contact information
   - Security classification: Personally Identifiable Information (PII)

### Directory Locations

- **Application Root**: `/var/www/html/`
- **Storage Directory**: `/var/www/html/storage/`
- **Backup Directory**: `/var/www/html/storage/backups/`
- **Log File**: `/var/www/html/storage/file_permissions.log`

### Admin Page Controlling the Vulnerability

- **Page**: `admin/security-settings.php`
- **URL**: `http://localhost:8080/admin/security-settings.php`
- **Access Control**: Requires administrator role authentication
- **Toggle Label**: "Weak File Permissions"
- **Control Type**: Toggle switch (checkbox with form-switch styling)

### Additional Related Pages

- **Testing Page**: `admin/test-file-permissions.php`
  - URL: `http://localhost:8080/admin/test-file-permissions.php`
  - Purpose: Comprehensive testing and diagnostic interface
  - Features: Permission status display, diagnostic table, Docker commands, log viewer

### Backend Implementation Files

- **Core Logic**: `app/security/functions.php`
  - Function: `syncFilePermissions($enabled)` - Changes file permissions based on security mode
  - Function: `getFilePermissionsStatus()` - Returns current permission status for all monitored files
  - Function: `applyVulnerabilitySideEffects($name, $enabled)` - Triggers permission changes when toggle is activated

## 3. Security Toggle Behaviour

### Security OFF (Vulnerable Mode)

When the "Weak File Permissions" toggle is set to OFF (vulnerability enabled):

#### Expected Permission Values
- **Octal Notation**: `0666` (rw-rw-rw-)
- **Symbolic Notation**: `-rw-rw-rw-`
- **Binary Representation**: `110110110`

#### Permission Breakdown
- **Owner (first digit - 6)**: Read (4) + Write (2) = 6
  - Can read the file
  - Can write to the file
  - Cannot execute the file
- **Group (second digit - 6)**: Read (4) + Write (2) = 6
  - Can read the file
  - Can write to the file
  - Cannot execute the file
- **Others (third digit - 6)**: Read (4) + Write (2) = 6
  - Can read the file
  - Can write to the file
  - Cannot execute the file

#### Why the Files Become Insecure
With 0666 permissions, any user account on the system (including low-privileged accounts, web server processes, and automated scripts) can:
- Read the complete contents of sensitive files
- Modify or overwrite file contents
- Delete the files entirely
- Inject malicious data into the files
- Corrupt the file structure

This violates the principle of least privilege because files containing sensitive data are accessible to all users, not just those with a legitimate need to access them.

#### What an Attacker Could Do
In vulnerable mode, an attacker who gains any level of system access (even as a low-privileged user) can:

1. **Read Sensitive Credentials**: Access `backup.sql` to extract email addresses and password hashes for offline cracking
2. **Expose Personal Information**: Read `student_records.csv` to obtain student PII including names, emails, and parent contact information
3. **Inject Malicious Content**: Append malicious SQL statements to `backup.sql` that could be executed during database restoration
4. **Corrupt Data**: Modify `student_records.csv` to inject fake student records or alter existing ones
5. **Delete Critical Files**: Remove backup files, preventing system recovery
6. **Privilege Escalation**: Modify configuration files to execute code with higher privileges

### Security ON (Secure Mode)

When the "Weak File Permissions" toggle is set to ON (vulnerability disabled):

#### Expected Permission Values
- **Octal Notation**: `0640` (rw-r-----)
- **Symbolic Notation**: `-rw-r-----`
- **Binary Representation**: `110100000`

#### Permission Breakdown
- **Owner (first digit - 6)**: Read (4) + Write (2) = 6
  - Can read the file
  - Can write to the file
  - Cannot execute the file
- **Group (second digit - 4)**: Read (4) = 4
  - Can read the file
  - Cannot write to the file
  - Cannot execute the file
- **Others (third digit - 0)**: No permissions
  - Cannot read the file
  - Cannot write to the file
  - Cannot execute the file

#### Why the Files Become Protected
With 0640 permissions, access is strictly limited:
- Only the file owner (typically `www-data` or `root`) can read and write
- Group members can only read the file (no write access)
- All other users have absolutely no access (no read, no write, no execute)

This enforces the principle of least privilege by ensuring that only authorized users with a legitimate need can access the sensitive files.

#### What Attacks Should No Longer Work
In secure mode, the following attacks are prevented:

1. **Unauthorized Read Access**: Low-privileged users cannot read file contents
2. **Unauthorized Write Access**: Low-privileged users cannot modify file contents
3. **File Deletion**: Low-privileged users cannot delete the files
4. **Data Injection**: Attackers cannot append malicious content to files
5. **Credential Exposure**: Password hashes and sensitive data remain protected
6. **PII Disclosure**: Student personal information remains confidential

Any attempt by a non-owner user to access the files will result in a "Permission denied" error.

## 4. Environment Requirements

### Operating System Requirements

- **Host OS**: Windows, macOS, or Linux (Docker Desktop required)
- **Container OS**: Debian-based Linux (provided by PHP:8.2-apache Docker image)
- **File System**: Supports Unix-style permissions (ext4, xfs, or compatible)
- **User Management**: Standard Linux user/group system

### Docker Requirements

- **Docker Engine**: Version 20.10 or higher
- **Docker Compose**: Version 2.0 or higher
- **Docker Desktop**: Required for Windows/macOS hosts
- **Container Access**: Ability to execute commands inside containers
- **Volume Mounts**: Host directory must be mounted into container

### PHP Requirements

- **PHP Version**: 8.2 or higher
- **Required Extensions**: 
  - `pdo_mysql` (database connectivity)
  - `mbstring` (string manipulation)
  - `fileinfo` (MIME type detection)
- **PHP Functions Used**:
  - `chmod()` - Change file permissions
  - `fileperms()` - Get file permissions
  - `file_exists()` - Check file existence
  - `is_readable()` - Check read access
  - `is_writable()` - Check write access
  - `clearstatcache()` - Clear file status cache

### Container Requirements

- **Web Container**: `myeduconnect-web` (PHP/Apache)
- **Database Container**: `myeduconnect-mysql` (MySQL 8.0)
- **Container User**: Files owned by `www-data` (UID 33)
- **Working Directory**: `/var/www/html/`
- **Required Ports**: 8080 (HTTP), 8443 (HTTPS), 2222 (SSH)

### Commands Needed Before Testing

#### Start Docker Containers
```bash
# Purpose: Start all application containers
# Expected Result: All containers running and accessible
# Security Relevance: Required for vulnerability testing environment

docker-compose up -d
```

#### Verify Container Status
```bash
# Purpose: Confirm all containers are running
# Expected Result: List showing all containers with "Up" status
# Security Relevance: Ensures testing environment is operational

docker ps
```

#### Verify Web Server Access
```bash
# Purpose: Confirm web server is responding
# Expected Result: HTTP 200 OK response
# Security Relevance: Ensures admin interface is accessible for vulnerability toggling

curl -I http://localhost:8080
```

#### Enter Container for Command-Line Testing
```bash
# Purpose: Access container shell for direct file permission testing
# Expected Result: Bash shell prompt inside container
# Security Relevance: Enables direct verification of permission changes

docker exec -it myeduconnect-web bash
```

#### Verify File Existence
```bash
# Purpose: Confirm monitored files exist before testing
# Expected Result: Files listed with current permissions
# Security Relevance: Ensures vulnerability has files to operate on

docker exec myeduconnect-web ls -la /var/www/html/storage/backups/
docker exec myeduconnect-web ls -la /var/www/html/storage/
```

## 5. Instructor Testing Guide

This step-by-step guide is written for instructors, examiners, or security auditors who need to test the Weak File Permissions vulnerability for grading or assessment purposes.

### SECTION 1: TESTING VULNERABLE MODE

This section tests the vulnerability when it is ENABLED (Security OFF). Files should have world-readable/writable permissions (0666).

#### Step 1: Login to the Application

**Action**: Navigate to the application login page and authenticate as an administrator.

**URL**: `http://localhost:8080/login.php`

**Credentials**:
- Username: `admin@myeduconnect.com`
- Password: `admin123` (or as specified in project documentation)

**Expected Result**: Successful login and redirect to administrator dashboard.

**Security Relevance**: Administrator access is required to toggle security vulnerabilities.

#### Step 2: Enable Weak File Permissions

**Action**: Enable the Weak File Permissions vulnerability.

**Procedure**:
1. Navigate to: `http://localhost:8080/admin/security-settings.php`
2. Click the "Weak File Permissions" toggle switch to ON (checked state)
3. Click "Save Security Settings" button
4. Wait for page to reload

**Expected Result**: 
- Success message: "Security Vulnerability Manager updated."
- Toggle remains in ON position
- File Permissions Status card updates to show VULNERABLE badges

**Security Relevance**: This action intentionally weakens file permissions for educational demonstration.

#### Step 3: Verify Permissions Are Insecure

**Action**: Review the File Permissions Status card on the Security Settings page.

**Expected Vulnerable State**:
- backup.sql: Permissions `0666`, Badge `VULNERABLE`
- student_records.csv: Permissions `0666`, Badge `VULNERABLE`
- Readable: YES (green checkmark)
- Writable: YES (green checkmark)
- Expected: 0666
- Actual: 0666
- Matches Expected: YES

**Security Relevance**: Confirms that files are now in vulnerable state with world-readable/writable permissions.

#### Step 4: Open the Test Page

**Action**: Access the dedicated testing page for detailed verification.

**Navigation**: Click "Test File Permissions" button in File Permissions Status card, or navigate directly to: `http://localhost:8080/admin/test-file-permissions.php`

**Expected Result**: Redirect to test page with comprehensive diagnostic information showing actual filesystem values (owner, group, permissions, symbolic notation).

**Security Relevance**: Provides detailed diagnostic data for evidence collection.

#### Step 5: Enter the Docker Container

**Action**: Access the container shell for direct file permission testing.

**Command**:
```bash
docker exec -it myeduconnect-web bash
```

**Expected Result**: Bash shell prompt inside container.

**Security Relevance**: Enables direct verification of permission changes on the filesystem.

#### Step 6: Verify File Permissions in Container

**Action**: Check the actual permissions on the monitored files.

**Commands**:
```bash
stat -c "%a %n" /var/www/html/storage/backups/backup.sql
stat -c "%a %n" /var/www/html/storage/student_records.csv
ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv
```

**Expected Result**: 
- Both files show `0666` permissions
- Symbolic notation shows `-rw-rw-rw-`
- Owner is `www-data`

**Security Relevance**: Confirms that files have world-readable/writable permissions as expected in vulnerable mode.

#### Step 7: Attempt to Modify backup.sql as Non-Owner User

**Action**: Switch to a non-owner user and attempt to modify the file.

**CRITICAL**: You MUST test as a non-owner user. Docker exec runs as root by default, which bypasses all permission checks.

**Commands**:
```bash
su -s /bin/bash nobody
echo "VULNERABLE_MODE_TEST" >> /var/www/html/storage/backups/backup.sql
```

**Expected Result**: Command executes successfully with no error message.

**Security Relevance**: Demonstrates that unauthorized users can modify sensitive files when permissions are weak.

#### Step 8: Verify Modification Succeeded

**Action**: Confirm that the file was actually modified.

**Command**:
```bash
cat /var/www/html/storage/backups/backup.sql
```

**Expected Result**: File contents display, including the appended "VULNERABLE_MODE_TEST" text.

**Security Relevance**: Provides concrete evidence that the vulnerability is exploitable - unauthorized write access succeeded.

#### Step 9: Capture Evidence

**Action**: Capture screenshots and command outputs for grading.

**Evidence Required**:
- Security Settings page showing toggle ON
- File Permissions Status card showing VULNERABLE badges
- Test page diagnostic table showing 0666 permissions
- Docker terminal showing stat commands with 0666 output
- Docker terminal showing successful write operation
- Docker terminal showing modified file contents

**Security Relevance**: Provides documented proof that vulnerable mode allows unauthorized modification.

---

### SECTION 2: TESTING SECURE MODE

This section tests the vulnerability when it is DISABLED (Security ON). Files should have restrictive permissions (0640).

#### Step 1: Disable Weak File Permissions

**Action**: Disable the Weak File Permissions vulnerability to test remediation.

**Procedure**:
1. Navigate to: `http://localhost:8080/admin/security-settings.php`
2. Click the "Weak File Permissions" toggle to OFF (unchecked state)
3. Click "Save Security Settings" button
4. Wait for page to reload

**Expected Result**:
- Success message: "Security Vulnerability Manager updated."
- Toggle remains in OFF position
- File Permissions Status card updates to show SECURE badges

**Security Relevance**: This action restores secure file permissions.

#### Step 2: Verify Secure Permissions Are Applied

**Action**: Review the updated File Permissions Status card.

**Expected Secure State**:
- backup.sql: Permissions `0640`, Badge `SECURE`
- student_records.csv: Permissions `0640`, Badge `SECURE`
- Readable: YES (green checkmark for owner)
- Writable: YES (green checkmark for owner)
- Expected: 0640
- Actual: 0640
- Matches Expected: YES

**Security Relevance**: Confirms that files are now in secure state with restricted permissions.

#### Step 3: Open the Test Page

**Action**: Access the dedicated testing page for detailed verification.

**Navigation**: Click "Test File Permissions" button in File Permissions Status card, or navigate directly to: `http://localhost:8080/admin/test-file-permissions.php`

**Expected Result**: Redirect to test page with comprehensive diagnostic information showing actual filesystem values (owner, group, permissions, symbolic notation).

**Security Relevance**: Provides detailed diagnostic data for evidence collection.

#### Step 4: Enter the Docker Container

**Action**: Access the container shell for direct file permission testing.

**Command**:
```bash
docker exec -it myeduconnect-web bash
```

**Expected Result**: Bash shell prompt inside container.

**Security Relevance**: Enables direct verification of permission changes on the filesystem.

#### Step 5: Verify File Permissions in Container

**Action**: Check the actual permissions on the monitored files.

**Commands**:
```bash
stat -c "%a %n" /var/www/html/storage/backups/backup.sql
stat -c "%a %n" /var/www/html/storage/student_records.csv
ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv
```

**Expected Result**: 
- Both files show `0640` permissions
- Symbolic notation shows `-rw-r-----`
- Owner is `www-data`

**Security Relevance**: Confirms that files have restrictive permissions as expected in secure mode.

#### Step 6: Attempt to Modify backup.sql as Non-Owner User

**Action**: Switch to a non-owner user and attempt to modify the file.

**CRITICAL**: You MUST test as a non-owner user. Docker exec runs as root by default, which bypasses all permission checks.

**Commands**:
```bash
su -s /bin/bash nobody
echo "SECURE_MODE_TEST" >> /var/www/html/storage/backups/backup.sql
```

**Expected Result**: Command fails with "Permission denied" error message.

**Security Relevance**: Demonstrates that secure permissions successfully prevent unauthorized modification.

#### Step 7: Attempt to Read backup.sql as Non-Owner User

**Action**: Attempt to read the file as a non-owner user.

**Command**:
```bash
cat /var/www/html/storage/backups/backup.sql
```

**Expected Result**: Command fails with "Permission denied" error message.

**Security Relevance**: Demonstrates that secure permissions also prevent unauthorized read access.

#### Step 8: Verify File Was Not Modified

**Action**: Exit the nobody user shell and verify the file contents as root.

**Commands**:
```bash
exit
cat /var/www/html/storage/backups/backup.sql
```

**Expected Result**: File contents display WITHOUT the "SECURE_MODE_TEST" text (proving the write attempt failed).

**Security Relevance**: Provides concrete evidence that the security measure is effective - unauthorized write access was blocked.

#### Step 9: Capture Evidence

**Action**: Capture screenshots and command outputs for grading.

**Evidence Required**:
- Security Settings page showing toggle OFF
- File Permissions Status card showing SECURE badges
- Test page diagnostic table showing 0640 permissions
- Docker terminal showing stat commands with 0640 output
- Docker terminal showing "Permission denied" error on write attempt
- Docker terminal showing "Permission denied" error on read attempt
- Docker terminal showing file contents without unauthorized modifications

**Security Relevance**: Provides documented proof that secure mode blocks unauthorized modification.

## 6. Verification Using Linux Commands

This section provides exact Linux commands for verifying the Weak File Permissions vulnerability, including purpose, expected results, and security relevance for each command.

### CRITICAL TESTING REQUIREMENT

**IMPORTANT**: All file access tests MUST be performed as a non-owner, non-root user to accurately demonstrate the vulnerability. The `nobody` user (UID 65534) is recommended for testing.

**DO NOT test as root**: Docker exec commands run as root by default. Root can bypass all permission checks and will always succeed, even in secure mode. This will give false positive results.

**Correct testing approach**:
```bash
docker exec -it myeduconnect-web bash
su -s /bin/bash nobody
# Now run your test commands as nobody
```

### ls -l (List Files with Permissions)

#### Command
```bash
ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv
```

**Purpose**: Display detailed file information including permissions, owner, group, size, and modification time.

**Expected Vulnerable Output**:
```
-rw-rw-rw- 1 www-data www-data 466 Jun 19 12:33 backup.sql
-rw-rw-rw- 1 www-data www-data 282 Jun 19 12:33 student_records.csv
```

**Expected Secure Output**:
```
-rw-r----- 1 www-data www-data 466 Jun 19 12:35 backup.sql
-rw-r----- 1 www-data www-data 282 Jun 19 12:35 student_records.csv
```

**What the Examiner Should See**:
- First character: `-` (regular file)
- Next 9 characters: Permission bits (rwxrwxrwx format)
- Owner: www-data
- Group: www-data
- File size in bytes
- Modification date and time
- File name

**Security Relevance**: This is the primary command for visually verifying file permission states. The permission bits directly indicate whether files are secure (640 = rw-r-----) or vulnerable (666 = rw-rw-rw-).

### stat (Detailed File Information)

#### Command
```bash
stat /var/www/html/storage/backups/backup.sql
stat /var/www/html/storage/student_records.csv
```

**Purpose**: Display comprehensive file metadata including inode, permissions, access/modify/change times, and file size.

**Expected Vulnerable Output**:
```
  File: backup.sql
  Size: 466        Blocks: 8          IO Block: 4096   regular file
Access: (0666/-rw-rw-rw-)  Uid: (   33/ www-data)   Gid: (   33/ www-data)
Access: 2026-06-19 12:33:45.000000000 +0000
Modify: 2026-06-19 12:33:45.000000000 +0000
Change: 2026-06-19 12:33:45.000000000 +0000
 Birth: -
```

**Expected Secure Output**:
```
  File: backup.sql
  Size: 466        Blocks: 8          IO Block: 4096   regular file
Access: (0640/-rw-r-----)  Uid: (   33/ www-data)   Gid: (   33/ www-data)
Access: 2026-06-19 12:35:22.000000000 +0000
Modify: 2026-06-19 12:35:22.000000000 +0000
Change: 2026-06-19 12:35:22.000000000 +0000
 Birth: -
```

**What the Examiner Should See**:
- Access field shows octal permissions in parentheses
- Symbolic permissions shown after octal value
- Uid and Gid show numeric and symbolic user/group identifiers
- Three timestamps: Access (last read), Modify (last content change), Change (last metadata change)

**Security Relevance**: The `stat` command provides the most detailed permission information. The octal value (0666 or 0640) is the definitive indicator of the security state.

### stat -c "%a %n" (Octal Permissions Only)

#### Command
```bash
stat -c "%a %n" /var/www/html/storage/backups/backup.sql
stat -c "%a %n" /var/www/html/storage/student_records.csv
```

**Purpose**: Display only the octal permission value and file name for quick verification.

**Expected Vulnerable Output**:
```
0666 /var/www/html/storage/backups/backup.sql
0666 /var/www/html/storage/student_records.csv
```

**Expected Secure Output**:
```
0640 /var/www/html/storage/backups/backup.sql
0640 /var/www/html/storage/student_records.csv
```

**What the Examiner Should See**: Clean output showing only the permission octal value and full file path.

**Security Relevance**: This command provides the most concise verification of permission state, ideal for automated testing and quick manual checks.

### cat (Read File Contents)

#### Command
```bash
# MUST be run as nobody user (not root)
su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
su -s /bin/bash nobody -c "cat /var/www/html/storage/student_records.csv"
```

**Purpose**: Display the complete contents of a file to verify readability.

**Expected Vulnerable Output** (backup.sql):
```
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

**Expected Secure Output** (as non-owner):
```
cat: /var/www/html/storage/backups/backup.sql: Permission denied
```

**What the Examiner Should See**: In vulnerable mode, the complete file contents display. In secure mode (as non-owner), a permission denied error appears.

**Security Relevance**: This command demonstrates whether unauthorized users can read sensitive file contents, which is the primary risk of weak file permissions.

**CRITICAL**: If you run this command as root (default Docker exec user), it will ALWAYS succeed even in secure mode, giving false positive results. You MUST switch to the `nobody` user first.

### echo (Write to File)

#### Command
```bash
# MUST be run as nobody user (not root)
su -s /bin/bash nobody -c "echo 'ATTACKER DATA' >> /var/www/html/storage/backups/backup.sql"
su -s /bin/bash nobody -c "echo 'MALICIOUS RECORD' >> /var/www/html/storage/student_records.csv"
```

**Purpose**: Append data to a file to verify write access.

**Expected Vulnerable Output**: Command executes successfully with no error message. File size increases.

**Expected Secure Output** (as non-owner):
```
bash: /var/www/html/storage/backups/backup.sql: Permission denied
```

**What the Examiner Should See**: In vulnerable mode, the command completes silently. In secure mode (as non-owner), a permission denied error appears.

**Security Relevance**: This command demonstrates whether unauthorized users can modify file contents, which is a critical security risk of weak file permissions.

**CRITICAL**: If you run this command as root (default Docker exec user), it will ALWAYS succeed even in secure mode, giving false positive results. You MUST switch to the `nobody` user first.

### chmod (Change Permissions)

#### Command
```bash
chmod 640 /var/www/html/storage/backups/backup.sql
chmod 640 /var/www/html/storage/student_records.csv
```

**Purpose**: Manually change file permissions to secure mode (for testing or remediation).

**Expected Output**: No output on success. Error message if permission denied.

**What the Examiner Should See**: Command executes without output if successful. Error if insufficient privileges.

**Security Relevance**: This command is used for manual remediation of weak permissions and for testing permission changes outside the application interface.

### pwd (Print Working Directory)

#### Command
```bash
pwd
```

**Purpose**: Display the current working directory to confirm location.

**Expected Output**:
```
/var/www/html
```

**What the Examiner Should See**: Full path of current directory.

**Security Relevance**: Ensures the examiner is in the correct directory before executing file operations, preventing accidental operations on wrong files.

### find (Search for Files by Permissions)

#### Command
```bash
find /var/www/html/storage -type f -perm 0666
find /var/www/html/storage -type f -perm 0640
```

**Purpose**: Search for files with specific permission values.

**Expected Vulnerable Output**:
```
/var/www/html/storage/backups/backup.sql
/var/www/html/storage/student_records.csv
```

**Expected Secure Output**:
```
/var/www/html/storage/backups/backup.sql
/var/www/html/storage/student_records.csv
```

**What the Examiner Should See**: List of files matching the specified permission value.

**Security Relevance**: This command is useful for security audits to identify all files with weak permissions across the entire storage directory.

### su (Switch User)

#### Command
```bash
# CORRECT: Use -s flag to specify shell for nobody user
su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
su -s /bin/bash nobody -c "echo test >> /var/www/html/storage/backups/backup.sql"

# OR: Switch to nobody user interactively first
su -s /bin/bash nobody
# Then run commands directly
cat /var/www/html/storage/backups/backup.sql
echo test >> /var/www/html/storage/backups/backup.sql
```

**Purpose**: Execute a command as a different user (nobody) to test access restrictions.

**Expected Vulnerable Output**: Command executes successfully (file is readable/writable by all users).

**Expected Secure Output**:
```
cat: /var/www/html/storage/backups/backup.sql: Permission denied
```

**What the Examiner Should See**: In vulnerable mode, the command succeeds. In secure mode, permission denied error appears.

**Security Relevance**: This command demonstrates the effectiveness of secure permissions by testing access from a non-owner user account.

**IMPORTANT**: The `nobody` user does not have a default shell, so you must use `su -s /bin/bash` to specify the shell. Using `su - nobody` without the -s flag will fail with "This account is currently not available."

## 7. Docker Verification

This section provides exact Docker commands for testing the Weak File Permissions vulnerability inside the container environment.

### docker ps (List Running Containers)

#### Command
```bash
docker ps
```

**Purpose**: Display all currently running Docker containers.

**Expected Output**:
```
CONTAINER ID   IMAGE                         COMMAND                  CREATED          STATUS                       PORTS                                                                                                                         NAMES
a4750d226290   ethical-hacking--master-web   "docker-php-entrypoi…"   32 minutes ago   Up 32 minutes                0.0.0.0:2222->22/tcp, 0.0.0.0:8080->80/tcp, 0.0.0.0:8443->443/tcp                          myeduconnect-web
b072e6af1d2c   phpmyadmin/phpmyadmin         "/docker-entrypoint.…"   32 minutes ago   Up 32 minutes                0.0.0.0:8081->80/tcp                                                                                       myeduconnect-phpmyadmin
2dc22c22ff1e   mysql:8.0                     "docker-entrypoint.s…"   32 minutes ago   Up 32 minutes                0.0.0.0:3307->3306/tcp                                                                                   myeduconnect-mysql
```

**Expected Results**: 
- myeduconnect-web container shows "Up" status
- Ports 8080, 8443, 2222 are mapped
- Container has been running for some time

**Security Relevance**: Confirms the web container is running and accessible for testing.

### docker exec -it CONTAINER_NAME bash (Access Container Shell)

#### Command
```bash
docker exec -it myeduconnect-web bash
```

**Purpose**: Open an interactive bash shell inside the web container.

**Expected Output**: Bash shell prompt inside container:
```
root@<container_id>:/var/www/html#
```

**Expected Results**: 
- Shell prompt appears
- Current directory is /var/www/html
- Can execute Linux commands

**Security Relevance**: Provides direct access to the container filesystem for permission verification and testing.

### ls -la storage/ (List Storage Directory)

#### Command
```bash
docker exec myeduconnect-web ls -la /var/www/html/storage/
```

**Purpose**: List all files and directories in the storage directory with detailed information.

**Expected Output**:
```
total 20
drwxrwxrwx 1 root     root     4096 Jun 19 11:56 .
drwxrwxrwx 1 root     root     4096 Jun 19 10:59 ..
drwxrwxrwx 1 root     root     4096 Jun 17 10:41 backups
-rw-r--r-- 1 www-data www-data 8292 Jun 19 12:33 file_permissions.log
drwxr-xr-x 1 root     root     4096 Jun 18 08:20 ssh
-rw-r--r-- 1 root     root      720 Jun 18 08:20 ssh_toggle.log
-rw-r----- 1 www-data www-data  282 Jun 19 12:33 student_records.csv
-rw-r--r-- 1 root     root      634 Jun 18 08:20 sudo_toggle.log
-rw-rw-r-- 1 www-data www-data    0 Jun 18 07:55 test.txt
```

**Expected Results**: 
- All files and directories listed with permissions
- Monitored files (backup.sql, student_records.csv) visible
- Log file shows recent modification

**Security Relevance**: Provides overview of storage directory structure and current permission states.

### stat storage/backup.sql (Detailed Backup File Information)

#### Command
```bash
docker exec myeduconnect-web stat /var/www/html/storage/backups/backup.sql
```

**Purpose**: Display comprehensive metadata for the backup.sql file.

**Expected Vulnerable Output**:
```
  File: /var/www/html/storage/backups/backup.sql
  Size: 466        Blocks: 8          IO Block: 4096   regular file
Access: (0666/-rw-rw-rw-)  Uid: (   33/ www-data)   Gid: (   33/ www-data)
Access: 2026-06-19 12:33:45.000000000 +0000
Modify: 2026-06-19 12:33:45.000000000 +0000
Change: 2026-06-19 12:33:45.000000000 +0000
 Birth: -
```

**Expected Secure Output**:
```
  File: /var/www/html/storage/backups/backup.sql
  Size: 466        Blocks: 8          IO Block: 4096   regular file
Access: (0640/-rw-r-----)  Uid: (   33/ www-data)   Gid: (   33/ www-data)
Access: 2026-06-19 12:35:22.000000000 +0000
Modify: 2026-06-19 12:35:22.000000000 +0000
Change: 2026-06-19 12:35:22.000000000 +0000
 Birth: -
```

**Expected Results**: 
- File size displayed
- Octal permissions shown in Access field
- Owner and group identified
- Timestamps shown

**Security Relevance**: Provides definitive evidence of current permission state for grading.

### cat storage/file_permissions.log (View Permission Change Log)

#### Command
```bash
docker exec myeduconnect-web cat /var/www/html/storage/file_permissions.log
```

**Purpose**: Display the complete log of all permission changes.

**Expected Output**:
```
2026-06-19 12:30:48 - File: backup.sql - Mode: SECURE - Target: 640 (owner rw, group r, others none) - Actual: 0640 - Success: YES
2026-06-19 12:30:48 - File: student_records.csv - Mode: SECURE - Target: 640 (owner rw, group r, others none) - Actual: 0640 - Success: YES
2026-06-19 12:33:43 - File: backup.sql - Mode: VULNERABLE - Target: 666 (world-readable/writable) - Actual: 0666 - Success: YES
2026-06-19 12:33:43 - File: student_records.csv - Mode: VULNERABLE - Target: 666 (world-readable/writable) - Actual: 0666 - Success: YES
2026-06-19 12:33:44 - File: backup.sql - Mode: SECURE - Target: 640 (owner rw, group r, others none) - Actual: 0640 - Success: YES
2026-06-19 12:33:44 - File: student_records.csv - Mode: SECURE - Target: 640 (owner rw, group r, others none) - Actual: 0640 - Success: YES
```

**Expected Results**: 
- Chronological log entries
- Each entry shows: timestamp, file name, mode, target permissions, actual permissions, success status
- Recent changes visible at bottom

**Security Relevance**: Provides audit trail of permission changes for evidence collection and troubleshooting.

## 8. Vulnerability Demonstration

This section provides a complete attack demonstration showing how the Weak File Permissions vulnerability can be exploited when Security is OFF (Vulnerable Mode).

### Pre-Demonstration Setup

**Prerequisites**:
- Docker containers running
- Weak File Permissions vulnerability enabled (toggle OFF)
- Access to container shell

**Initial State**:
- backup.sql permissions: 0666 (rw-rw-rw-)
- student_records.csv permissions: 0666 (rw-rw-rw-)
- Both files world-readable and world-writable

### Step 1: Security OFF - File Permissions Become 0777/0666

**Action**: Enable the Weak File Permissions vulnerability via the admin interface.

**Procedure**:
1. Navigate to `http://localhost:8080/admin/security-settings.php`
2. Toggle "Weak File Permissions" to ON (checked)
3. Click "Save Security Settings"

**Expected Result**: 
- Database updated: `security_settings.enabled = 1`
- PHP function `syncFilePermissions(true)` executed
- Files set to 0666 permissions
- Log entry created

**Security Relevance**: This action intentionally weakens file permissions to demonstrate the vulnerability.

### Step 2: Verify Vulnerable Permissions

**Action**: Confirm files have vulnerable permissions using Linux commands.

**Commands**:
```bash
docker exec -it myeduconnect-web bash
ls -l /var/www/html/storage/backups/backup.sql
stat -c "%a %n" /var/www/html/storage/backups/backup.sql
```

**Expected Output**:
```
-rw-rw-rw- 1 www-data www-data 466 Jun 19 12:33 backup.sql
0666 /var/www/html/storage/backups/backup.sql
```

**Security Relevance**: Confirms that files are now in vulnerable state with world-readable/writable permissions.

### Step 3: Attacker Reads Sensitive Backup File

**Action**: Demonstrate that any user can read the backup file containing credentials.

**Scenario**: Attacker gains low-privileged access (e.g., via web shell or compromised account).

**Commands**:
```bash
# As any user (including low-privileged)
cat /var/www/html/storage/backups/backup.sql
```

**Expected Output**:
```
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

**Security Relevance**: Demonstrates that weak permissions allow unauthorized read access to sensitive credentials, which is the primary security risk.

### Step 4: Attacker Reads Student Personal Information

**Action**: Demonstrate that any user can read the student records file containing PII.

**Commands**:
```bash
cat /var/www/html/storage/student_records.csv
```

**Expected Output**:
```
student_id,email,first_name,last_name,grade_level,parent_email
STU001,student1@myeduconnect.com,Alice,Williams,Grade 12,parent1@email.com
STU002,student2@myeduconnect.com,Bob,Brown,Grade 12,parent2@email.com
STU003,student3@myeduconnect.com,Charlie,Davis,Grade 11,parent3@email.com
```

**Security Relevance**: Demonstrates that weak permissions expose personally identifiable information (PII), violating privacy regulations.

### Step 5: Attacker Modifies Backup File

**Action**: Demonstrate that any user can modify the backup file.

**Commands**:
```bash
echo "-- MALICIOUS INJECTION" >> /var/www/html/storage/backups/backup.sql
echo "DROP TABLE users; --" >> /var/www/html/storage/backups/backup.sql
```

**Expected Result**: Command executes successfully with no error. File size increases.

**Verification**:
```bash
tail -5 /var/www/html/storage/backups/backup.sql
```

**Expected Output**:
```
('admin@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- MALICIOUS INJECTION
DROP TABLE users; --
```

**Security Relevance**: Demonstrates that weak permissions allow unauthorized write access, enabling data integrity attacks and potential SQL injection during backup restoration.

### Step 6: Attacker Injects Fake Student Records

**Action**: Demonstrate that any user can inject fake data into the student records file.

**Commands**:
```bash
echo "FAKE001,hacker@evil.com,Hacker,Evil,Grade 12,evilparent@email.com" >> /var/www/html/storage/student_records.csv
```

**Expected Result**: Command executes successfully.

**Verification**:
```bash
tail -2 /var/www/html/storage/student_records.csv
```

**Expected Output**:
```
STU003,student3@myeduconnect.com,Charlie,Davis,Grade 11,parent3@email.com
FAKE001,hacker@evil.com,Hacker,Evil,Grade 12,evilparent@email.com
```

**Security Relevance**: Demonstrates that weak permissions allow data integrity attacks, enabling fraud and system disruption through fake record injection.

### Step 7: Verification Confirms File Modification

**Action**: Confirm that modifications persisted and files remain in vulnerable state.

**Commands**:
```bash
ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv
```

**Expected Output**:
```
-rw-rw-rw- 1 www-data www-data 512 Jun 19 12:35 backup.sql
-rw-rw-rw- 1 www-data www-data 354 Jun 19 12:35 student_records.csv
```

**Security Relevance**: Confirms that unauthorized modifications persisted, demonstrating the ongoing security risk of weak permissions.

### Summary of Vulnerability Demonstration

**Security Impact Achieved**:
- ✅ Confidentiality breach: Sensitive credentials exposed
- ✅ Privacy violation: Student PII disclosed
- ✅ Integrity breach: Files successfully modified
- ✅ Data corruption: Malicious content injected
- ✅ Fraud potential: Fake records created

**Attack Vectors Demonstrated**:
- Unauthorized read access to backup files
- Unauthorized read access to PII files
- Unauthorized write access to backup files
- Unauthorized write access to data files
- SQL injection via backup file modification
- Data fraud via record injection

**Evidence Collected**:
- Command outputs showing successful read operations
- Command outputs showing successful write operations
- File content displaying injected malicious data
- Permission verification confirming 0666 state

## 9. Remediation Demonstration

This section provides a complete secure-mode demonstration showing how the Weak File Permissions vulnerability is remediated when Security is ON (Secure Mode).

### Pre-Demonstration Setup

**Prerequisites**:
- Docker containers running
- Weak File Permissions vulnerability disabled (toggle ON)
- Access to container shell
- Files may have been modified during vulnerability demonstration

**Initial State**:
- backup.sql may contain malicious injections from previous demonstration
- student_records.csv may contain fake records from previous demonstration
- Permissions will be changed to 0640

### Step 1: Security ON - Permissions Become 0640

**Action**: Disable the Weak File Permissions vulnerability via the admin interface.

**Procedure**:
1. Navigate to `http://localhost:8080/admin/security-settings.php`
2. Toggle "Weak File Permissions" to OFF (unchecked)
3. Click "Save Security Settings"

**Expected Result**: 
- Database updated: `security_settings.enabled = 0`
- PHP function `syncFilePermissions(false)` executed
- Files set to 0640 permissions
- Log entry created

**Security Relevance**: This action restores secure file permissions, remediating the vulnerability.

### Step 2: Verify Secure Permissions

**Action**: Confirm files have secure permissions using Linux commands.

**Commands**:
```bash
docker exec -it myeduconnect-web bash
ls -l /var/www/html/storage/backups/backup.sql
stat -c "%a %n" /var/www/html/storage/backups/backup.sql
```

**Expected Output**:
```
-rw-r----- 1 www-data www-data 512 Jun 19 12:35 backup.sql
0640 /var/www/html/storage/backups/backup.sql
```

**Security Relevance**: Confirms that files are now in secure state with restricted permissions (owner read/write, group read only, others no access).

### Step 3: Attacker Attempts to Read Backup File

**Action**: Demonstrate that non-owner users cannot read the backup file.

**Scenario**: Attacker (low-privileged user) attempts to read sensitive credentials.

**Commands**:
```bash
# Switch to non-owner user
su - nobody -c "cat /var/www/html/storage/backups/backup.sql"
```

**Expected Output**:
```
cat: /var/www/html/storage/backups/backup.sql: Permission denied
```

**Security Relevance**: Demonstrates that secure permissions successfully prevent unauthorized read access to sensitive credentials.

### Step 4: Attacker Attempts to Read Student Records

**Action**: Demonstrate that non-owner users cannot read the student records file.

**Commands**:
```bash
su - nobody -c "cat /var/www/html/storage/student_records.csv"
```

**Expected Output**:
```
cat: /var/www/html/storage/student_records.csv: Permission denied
```

**Security Relevance**: Demonstrates that secure permissions successfully prevent unauthorized read access to PII.

### Step 5: Attacker Attempts to Modify Backup File

**Action**: Demonstrate that non-owner users cannot modify the backup file.

**Commands**:
```bash
su - nobody -c "echo 'MALICIOUS' >> /var/www/html/storage/backups/backup.sql"
```

**Expected Output**:
```
bash: /var/www/html/storage/backups/backup.sql: Permission denied
```

**Security Relevance**: Demonstrates that secure permissions successfully prevent unauthorized write access, protecting data integrity.

### Step 6: Attacker Attempts to Modify Student Records

**Action**: Demonstrate that non-owner users cannot modify the student records file.

**Commands**:
```bash
su - nobody -c "echo 'FAKE' >> /var/www/html/storage/student_records.csv"
```

**Expected Output**:
```
bash: /var/www/html/storage/student_records.csv: Permission denied
```

**Security Relevance**: Demonstrates that secure permissions successfully prevent unauthorized data modification, preventing fraud.

### Step 7: Owner Can Still Access Files

**Action**: Demonstrate that the file owner (www-data) can still read and write files.

**Commands**:
```bash
# As owner (default when entering container)
cat /var/www/html/storage/backups/backup.sql
echo "OWNER WRITE TEST" >> /var/www/html/storage/backups/backup.sql
```

**Expected Output**: 
- File contents display successfully
- Write operation completes successfully
- No permission denied errors

**Security Relevance**: Confirms that legitimate access (owner) is not impacted by security hardening, maintaining system functionality.

### Step 8: Verification Confirms Protection

**Action**: Confirm that files remain in secure state and unauthorized access is blocked.

**Commands**:
```bash
ls -l /var/www/html/storage/backups/backup.sql
ls -l /var/www/html/storage/student_records.csv
```

**Expected Output**:
```
-rw-r----- 1 www-data www-data 530 Jun 19 12:36 backup.sql
-rw-r----- 1 www-data www-data 282 Jun 19 12:36 student_records.csv
```

**Security Relevance**: Confirms that secure permissions persist and unauthorized access remains blocked.

### Summary of Remediation Demonstration

**Security Protection Achieved**:
- ✅ Confidentiality protected: Unauthorized read access blocked
- ✅ Privacy protected: PII access restricted to authorized users
- ✅ Integrity protected: Unauthorized write access blocked
- ✅ Data integrity: Malicious modifications prevented
- ✅ Fraud prevention: Fake record injection blocked

**Protection Mechanisms Demonstrated**:
- Non-owner users cannot read sensitive files
- Non-owner users cannot write to sensitive files
- File owner retains full access for legitimate operations
- Group members have read-only access (if applicable)
- Principle of least privilege enforced

**Evidence Collected**:
- Command outputs showing permission denied errors
- Permission verification confirming 0640 state
- Owner access confirmation showing legitimate operations work

## 10. Before and After Comparison

This table compares the security state of the system before and after remediation.

| Feature | Vulnerable Mode (Security OFF) | Secure Mode (Security ON) |
|---------|--------------------------------|---------------------------|
| **Permissions** | 0666 (rw-rw-rw-) | 0640 (rw-r-----) |
| **Owner Access** | Read + Write | Read + Write |
| **Group Access** | Read + Write | Read Only |
| **Others Access** | Read + Write | No Access |
| **Risk Level** | HIGH | LOW |
| **Attack Success (Read)** | YES - Any user can read files | NO - Non-owner users blocked |
| **Attack Success (Write)** | YES - Any user can modify files | NO - Non-owner users blocked |
| **Attack Success (Delete)** | YES - Any user can delete files | NO - Non-owner users blocked |
| **Confidentiality** | COMPROMISED - All users can read | PROTECTED - Only owner/group can read |
| **Integrity** | COMPROMISED - All users can modify | PROTECTED - Only owner can modify |
| **Compliance** | VIOLATED - Fails security standards | COMPLIANT - Meets security standards |
| **Principle of Least Privilege** | VIOLATED - Overly permissive | ENFORCED - Minimal required access |
| **Audit Trail** | Logged in file_permissions.log | Logged in file_permissions.log |
| **UI Status Badge** | VULNERABLE (red) | SECURE (green) |
| **Readable by Others** | YES | NO |
| **Writable by Others** | YES | NO |
| **Executable by Others** | NO | NO |
| **Web Server Access** | Full access | Read-only (group) |
| **Low-Privileged User Access** | Full access | No access |

## 11. Expected Screenshots

This section lists every screenshot required for the final assignment report. Each screenshot should be captured with clear visibility of relevant elements.

### Screenshot 1: Security Settings Page - Initial State

**What to Capture**:
- Full Security Vulnerability Manager page
- "Weak File Permissions" toggle in disabled state (unchecked)
- "File Permissions Status" card showing SECURE badges
- Current permission values (0640)
- Readable/Writable indicators

**Purpose**: Establishes baseline secure state before testing.

**Evidence Value**: Shows that the system starts in a secure configuration.

### Screenshot 2: Security Settings Page - Vulnerability Enabled

**What to Capture**:
- Security Vulnerability Manager page
- "Weak File Permissions" toggle in enabled state (checked)
- "File Permissions Status" card showing VULNERABLE badges
- Current permission values (0666)
- Readable/Writable indicators showing YES
- No error messages

**Purpose**: Documents the act of enabling the vulnerability.

**Evidence Value**: Shows that the vulnerability can be enabled via the admin interface.

### Screenshot 3: Test Page - Vulnerable Mode Status

**What to Capture**:
- Full File Permissions Test page
- "Current Mode" alert showing "VULNERABLE (Security OFF)"
- "Current File Permissions" table
- Both files showing 0666 permissions
- Security Status badges showing VULNERABLE
- Risk Level badges showing HIGH

**Purpose**: Provides detailed diagnostic information for vulnerable state.

**Evidence Value**: Shows comprehensive permission status in vulnerable mode.

### Screenshot 4: Test Page - Diagnostic Table (Vulnerable)

**What to Capture**:
- "File Permissions Diagnostics" section
- Diagnostic table showing:
  - File Name: backup.sql, student_records.csv
  - Current Permission Octal: 0666
  - Readable: YES (green checkmark)
  - Writable: YES (green checkmark)
  - Expected State: VULNERABLE (0666)
  - Actual State: VULNERABLE
  - Pass/Fail: PASS (green badge)

**Purpose**: Shows diagnostic validation confirming vulnerable state.

**Evidence Value**: Provides evidence that the system correctly identifies and reports the vulnerable state.

### Screenshot 5: Docker Terminal - Permission Verification (Vulnerable)

**What to Capture**:
- Docker container terminal window
- Command: `ls -l /var/www/html/storage/backups/backup.sql`
- Output showing: `-rw-rw-rw-` permissions
- Command: `stat -c "%a %n" /var/www/html/storage/backups/backup.sql`
- Output showing: `0666`

**Purpose**: Provides command-line evidence of vulnerable permissions.

**Evidence Value**: Shows independent verification of vulnerable state outside the web interface.

### Screenshot 6: Docker Terminal - Successful File Read

**What to Capture**:
- Docker container terminal window
- Command: `cat /var/www/html/storage/backups/backup.sql`
- Output showing complete file contents including credentials

**Purpose**: Demonstrates that files are readable in vulnerable mode.

**Evidence Value**: Shows proof that sensitive data can be accessed when vulnerability is enabled.

### Screenshot 7: Docker Terminal - Successful File Write

**What to Capture**:
- Docker container terminal window
- Command: `echo "TEST" >> /var/www/html/storage/backups/backup.sql`
- No error message (success)
- Verification command: `tail -3 /var/www/html/storage/backups/backup.sql`
- Output showing appended "TEST" line

**Purpose**: Demonstrates that files are writable in vulnerable mode.

**Evidence Value**: Shows proof that files can be modified when vulnerability is enabled.

### Screenshot 8: Security Settings Page - Vulnerability Disabled

**What to Capture**:
- Security Vulnerability Manager page
- "Weak File Permissions" toggle in disabled state (unchecked)
- "File Permissions Status" card showing SECURE badges
- Current permission values (0640)
- Readable/Writable indicators

**Purpose**: Documents the act of disabling the vulnerability (remediation).

**Evidence Value**: Shows that the vulnerability can be disabled via the admin interface.

### Screenshot 9: Test Page - Secure Mode Status

**What to Capture**:
- Full File Permissions Test page
- "Current Mode" alert showing "SECURE (Security ON)"
- "Current File Permissions" table
- Both files showing 0640 permissions
- Security Status badges showing SECURE
- Risk Level badges showing LOW

**Purpose**: Provides detailed diagnostic information for secure state.

**Evidence Value**: Shows comprehensive permission status in secure mode.

### Screenshot 10: Test Page - Diagnostic Table (Secure)

**What to Capture**:
- "File Permissions Diagnostics" section
- Diagnostic table showing:
  - File Name: backup.sql, student_records.csv
  - Current Permission Octal: 0640
  - Readable: YES (green checkmark)
  - Writable: YES (green checkmark for owner)
  - Expected State: SECURE (0640)
  - Actual State: SECURE
  - Pass/Fail: PASS (green badge)

**Purpose**: Shows diagnostic validation confirming secure state.

**Evidence Value**: Provides evidence that the system correctly identifies and reports the secure state.

### Screenshot 11: Docker Terminal - Permission Verification (Secure)

**What to Capture**:
- Docker container terminal window
- Command: `ls -l /var/www/html/storage/backups/backup.sql`
- Output showing: `-rw-r-----` permissions
- Command: `stat -c "%a %n" /var/www/html/storage/backups/backup.sql`
- Output showing: `0640`

**Purpose**: Provides command-line evidence of secure permissions.

**Evidence Value**: Shows independent verification of secure state outside the web interface.

### Screenshot 12: Docker Terminal - Failed Read Attempt

**What to Capture**:
- Docker container terminal window
- Command: `su - nobody -c "cat /var/www/html/storage/backups/backup.sql"`
- Output showing: `Permission denied` error

**Purpose**: Demonstrates that unauthorized read access is blocked in secure mode.

**Evidence Value**: Shows proof that remediation successfully prevents unauthorized access.

### Screenshot 13: Docker Terminal - Failed Write Attempt

**What to Capture**:
- Docker container terminal window
- Command: `su - nobody -c "echo test >> /var/www/html/storage/backups/backup.sql"`
- Output showing: `Permission denied` error

**Purpose**: Demonstrates that unauthorized write access is blocked in secure mode.

**Evidence Value**: Shows proof that remediation successfully prevents unauthorized modification.

### Screenshot 14: Log File Output

**What to Capture**:
- Docker container terminal window
- Command: `cat /var/www/html/storage/file_permissions.log`
- Output showing recent permission change entries
- Entries showing both SECURE and VULNERABLE mode changes
- Success: YES for all entries

**Purpose**: Provides audit trail of permission changes.

**Evidence Value**: Shows that the system logs all permission changes with timestamps and success status.

### Screenshot 15: Final Secure Status

**What to Capture**:
- Security Settings page
- "File Permissions Status" card
- Both files showing SECURE badges
- 0640 permissions
- No error messages
- No warning banners

**Purpose**: Documents final state after complete testing cycle.

**Evidence Value**: Shows that the system returns to secure state after testing is complete.

## 12. Troubleshooting

This section provides solutions for common issues that may be encountered during testing or operation of the Weak File Permissions vulnerability.

### chmod Not Working

**Symptoms**:
- Permissions do not change after toggling vulnerability
- Log file shows "Success: NO"
- UI shows error messages

**Possible Causes**:
1. File ownership issues (files not owned by www-data)
2. Docker volume mount restrictions
3. Windows filesystem limitations (on Windows hosts)
4. Insufficient container user privileges

**Solutions**:

**Solution 1: Check File Ownership**
```bash
# Purpose: Verify file ownership
# Expected Result: Files owned by www-data:www-data
docker exec myeduconnect-web stat -c "%U:%G %n" /var/www/html/storage/backups/backup.sql
```

**Solution 2: Fix File Ownership**
```bash
# Purpose: Correct file ownership to www-data
# Expected Result: Ownership changed successfully
docker exec myeduconnect-web chown www-data:www-data /var/www/html/storage/backups/backup.sql
docker exec myeduconnect-web chown www-data:www-data /var/www/html/storage/student_records.csv
```

**Solution 3: Run as Root Inside Container**
```bash
# Purpose: Execute chmod with root privileges
# Expected Result: Permissions change successfully
docker exec -u 0 myeduconnect-web chmod 640 /var/www/html/storage/backups/backup.sql
docker exec -u 0 myeduconnect-web chmod 640 /var/www/html/storage/student_records.csv
```

**Solution 4: Check for Windows ACL Restrictions**
```bash
# Purpose: Identify Windows ACL issues on Windows hosts
# Expected Result: ACL information displayed
# On Windows host:
icacls "u:\Ethi-Assignment\ethical-hacking--master\storage\backups\backup.sql"
```

### Docker Permission Issues

**Symptoms**:
- Cannot access container shell
- Permission denied when executing commands
- Volume mount errors

**Possible Causes**:
1. Container not running
2. Incorrect container name
3. Insufficient Docker permissions
4. Volume mount path issues

**Solutions**:

**Solution 1: Verify Container Status**
```bash
# Purpose: Confirm container is running
# Expected Result: Container shows "Up" status
docker ps
```

**Solution 2: Restart Container**
```bash
# Purpose: Restart container to resolve transient issues
# Expected Result: Container restarts successfully
docker-compose restart web
```

**Solution 3: Check Docker Permissions**
```bash
# Purpose: Verify user has Docker access
# Expected Result: Docker commands execute successfully
docker ps
```

**Solution 4: Verify Volume Mount**
```bash
# Purpose: Confirm volume is properly mounted
# Expected Result: Volume mount listed in container inspect output
docker inspect myeduconnect-web | grep -A 10 Mounts
```

### Mounted Volume Issues

**Symptoms**:
- Files not visible inside container
- Changes in container not reflected on host
- Permission inconsistencies between host and container

**Possible Causes**:
1. Volume mount path incorrect
2. Volume not mounted at all
3. Filesystem incompatibility
4. SELinux/AppArmor restrictions

**Solutions**:

**Solution 1: Verify Volume Mount**
```bash
# Purpose: Confirm volume is mounted correctly
# Expected Result: Volume path matches docker-compose.yml
docker exec myeduconnect-web pwd
docker exec myeduconnect-web ls -la /var/www/html/storage/
```

**Solution 2: Check docker-compose.yml**
```yaml
# Purpose: Verify volume configuration in docker-compose.yml
# Expected Result: Volume mounts configured correctly
volumes:
  - .:/var/www/html
```

**Solution 3: Rebuild Container**
```bash
# Purpose: Rebuild container with correct volume mounts
# Expected Result: Container rebuilds successfully
docker-compose down
docker-compose up -d --build
```

**Solution 4: Check Filesystem Type**
```bash
# Purpose: Verify filesystem supports Unix permissions
# Expected Result: Compatible filesystem (ext4, xfs, etc.)
# On host system:
df -T /path/to/project
```

### File Ownership Problems

**Symptoms**:
- chmod fails even as root
- Files show wrong owner
- Permission changes don't persist

**Possible Causes**:
1. Files owned by root instead of www-data
2. UID/GID mismatch between host and container
3. Incorrect user in Dockerfile

**Solutions**:

**Solution 1: Check Current Ownership**
```bash
# Purpose: Identify current file owner
# Expected Result: Owner displayed
docker exec myeduconnect-web stat -c "%U:%G %n" /var/www/html/storage/backups/backup.sql
```

**Solution 2: Change Ownership**
```bash
# Purpose: Set correct ownership to www-data
# Expected Result: Ownership changed successfully
docker exec myeduconnect-web chown -R www-data:www-data /var/www/html/storage/
```

**Solution 3: Verify Container User**
```bash
# Purpose: Check which user PHP runs as
# Expected Result: www-data or equivalent
docker exec myeduconnect-web whoami
```

**Solution 4: Check Dockerfile Configuration**
```dockerfile
# Purpose: Verify Dockerfile sets correct user
# Expected Result: USER www-data or equivalent
# In docker/Dockerfile:
RUN chown -R www-data:www-data /var/www/html
```

### Missing Files

**Symptoms**:
- UI shows "NOT FOUND" badge
- File operations fail with "No such file or directory"
- Log shows file does not exist

**Possible Causes**:
1. Files deleted accidentally
2. Files in wrong directory
3. Directory structure not created
4. Case sensitivity issues

**Solutions**:

**Solution 1: Check File Existence**
```bash
# Purpose: Verify files exist
# Expected Result: Files listed
docker exec myeduconnect-web ls -la /var/www/html/storage/backups/
docker exec myeduconnect-web ls -la /var/www/html/storage/
```

**Solution 2: Create Missing Directories**
```bash
# Purpose: Create required directory structure
# Expected Result: Directories created successfully
docker exec myeduconnect-web mkdir -p /var/www/html/storage/backups/
```

**Solution 3: Recreate Missing Files**
```bash
# Purpose: Create missing files with default content
# Expected Result: Files created successfully
docker exec myeduconnect-web bash -c 'echo "-- Backup placeholder" > /var/www/html/storage/backups/backup.sql'
docker exec myeduconnect-web bash -c 'echo "student_id,email,name" > /var/www/html/storage/student_records.csv'
```

**Solution 4: Check Case Sensitivity**
```bash
# Purpose: Verify filenames match exactly (case-sensitive)
# Expected Result: Correct filenames identified
docker exec myeduconnect-web ls -la /var/www/html/storage/ | grep -i backup
docker exec myeduconnect-web ls -la /var/www/html/storage/ | grep -i student
```

### Status Not Updating

**Symptoms**:
- UI shows stale permission values
- Toggle changes don't affect displayed status
- Status doesn't reflect actual file permissions

**Possible Causes**:
1. PHP opcode cache
2. Browser cache
3. File status cache not cleared
4. Database not updated

**Solutions**:

**Solution 1: Clear PHP Cache**
```bash
# Purpose: Clear PHP opcode cache
# Expected Result: Cache cleared
docker exec myeduconnect-web bash -c "rm -rf /var/www/html/storage/cache/*"
```

**Solution 2: Restart Web Server**
```bash
# Purpose: Restart Apache to clear caches
# Expected Result: Web server restarts successfully
docker-compose restart web
```

**Solution 3: Clear Browser Cache**
```Purpose**: Clear browser cache and reload
# Expected Result: Fresh page loads
# Action: Press Ctrl+F5 or clear browser cache manually
```

**Solution 4: Check Database State**
```bash
# Purpose: Verify database reflects toggle state
# Expected Result: Database shows correct enabled value
docker exec -it myeduconnect-mysql mysql -u root -prootpassword myeduconnect -e "SELECT * FROM security_settings WHERE vulnerability_name = 'weak_file_permissions';"
```

### Cached Results

**Symptoms**:
- File permissions show old values
- Status doesn't update immediately after toggle
- clearstatcache not called

**Possible Causes**:
1. PHP file status cache
2. Browser caching
3. CDN caching (if applicable)
4. Application-level caching

**Solutions**:

**Solution 1: Verify clearstatcache Implementation**
```php
# Purpose: Confirm PHP function calls clearstatcache
# Expected Result: clearstatcache(true, $file) called before fileperms()
# In app/security/functions.php, verify:
clearstatcache(true, $file);
$perms = substr(sprintf('%o', fileperms($file)), -4);
```

**Solution 2: Force Cache Refresh**
```bash
# Purpose: Force refresh of file status
# Expected Result: Fresh permissions read
# Touch files to update modification time
docker exec myeduconnect-web touch /var/www/html/storage/backups/backup.sql
docker exec myeduconnect-web touch /var/www/html/storage/student_records.csv
```

**Solution 3: Wait for Cache Expiration**
```Purpose**: Allow time for cache to expire naturally
# Expected Result: Fresh data after TTL
# Action: Wait 1-2 minutes, then refresh page
```

**Solution 4: Disable Caching (Development Only)**
```php
// Purpose: Disable OPcache for development
// Expected Result: No caching
// In php.ini or Dockerfile:
opcache.enable=0
opcache.enable_cli=0
```

## 13. Assessment Evidence Checklist

This checklist is provided for instructors, examiners, or security auditors to systematically verify the Weak File Permissions vulnerability implementation. Each item should be confirmed during the assessment process.

### Vulnerability Existence

☐ **Vulnerability exists in the application**
   - Evidence: "Weak File Permissions" toggle visible in Security Settings page
   - Evidence: Vulnerability listed in security_settings database table
   - Evidence: Monitored files exist in storage directory

☐ **Vulnerability is properly documented**
   - Evidence: README_WEAK_FILE_PERMISSIONS.md exists and is comprehensive
   - Evidence: Documentation explains purpose, risks, and remediation
   - Evidence: Testing procedures are clearly documented

### Vulnerability Toggle Functionality

☐ **Vulnerability can be enabled**
   - Evidence: Toggle can be set to ON (checked)
   - Evidence: Save Security Settings button works
   - Evidence: Success message displayed after enabling
   - Evidence: Database updated (enabled = 1)

☐ **Vulnerability can be disabled**
   - Evidence: Toggle can be set to OFF (unchecked)
   - Evidence: Save Security Settings button works
   - Evidence: Success message displayed after disabling
   - Evidence: Database updated (enabled = 0)

☐ **Toggle system functions correctly**
   - Evidence: Toggle state persists across page refreshes
   - Evidence: Toggle state persists across container restarts
   - Evidence: UI accurately reflects current toggle state
   - Evidence: No error messages during normal operation

### File Permission Changes

☐ **File permissions change correctly when enabling vulnerability**
   - Evidence: backup.sql changes to 0666 when vulnerability enabled
   - Evidence: student_records.csv changes to 0666 when vulnerability enabled
   - Evidence: Both files change atomically (all or none)
   - Evidence: Log file records successful permission changes

☐ **File permissions change correctly when disabling vulnerability**
   - Evidence: backup.sql changes to 0640 when vulnerability disabled
   - Evidence: student_records.csv changes to 0640 when vulnerability disabled
   - Evidence: Both files change atomically (all or none)
   - Evidence: Log file records successful permission changes

☐ **Permissions verified via Linux commands**
   - Evidence: `ls -l` shows correct symbolic permissions
   - Evidence: `stat` shows correct octal permissions
   - Evidence: `stat -c "%a %n"` confirms permission values
   - Evidence: Commands executed inside Docker container

### Vulnerability Exploitation

☐ **Vulnerability is exploitable in vulnerable mode**
   - Evidence: Files can be read by any user in vulnerable mode
   - Evidence: Files can be written by any user in vulnerable mode
   - Evidence: Sensitive data (credentials, PII) is accessible
   - Evidence: File modifications persist

☐ **Unauthorized read access possible**
   - Evidence: `cat` command reads backup.sql successfully
   - Evidence: `cat` command reads student_records.csv successfully
   - Evidence: Non-owner user can read files
   - Evidence: No permission denied errors

☐ **Unauthorized write access possible**
   - Evidence: `echo` command appends to files successfully
   - Evidence: File modifications persist after write
   - Evidence: Non-owner user can write to files
   - Evidence: No permission denied errors

☐ **Attack demonstration successful**
   - Evidence: Credentials extracted from backup.sql
   - Evidence: PII read from student_records.csv
   - Evidence: Malicious content injected into files
   - Evidence: Fake records created in data files

### Remediation Verification

☐ **Remediation works when vulnerability disabled**
   - Evidence: Files cannot be read by non-owner users in secure mode
   - Evidence: Files cannot be written by non-owner users in secure mode
   - Evidence: Permission denied errors displayed
   - Evidence: Access controls enforced correctly

☐ **Unauthorized read access blocked**
   - Evidence: `cat` command fails with permission denied
   - Evidence: Non-owner user cannot read files
   - Evidence: Sensitive data remains protected
   - Evidence: Error messages clearly indicate permission denied

☐ **Unauthorized write access blocked**
   - Evidence: `echo` command fails with permission denied
   - Evidence: Non-owner user cannot write to files
   - Evidence: File integrity protected
   - Evidence: Error messages clearly indicate permission denied

☐ **Legitimate access maintained**
   - Evidence: File owner can still read files
   - Evidence: File owner can still write to files
   - Evidence: System functionality not impaired
   - Evidence: Web server can access files as needed

### Security Status Display

☐ **Security status updates correctly in UI**
   - Evidence: UI shows SECURE badge when permissions are 0640
   - Evidence: UI shows VULNERABLE badge when permissions are 0666
   - Evidence: Status updates immediately after toggle
   - Evidence: Status reflects actual file permissions

☐ **Real-time validation implemented**
   - Evidence: UI uses file_exists() to check file presence
   - Evidence: UI uses is_readable() to check read access
   - Evidence: UI uses is_writable() to check write access
   - Evidence: UI uses fileperms() to get actual permissions

☐ **Expected vs actual comparison displayed**
   - Evidence: UI shows expected permissions for current mode
   - Evidence: UI shows actual permissions from filesystem
   - Evidence: UI indicates when expected != actual
   - Evidence: Error messages displayed for mismatches

☐ **Diagnostic information available**
   - Evidence: Test page provides comprehensive diagnostics
   - Evidence: Diagnostic table shows all file status
   - Evidence: Pass/fail indicators for each file
   - Evidence: Docker commands provided for verification

### Evidence Collection

☐ **Screenshots collected successfully**
   - Evidence: Security Settings page captured (both states)
   - Evidence: Test page captured (both modes)
   - Evidence: Docker terminal captured (permission verification)
   - Evidence: Docker terminal captured (exploitation demonstration)
   - Evidence: Docker terminal captured (remediation demonstration)
   - Evidence: Log file output captured
   - Evidence: Final secure status captured

☐ **Command outputs preserved**
   - Evidence: Linux command outputs saved
   - Evidence: Permission verification commands documented
   - Evidence: Exploitation commands documented
   - Evidence: Remediation commands documented

☐ **Log entries preserved**
   - Evidence: file_permissions.log content captured
   - Evidence: Audit log entries captured
   - Evidence: Timestamps and success status visible
   - Evidence: Permission changes traceable

☐ **Database records preserved**
   - Evidence: security_settings table state captured
   - Evidence: Vulnerability enabled/disabled status documented
   - Evidence: Audit log records captured

### Overall Assessment

☐ **Implementation meets requirements**
   - Evidence: All required features implemented
   - Evidence: Toggle system works correctly
   - Evidence: File permissions change as specified
   - Evidence: Error handling implemented

☐ **Educational objectives achieved**
   - Evidence: Vulnerability demonstrates security risk clearly
   - Evidence: Remediation demonstrates security fix clearly
   - Evidence: Testing procedures are comprehensive
   - Evidence: Documentation is complete and accurate

☐ **Code quality acceptable**
   - Evidence: No syntax errors
   - Evidence: Proper error handling
   - Evidence: Atomic operations implemented
   - Evidence: Logging and audit trail functional

☐ **Ready for grading**
   - Evidence: All evidence collected
   - Evidence: Screenshots clear and labeled
   - Evidence: Command outputs verifiable
   - Evidence: Documentation complete

---

**Document Version:** 2.0  
**Last Updated:** June 19, 2026  
**Author:** Senior Cybersecurity Engineer  
**Purpose:** Academic Assessment and Instructor Testing Guide
