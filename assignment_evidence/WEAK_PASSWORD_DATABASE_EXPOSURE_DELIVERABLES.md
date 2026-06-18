# Weak Password Storage + Database Exposure - Assignment Evidence Checklist

## Deliverables Summary

### Files Analyzed
- `login.php` - Login page authentication flow
- `register.php` - User registration flow
- `admin/create-user.php` - Admin user creation flow
- `app/security/auth.php` - Authentication functions (login, register, password verification)
- `app/security/functions.php` - Security functions including password hashing and vulnerability toggles
- `app/config/config.php` - Application configuration
- `database/schema.sql` - Database schema definition
- `database/init.sql` - Database initialization script
- `admin/users.php` - User management page
- `admin/security-settings.php` - Security vulnerability toggle interface
- `assignment_evidence/BACKUP_FILE_EXPOSURE.md` - Existing backup exposure documentation

### Files Modified
- `app/security/functions.php` - Modified `hashPassword()` to store plaintext when vulnerable, modified `verifyPassword()` to support plaintext, added `syncDatabaseExposure()` function, updated vulnerability description, added side effect handler
- `app/security/auth.php` - Modified login function to migrate plaintext passwords to bcrypt
- `database/schema.sql` - Updated security_settings description for weak_password_hashing
- `database/init.sql` - Updated security_settings description for weak_password_hashing
- `admin/security-settings.php` - Added database exposure URL to lab notes

### Files Created
- `assignment_evidence/WEAK_PASSWORD_DATABASE_EXPOSURE.md` - Comprehensive README documentation
- `assignment_evidence/WEAK_PASSWORD_DATABASE_EXPOSURE_TESTING_GUIDE.md` - Detailed testing guide for Windows + Docker Desktop
- `admin/user-database-exposure.php` - Dynamically created exposure endpoint (created/removed by toggle)

---

## Password Storage Changes

### Modified Function: `hashPassword()`

**Location:** `app/security/functions.php` (lines 103-110)

**Change:** Modified to store plaintext when `weak_password_hashing` vulnerability is enabled

**Before:**
```php
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        return md5($password);  // MD5 hashing
    }
    return password_hash($password, PASSWORD_BCRYPT);
}
```

**After:**
```php
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        return $password;  // Plaintext storage
    }
    return password_hash($password, PASSWORD_BCRYPT);
}
```

### Modified Function: `verifyPassword()`

**Location:** `app/security/functions.php` (lines 115-127)

**Change:** Added support for plaintext password verification

**Before:**
```php
function verifyPassword($password, $hash) {
    if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        return md5($password) === $hash;
    }
    return password_verify($password, $hash);
}
```

**After:**
```php
function verifyPassword($password, $hash) {
    if ($password === $hash) {
        return true;  // Plaintext match
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        return md5($password) === $hash;
    }
    return password_verify($password, $hash);
}
```

### Modified Function: `login()`

**Location:** `app/security/auth.php` (lines 13-76)

**Change:** Updated password migration logic to include plaintext passwords

**Before:**
```php
if (preg_match('/^[a-f0-9]{32}$/i', $user['password'])) {
    dbUpdate(
        "UPDATE users SET password = ? WHERE user_id = ?",
        [password_hash($password, PASSWORD_BCRYPT), $user['user_id']]
    );
}
```

**After:**
```php
if (preg_match('/^[a-f0-9]{32}$/i', $user['password']) || $password === $user['password']) {
    dbUpdate(
        "UPDATE users SET password = ? WHERE user_id = ?",
        [password_hash($password, PASSWORD_BCRYPT), $user['user_id']]
    );
}
```

---

## Database Exposure Implementation Details

### New Function: `syncDatabaseExposure()`

**Location:** `app/security/functions.php` (lines 677-752)

**Purpose:** Creates or removes the database exposure endpoint based on vulnerability toggle state

**Behavior:**
- When enabled: Creates `admin/user-database-exposure.php` with code to display all user data including passwords
- When disabled: Removes `admin/user-database-exposure.php` to prevent access

**Exposure Endpoint Features:**
- Displays user_id, email, password, first_name, last_name, role, status
- Passwords shown in plaintext when vulnerability is enabled
- Returns 403 Forbidden when vulnerability is disabled
- No authentication required (demonstrates exposure vulnerability)

### Exposure Endpoint Content

**File:** `admin/user-database-exposure.php` (dynamically created)

**Key Features:**
- Checks if vulnerability is enabled before displaying data
- Queries all users from database
- Displays passwords in red warning color
- Shows vulnerability warning banner
- Simple HTML table format for easy viewing

---

## Security Toggle Integration Details

### Updated Vulnerability Description

**Location:** `app/security/functions.php` (line 40)

**Change:** Updated description to reflect plaintext storage

**Before:**
```php
'weak_password_hashing' => 'Uses MD5 hashing for passwords instead of bcrypt.',
```

**After:**
```php
'weak_password_hashing' => 'Stores passwords in plaintext instead of using bcrypt hashing.',
```

### Added Side Effect Handler

**Location:** `app/security/functions.php` (lines 645-647)

**Change:** Added weak_password_hashing to applyVulnerabilitySideEffects()

**Added:**
```php
} elseif ($name === 'weak_password_hashing') {
    syncDatabaseExposure($enabled);
}
```

### Added Initialization Call

**Location:** `app/security/functions.php` (lines 55-56)

**Change:** Added initialization call for weak_password_hashing side effects

**Added:**
```php
$weakPassword = dbSelectOne("SELECT enabled FROM security_settings WHERE vulnerability_name = 'weak_password_hashing' LIMIT 1");
applyVulnerabilitySideEffects('weak_password_hashing', $weakPassword ? ((int)$weakPassword['enabled'] === 1) : false);
```

### Updated Admin UI

**Location:** `admin/security-settings.php` (lines 194-199)

**Change:** Added database exposure URL to lab notes

**Added:**
```php
<div class="col-md-12">
    <div class="d-flex">
        <strong class="me-2" style="min-width: 200px;">Database Exposure URL (enabled mode):</strong>
        <code class="flex-grow-1"><?php echo APP_URL; ?>/admin/user-database-exposure.php</code>
    </div>
</div>
```

---

## Validation Results

### Security OFF Validation

**Expected Behavior:**
- Passwords stored in plaintext in database
- Database exposure endpoint accessible
- Passwords visible on exposure page
- No authentication required for exposure page

**Validation Method:**
1. Enable weak_password_hashing toggle
2. Register new user
3. Query database to verify plaintext storage
4. Access exposure endpoint to verify visibility
5. Confirm all credentials are exposed

**Expected Database Record:**
```sql
user_id | email                    | password      | first_name | last_name | role
--------|--------------------------|---------------|------------|-----------|--------
7       | teststudent@example.com | TestPassword123 | Test      | Student   | student
```

**Expected Exposure Page Output:**
- Table showing all users
- Password column displays plaintext passwords
- Warning banner visible
- No authentication required

### Security ON Validation

**Expected Behavior:**
- Passwords stored as bcrypt hashes
- Database exposure endpoint returns 403/404
- Passwords not visible
- Credentials protected

**Validation Method:**
1. Disable weak_password_hashing toggle
2. Register new user
3. Query database to verify bcrypt hash
4. Access exposure endpoint to verify access denied
5. Confirm credentials are protected

**Expected Database Record:**
```sql
user_id | email                    | password                              | first_name | last_name | role
--------|--------------------------|---------------------------------------|------------|-----------|--------
8       | securestudent@example.com| $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKo... | Secure     | Student   | student
```

**Expected Exposure Page Output:**
- 403 Forbidden or 404 Not Found
- No user data displayed
- Access denied message

### Regression Testing

**Verified Features:**
- SQL Injection vulnerability still functions when enabled
- Stored XSS vulnerability still functions when enabled
- IDOR vulnerability still functions when enabled
- Backup File Exposure vulnerability still functions when enabled
- Normal user registration works correctly
- Normal user login works correctly
- Normal profile updates work correctly
- No breaking changes introduced

---

## Required Screenshots

### Screenshot 1: Security Toggle OFF
**File:** `screenshot_01_security_toggle_off.png`
**Content:** Security Settings page with Weak Password Hashing toggle enabled
**URL:** http://localhost:8080/admin/security-settings.php

### Screenshot 2: User Registration (OFF)
**File:** `screenshot_02_registration_off.png`
**Content:** Registration form filled with test data (Test Student, teststudent@example.com, TestPassword123)
**URL:** http://localhost:8080/register.php

### Screenshot 3: Registration Success (OFF)
**File:** `screenshot_03_registration_success_off.png`
**Content:** Success message after registration
**URL:** http://localhost:8080/register.php

### Screenshot 4: Database Query (OFF)
**File:** `screenshot_04_database_plaintext.png`
**Content:** Database query result showing plaintext password "TestPassword123"
**Command:** `SELECT user_id, email, password, first_name, last_name FROM users WHERE email = 'teststudent@example.com';`

### Screenshot 5: Database Exposure Page
**File:** `screenshot_05_exposure_page.png`
**Content:** Database exposure page showing all user passwords in plaintext
**URL:** http://localhost:8080/admin/user-database-exposure.php

### Screenshot 6: Security Toggle ON
**File:** `screenshot_06_security_toggle_on.png`
**Content:** Security Settings page with Weak Password Hashing toggle disabled
**URL:** http://localhost:8080/admin/security-settings.php

### Screenshot 7: User Registration (ON)
**File:** `screenshot_07_registration_on.png`
**Content:** Registration form filled with secure test data (Secure Student, securestudent@example.com, SecurePassword123)
**URL:** http://localhost:8080/register.php

### Screenshot 8: Registration Success (ON)
**File:** `screenshot_08_registration_success_on.png`
**Content:** Success message after registration
**URL:** http://localhost:8080/register.php

### Screenshot 9: Database Query (ON)
**File:** `screenshot_09_database_hash.png`
**Content:** Database query result showing bcrypt hash (starts with $2y$10$)
**Command:** `SELECT user_id, email, password, first_name, last_name FROM users WHERE email = 'securestudent@example.com';`

### Screenshot 10: Access Denied
**File:** `screenshot_10_access_denied.png`
**Content:** 403 Forbidden or 404 Not Found message when accessing exposure page
**URL:** http://localhost:8080/admin/user-database-exposure.php

### Screenshot 11: Before/After Comparison
**File:** `screenshot_11_comparison.png`
**Content:** Side-by-side comparison showing plaintext password vs bcrypt hash
**Format:** Composite image or two screenshots side by side

---

## Rollback Instructions

### To Revert Changes

1. **Restore original hashPassword() function:**
   ```php
   function hashPassword($password) {
       if (isVulnerabilityEnabled('weak_password_hashing')) {
           return md5($password);
       }
       return password_hash($password, PASSWORD_BCRYPT);
   }
   ```

2. **Restore original verifyPassword() function:**
   ```php
   function verifyPassword($password, $hash) {
       if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
           return md5($password) === $hash;
       }
       return password_verify($password, $hash);
   }
   ```

3. **Remove syncDatabaseExposure() function** from `app/security/functions.php`

4. **Remove side effect handler** from `applyVulnerabilitySideEffects()` function

5. **Remove initialization call** from `ensureSecuritySettingsSeeded()` function

6. **Restore original vulnerability description** in `app/security/functions.php`:
   ```php
   'weak_password_hashing' => 'Uses MD5 hashing for passwords instead of bcrypt.',
   ```

7. **Restore original login() function** in `app/security/auth.php`

8. **Remove database exposure URL** from `admin/security-settings.php`

9. **Restore database schema** in `database/schema.sql` and `database/init.sql`

10. **Delete exposure endpoint** if it exists:
    ```powershell
    docker compose exec app rm /var/www/html/admin/user-database-exposure.php
    ```

11. **Delete documentation files:**
    - `assignment_evidence/WEAK_PASSWORD_DATABASE_EXPOSURE.md`
    - `assignment_evidence/WEAK_PASSWORD_DATABASE_EXPOSURE_TESTING_GUIDE.md`
    - `assignment_evidence/WEAK_PASSWORD_DATABASE_EXPOSURE_DELIVERABLES.md`

---

## Summary

### Implementation Complete

- **Password Storage:** Modified to use plaintext when vulnerable, bcrypt when secure
- **Password Verification:** Updated to support plaintext, MD5, and bcrypt
- **Database Exposure:** Created dynamic endpoint that exposes user credentials when vulnerable
- **Security Toggle:** Integrated with existing vulnerability framework
- **Side Effects:** Added automatic creation/removal of exposure endpoint
- **Documentation:** Comprehensive README, testing guide, and deliverables checklist

### Changes Isolated

- No modifications to SQL Injection vulnerability
- No modifications to XSS vulnerability
- No modifications to IDOR vulnerability
- No modifications to Backup File Exposure vulnerability
- No modifications to SSH vulnerabilities
- No modifications to existing business logic
- No modifications to existing APIs
- No modifications to existing routing
- No modifications to existing UI components (except security-settings.php lab notes)

### Testing Ready

- Detailed testing guide provided for Windows + Docker Desktop
- Step-by-step instructions for Security OFF validation
- Step-by-step instructions for Security ON validation
- Regression testing checklist included
- Troubleshooting section included
- Screenshot checklist with 11 required screenshots

### Assignment Evidence

- README documentation explaining vulnerability
- Testing guide with exact commands and URLs
- Deliverables checklist with all changes documented
- Rollback instructions provided
- All files analyzed, modified, and created listed
