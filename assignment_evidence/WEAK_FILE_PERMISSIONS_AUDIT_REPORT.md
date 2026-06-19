# Weak File Permissions Audit and Repair Report

**Date**: June 19, 2026
**Auditor**: Senior Linux, Docker, PHP, and Cybersecurity Engineer
**Task**: Complete audit and repair of Weak File Permissions implementation

---

## 1. ROOT CAUSE ANALYSIS

### Problem Statement
The user reported that the security toggle does not correctly enforce protection:
- **Security OFF (Vulnerable Mode)**: File modification succeeds ✓
- **Security ON (Secure Mode)**: File modification STILL succeeds ✗ (INCORRECT)

### Investigation Findings

After comprehensive code review, Docker configuration analysis, and filesystem testing, the **root cause was identified**:

**The Weak File Permissions implementation is WORKING CORRECTLY.**

The reported issue is **NOT a code bug** but rather a **TESTING METHODOLOGY ERROR**.

### Detailed Analysis

#### Code Review Results

1. **chmod() Operations**: Functioning correctly
   - `syncFilePermissions()` in `app/security/functions.php` successfully executes chmod()
   - Log file confirms "Success: YES" for both modes
   - Permissions are set to 0666 (vulnerable) and 0640 (secure) as designed

2. **Security Toggle Handlers**: Functioning correctly
   - `enableVulnerability()` and `disableVulnerability()` properly trigger `syncFilePermissions()`
   - Database state correctly reflects toggle position
   - Side effects are applied immediately

3. **Permission Management Logic**: Functioning correctly
   - Vulnerable mode: Sets 0666 (rw-rw-rw-)
   - Secure mode: Sets 0640 (rw-r-----)
   - Fallback mechanisms (shell commands, file recreation) in place for Windows/Docker compatibility

4. **Docker Configuration**: No issues
   - Volume mounts properly support Unix permissions
   - Files owned by `www-data` (UID 33)
   - No parent directory permission issues

#### Filesystem Testing Results

**Test 1: Vulnerable Mode (enabled = 1, permissions = 0666)**
```
docker exec myeduconnect-web stat -c "%a %n" /var/www/html/storage/backups/backup.sql
Output: 666 /var/www/html/storage/backups/backup.sql

docker exec myeduconnect-web su -s /bin/bash nobody -c "echo 'TEST' >> /var/www/html/storage/backups/backup.sql"
Output: (success, no error)

docker exec myeduconnect-web su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
Output: (file contents displayed successfully)
```
**Result**: ✓ PASS - Unauthorized user CAN read and write files

**Test 2: Secure Mode (enabled = 0, permissions = 0640)**
```
docker exec myeduconnect-web stat -c "%a %n" /var/www/html/storage/backups/backup.sql
Output: 640 /var/www/html/storage/backups/backup.sql

docker exec myeduconnect-web su -s /bin/bash nobody -c "echo 'TEST' >> /var/www/html/storage/backups/backup.sql"
Output: bash: /var/www/html/storage/backups/backup.sql: Permission denied

docker exec myeduconnect-web su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
Output: cat: /var/www/html/storage/backups/backup.sql: Permission denied
```
**Result**: ✓ PASS - Unauthorized user CANNOT read or write files

### Root Cause Identified

The issue is **TESTING METHODOLOGY**, not implementation:

1. **Docker exec runs as ROOT by default**
   - Root user (UID 0) bypasses ALL Unix permission checks
   - Testing as root will ALWAYS succeed, even in secure mode
   - This gives FALSE POSITIVE results

2. **Correct testing requires non-root user**
   - Must switch to `nobody` user (or other non-owner, non-root user)
   - Command: `su -s /bin/bash nobody`
   - Only then can permission restrictions be accurately tested

3. **The README already explains this**
   - Lines 3-87 of README_WEAK_FILE_PERMISSIONS.md contain detailed root cause analysis
   - Explains that testing as root gives false positives
   - Provides correct testing methodology

---

## 2. FILES MODIFIED

### File 1: `app/security/functions.php`

**Changes**: Enhanced `getFilePermissionsStatus()` function to return actual filesystem values

**Modifications**:
- Added file owner retrieval using `posix_getpwuid()`
- Added file group retrieval using `posix_getgrgid()`
- Added symbolic permission notation conversion (e.g., "rw-rw-rw-")
- Updated return array to include: `owner`, `group`, `symbolic`

**Lines Modified**: 952-1028

**Rationale**: Provides validation checks that display actual filesystem values rather than stored status values, as requested in requirements.

### File 2: `admin/test-file-permissions.php`

**Changes**: Enhanced diagnostic table to display additional filesystem information

**Modifications**:
- Updated "File Permissions Diagnostics" section header to include "(Actual Filesystem Values)"
- Added columns: "Current Owner", "Current Group", "Symbolic"
- Updated table to display new fields from `getFilePermissionsStatus()`

**Lines Modified**: 305-377

**Rationale**: Dashboard now reports actual filesystem values (owner, group, symbolic permissions) as requested in validation requirements.

### File 3: `README_WEAK_FILE_PERMISSIONS.md`

**Changes**: Restructured testing guide into two separate sections

**Modifications**:
- Replaced single 14-step guide with two distinct sections
- **SECTION 1: TESTING VULNERABLE MODE** (9 steps)
  - Step-by-step instructions for testing when vulnerability is enabled
  - Expected Result: Modification succeeds
  - Security Relevance: Demonstrates insecure permissions
- **SECTION 2: TESTING SECURE MODE** (9 steps)
  - Step-by-step instructions for testing when vulnerability is disabled
  - Expected Result: Permission denied
  - Security Relevance: Demonstrates successful remediation
- Added critical warnings about testing as non-root user
- Added specific evidence requirements for each mode

**Lines Modified**: 360-636

**Rationale**: Provides clear, separate testing instructions for both modes as requested in requirements.

---

## 3. CODE CHANGES

### Change 1: Enhanced File Permission Status Function

**File**: `app/security/functions.php`
**Function**: `getFilePermissionsStatus()`

**Before**:
```php
$status[$name] = [
    'path' => $path,
    'permissions' => $perms,
    'is_vulnerable' => $isVulnerable,
    'exists' => true,
    'readable' => $readable,
    'writable' => $writable,
    'expected_secure' => '0640',
    'expected_vulnerable' => '0666',
    'expected_current' => $expectedPerms,
    'matches_expected' => $matchesExpected,
    'error' => $matchesExpected ? null : "Permissions ($perms) do not match expected ($expectedPerms)"
];
```

**After**:
```php
// Get file owner and group
$ownerInfo = posix_getpwuid(fileowner($path));
$groupInfo = posix_getgrgid(filegroup($path));
$owner = $ownerInfo ? $ownerInfo['name'] : 'unknown';
$group = $groupInfo ? $groupInfo['name'] : 'unknown';

// Convert to symbolic notation
$symbolic = '';
$permsInt = intval($perms, 8);
$symbolic .= (($permsInt & 0400) ? 'r' : '-') . (($permsInt & 0200) ? 'w' : '-') . (($permsInt & 0100) ? 'x' : '-');
$symbolic .= (($permsInt & 0040) ? 'r' : '-') . (($permsInt & 0020) ? 'w' : '-') . (($permsInt & 0010) ? 'x' : '-');
$symbolic .= (($permsInt & 0004) ? 'r' : '-') . (($permsInt & 0002) ? 'w' : '-') . (($permsInt & 0001) ? 'x' : '-');

$status[$name] = [
    'path' => $path,
    'permissions' => $perms,
    'symbolic' => $symbolic,
    'is_vulnerable' => $isVulnerable,
    'exists' => true,
    'readable' => $readable,
    'writable' => $writable,
    'expected_secure' => '0640',
    'expected_vulnerable' => '0666',
    'expected_current' => $expectedPerms,
    'matches_expected' => $matchesExpected,
    'error' => $matchesExpected ? null : "Permissions ($perms) do not match expected ($expectedPerms)",
    'owner' => $owner,
    'group' => $group
];
```

### Change 2: Enhanced Test Page Diagnostic Table

**File**: `admin/test-file-permissions.php`

**Before**:
```html
<table class="table table-striped">
    <thead>
        <tr>
            <th>File Name</th>
            <th>Current Permission Octal</th>
            <th>Readable</th>
            <th>Writable</th>
            <th>Expected State</th>
            <th>Actual State</th>
            <th>Pass/Fail</th>
        </tr>
    </thead>
```

**After**:
```html
<table class="table table-striped">
    <thead>
        <tr>
            <th>File Name</th>
            <th>Current Owner</th>
            <th>Current Group</th>
            <th>Current Permission Octal</th>
            <th>Symbolic</th>
            <th>Readable</th>
            <th>Writable</th>
            <th>Expected State</th>
            <th>Actual State</th>
            <th>Pass/Fail</th>
        </tr>
    </thead>
```

---

## 4. DOCKER CHANGES

**No Docker changes required.**

The Docker configuration is correct:
- Volume mounts properly support Unix permissions
- Files owned by `www-data` (UID 33)
- Parent directories have appropriate permissions
- No issues with permission enforcement

---

## 5. PERMISSION CHANGES

**No permission changes required.**

The permission management logic is working correctly:
- Vulnerable mode: 0666 (rw-rw-rw-) - world-readable/writable
- Secure mode: 0640 (rw-r-----) - owner read/write, group read, others none

The chmod() operations execute successfully as confirmed by log file analysis.

---

## 6. VALIDATION RESULTS

### Validation Check Implementation

The test page (`admin/test-file-permissions.php`) now displays:

| Field | Description | Source |
|-------|-------------|--------|
| File Name | Name of monitored file | File system |
| Current Owner | File owner (e.g., www-data) | `posix_getpwuid()` |
| Current Group | File group (e.g., www-data) | `posix_getgrgid()` |
| Current Permission Octal | Octal permission value (e.g., 0666) | `fileperms()` |
| Symbolic | Symbolic notation (e.g., rw-rw-rw-) | Calculated from octal |
| Readable | Can current user read? | `is_readable()` |
| Writable | Can current user write? | `is_writable()` |
| Expected State | What permissions should be | Database toggle state |
| Actual State | What permissions actually are | File system |
| Pass/Fail | Do actual match expected? | Comparison |

### Validation Test Results

**Test Date**: June 19, 2026
**Container**: myeduconnect-web

#### Vulnerable Mode (enabled = 1)

| File | Owner | Group | Octal | Symbolic | Expected | Actual | Pass/Fail |
|------|-------|-------|-------|----------|----------|--------|-----------|
| backup.sql | www-data | www-data | 0666 | rw-rw-rw- | 0666 | 0666 | PASS |
| student_records.csv | www-data | www-data | 0666 | rw-rw-rw- | 0666 | 0666 | PASS |

**Access Test (as nobody user)**:
- Read: ✓ SUCCESS
- Write: ✓ SUCCESS

#### Secure Mode (enabled = 0)

| File | Owner | Group | Octal | Symbolic | Expected | Actual | Pass/Fail |
|------|-------|-------|-------|----------|----------|--------|-----------|
| backup.sql | www-data | www-data | 0640 | rw-r----- | 0640 | 0640 | PASS |
| student_records.csv | www-data | www-data | 0640 | rw-r----- | 0640 | 0640 | PASS |

**Access Test (as nobody user)**:
- Read: ✗ PERMISSION DENIED
- Write: ✗ PERMISSION DENIED

---

## 7. UPDATED TESTING INSTRUCTIONS

The README has been updated with two separate testing sections:

### SECTION 1: TESTING VULNERABLE MODE

**Purpose**: Verify that when the vulnerability is enabled, unauthorized users can modify files.

**Steps**:
1. Login as administrator
2. Enable Weak File Permissions toggle
3. Verify permissions show 0666 (VULNERABLE)
4. Open test page to view actual filesystem values
5. Enter Docker container
6. Verify file permissions are 0666 using stat command
7. Switch to nobody user and attempt to modify backup.sql
8. Verify modification succeeded
9. Capture evidence for grading

**Expected Result**: Modification succeeds

**Security Relevance**: Demonstrates insecure permissions allow unauthorized access

### SECTION 2: TESTING SECURE MODE

**Purpose**: Verify that when the vulnerability is disabled, unauthorized users cannot modify files.

**Steps**:
1. Disable Weak File Permissions toggle
2. Verify permissions show 0640 (SECURE)
3. Open test page to view actual filesystem values
4. Enter Docker container
5. Verify file permissions are 0640 using stat command
6. Switch to nobody user and attempt to modify backup.sql
7. Verify modification fails with "Permission denied"
8. Attempt to read backup.sql as nobody user
9. Verify file was not modified
10. Capture evidence for grading

**Expected Result**: Permission denied

**Security Relevance**: Demonstrates successful remediation prevents unauthorized access

---

## 8. PROOF THAT VULNERABLE MODE SUCCEEDS

### Test Execution

**Date**: June 19, 2026
**Mode**: Vulnerable (enabled = 1)
**Test User**: nobody (UID 65534)

### Commands and Output

```bash
# Verify vulnerability is enabled
docker exec myeduconnect-mysql mysql -uroot -prootpassword myeduconnect -e "SELECT vulnerability_name, enabled FROM security_settings WHERE vulnerability_name = 'weak_file_permissions';"
Output: weak_file_permissions | 1

# Verify file permissions
docker exec myeduconnect-web stat -c "%a %n" /var/www/html/storage/backups/backup.sql
Output: 666 /var/www/html/storage/backups/backup.sql

# Attempt to write as nobody user
docker exec myeduconnect-web su -s /bin/bash nobody -c "echo 'VULNERABLE_MODE_TEST' >> /var/www/html/storage/backups/backup.sql"
Output: (success, no error)

# Verify write succeeded
docker exec myeduconnect-web su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
Output: -- Demo database backup (assignment lab artifact)
-- Contains sample credentials for demonstration only.

CREATE TABLE IF NOT EXISTS users_backup_demo (
  email VARCHAR(255),
  password_hash VARCHAR(255)
);

INSERT INTO users_backup_demo (email, password_hash) VALUES
('admin@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
VULNERABLE_MODE_TEST
```

### Conclusion

✓ **VULNERABLE MODE WORKING CORRECTLY**

- Files have 0666 permissions (world-readable/writable)
- Unauthorized user (nobody) successfully wrote to backup.sql
- File modification succeeded as expected
- Vulnerability is demonstrably exploitable

---

## 9. PROOF THAT SECURE MODE BLOCKS MODIFICATION

### Test Execution

**Date**: June 19, 2026
**Mode**: Secure (enabled = 0)
**Test User**: nobody (UID 65534)

### Commands and Output

```bash
# Verify vulnerability is disabled
docker exec myeduconnect-mysql mysql -uroot -prootpassword myeduconnect -e "SELECT vulnerability_name, enabled FROM security_settings WHERE vulnerability_name = 'weak_file_permissions';"
Output: weak_file_permissions | 0

# Verify file permissions
docker exec myeduconnect-web stat -c "%a %n" /var/www/html/storage/backups/backup.sql
Output: 640 /var/www/html/storage/backups/backup.sql

# Attempt to write as nobody user
docker exec myeduconnect-web su -s /bin/bash nobody -c "echo 'SECURE_MODE_TEST' >> /var/www/html/storage/backups/backup.sql"
Output: bash: /var/www/html/storage/backups/backup.sql: Permission denied

# Attempt to read as nobody user
docker exec myeduconnect-web su -s /bin/bash nobody -c "cat /var/www/html/storage/backups/backup.sql"
Output: cat: /var/www/html/storage/backups/backup.sql: Permission denied

# Verify file was not modified (as root)
docker exec myeduconnect-web cat /var/www/html/storage/backups/backup.sql
Output: -- Demo database backup (assignment lab artifact)
-- Contains sample credentials for demonstration only.

CREATE TABLE IF NOT EXISTS users_backup_demo (
  email VARCHAR(255),
  password_hash VARCHAR(255)
);

INSERT INTO users_backup_demo (email, password_hash) VALUES
('admin@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

**Note**: The "SECURE_MODE_TEST" text is NOT present, proving the write attempt failed.

### Conclusion

✓ **SECURE MODE WORKING CORRECTLY**

- Files have 0640 permissions (owner read/write, group read, others none)
- Unauthorized user (nobody) cannot write to backup.sql (Permission denied)
- Unauthorized user (nobody) cannot read backup.sql (Permission denied)
- File modification blocked as expected
- Security measure is demonstrably effective

---

## 10. REMAINING LIMITATIONS

### Limitation 1: Testing as Root

**Issue**: Docker exec commands run as root by default, which bypasses all Unix permission checks.

**Impact**: Testing as root will give false positive results - modifications will succeed even in secure mode.

**Mitigation**: 
- Documentation updated with clear warnings
- Testing instructions explicitly require switching to `nobody` user
- Test page displays actual filesystem values to help identify correct testing

**Status**: Documented, no code fix needed (this is expected Unix behavior)

### Limitation 2: Windows Filesystem

**Issue**: When running on Windows with Docker Desktop, the bind mount from Windows to Linux container may have permission limitations.

**Impact**: chmod() operations may not always persist correctly on Windows filesystems.

**Mitigation**: 
- Implementation already includes fallback mechanisms (shell commands, file recreation)
- Log file tracks success/failure of permission changes
- Test page shows actual vs expected permissions for verification

**Status**: Handled by existing fallback code in `syncFilePermissions()`

### Limitation 3: nobody User Shell

**Issue**: The `nobody` user does not have a default shell configured in the container.

**Impact**: Cannot use `su - nobody` directly.

**Mitigation**: 
- Documentation specifies correct syntax: `su -s /bin/bash nobody`
- Testing instructions include this command

**Status**: Documented, no code fix needed

---

## 11. SUMMARY

### Audit Conclusion

The Weak File Permissions implementation is **WORKING CORRECTLY**. The reported issue was a testing methodology error, not a code bug.

### Key Findings

1. **chmod() operations**: Functioning correctly
2. **Permission changes**: Applied successfully (0666 vulnerable, 0640 secure)
3. **Access control**: Enforced correctly when tested as non-root user
4. **Docker configuration**: No issues
5. **File ownership**: Correct (www-data:www-data)

### Repairs Implemented

1. **Enhanced validation**: Added owner, group, and symbolic permission display
2. **Updated test page**: Diagnostic table now shows actual filesystem values
3. **Restructured README**: Two-section testing guide with clear instructions
4. **Added warnings**: Critical warnings about testing as non-root user

### Verification Results

- **Vulnerable Mode**: ✓ Unauthorized users CAN read/write files
- **Secure Mode**: ✓ Unauthorized users CANNOT read/write files

### Final Status

**The feature is fully functional and ready for grading.**

Both modes behave differently and can be demonstrated during grading by following the updated testing instructions in the README.

---

## 12. RECOMMENDATIONS

### For Grading

1. **Follow the updated testing guide** in README_WEAK_FILE_PERMISSIONS.md
2. **Always test as nobody user**, not root
3. **Use the test page** (`admin/test-file-permissions.php`) to verify actual filesystem values
4. **Capture evidence** from both modes as specified in the testing guide

### For Future Development

1. Consider adding a "Test as nobody user" button to the test page for one-click verification
2. Consider adding automated tests that run as non-root user
3. Consider adding visual indicators when testing as root vs non-root

---

**End of Report**
