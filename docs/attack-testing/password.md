# Password Protection Attack Testing Guide

## Attack Overview

**Description:** Password Protection vulnerabilities occur when an application uses weak password hashing algorithms, insufficient work factors, or lacks proper password policies. Weak password hashing can lead to credential exposure through database breaches or rainbow table attacks.

**Learning Objectives:**
- Understand the importance of strong password hashing
- Learn the difference between bcrypt and weak hashing algorithms
- Practice identifying password hashing vulnerabilities
- Learn remediation techniques using modern hashing methods

**Risk Level:** HIGH
- Can lead to credential exposure
- Allows offline password cracking
- May enable account takeover
- Can result in credential stuffing attacks

**OWASP Mapping:** A02:2021 - Cryptographic Failures (formerly A3:2017 - Sensitive Data Exposure)
- CWE-256: Unprotected Storage of Credentials
- CWE-916: Use of Password Hash With Insufficient Computational Effort

## Files Involved

```
app/security/auth.php
app/security/functions.php
admin/security-settings.php
app/config/config.php
```

**Key Functions:**
- `hashPassword()` in `app/security/functions.php` (lines 46-48)
- `verifyPassword()` in `app/security/functions.php` (lines 53-55)
- `registerStudent()` in `app/security/auth.php` (lines 135-215)
- `registerTeacher()` in `app/security/auth.php` (lines 220-290)
- `registerAdmin()` in `app/security/auth.php` (lines 295-357)

## Toggle Information

```
Protection Name:
Password Protection

Database Key:
password_protection_enabled

Environment Variable:
PASSWORD_PROTECTION_ENABLED

Default State:
N/A (Toggle does not exist)

UI Location:
/admin/security-settings.php
Checkbox: NOT IMPLEMENTED
```

**Toggle Behavior:**
- **NOT IMPLEMENTED** - No toggle currently exists for password protection
- Current implementation always uses bcrypt (PASSWORD_DEFAULT)
- No option to demonstrate weak password hashing

## Current Implementation Status

### How Passwords Are Currently Handled

**Registration Functions:**
```php
// app/security/auth.php line 172
$hashedPassword = hashPassword($data['password']);

// app/security/functions.php line 46
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
```

**Verification Functions:**
```php
// app/security/auth.php line 40
if (!verifyPassword($password, $user['password'])) {
    return ['success' => false, 'message' => 'Invalid email or password'];
}

// app/security/functions.php line 53
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
```

**Current Behavior:**
- All passwords are hashed using bcrypt (PASSWORD_DEFAULT)
- Password verification always uses password_verify()
- No option to demonstrate weak hashing (MD5, SHA1)
- No toggle exists to control this behavior

## Secure Mode Verification

### Current State

Since no toggle exists, the application is **ALWAYS** in secure mode for password hashing.

### Verification Steps

#### Test 1: Check Password Hash

**Page to Visit:** Database query or admin panel

**Input to Test:**
```sql
SELECT password FROM users WHERE email = 'admin@myeduconnect.com';
```

**Expected Secure Behavior:**
- Password is hashed using bcrypt
- Hash starts with `$2y$` (bcrypt identifier)
- Hash is 60 characters long
- Plain text password is not stored

**Expected Logs:**
- No plain text passwords in database
- All passwords use bcrypt format

#### Test 2: Password Verification

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com
Password: Admin123!
```

**Expected Secure Behavior:**
- Login succeeds with correct password
- Password is verified using password_verify()
- Bcrypt hash comparison is secure
- No timing attack vulnerabilities

**Expected Logs:**
- Successful login logged in audit_logs table
- Secure password verification occurred

## Vulnerable Mode Verification

### Current State

**NOT POSSIBLE** - No toggle exists to enable weak password hashing. The application cannot demonstrate password hashing vulnerabilities.

### Recommended Implementation

To enable password vulnerability demonstrations, the following implementation is required:

#### Step 1: Add Toggle to Security Settings

**File:** `admin/security-settings.php`

Add checkbox:
```php
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="password_protection_enabled" id="password_protection_enabled" <?= $settings['password_protection_enabled'] ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold" for="password_protection_enabled">
        <i class="bi bi-key"></i> Password Protection
    </label>
</div>
<small class="text-muted">
    When OFF: Uses weak hashing (MD5/SHA1)<br>
    When ON: Uses bcrypt
</small>
```

#### Step 2: Add Environment Variable

**File:** `app/config/config.php`

Add constant:
```php
define('PASSWORD_PROTECTION_ENABLED', filter_var($_ENV['PASSWORD_PROTECTION_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
```

#### Step 3: Add Mapping

**File:** `app/security/functions.php`

Add to mapping array:
```php
$mapping = [
    'sqli_enabled' => SQLI_ENABLED,
    'xss_enabled' => XSS_ENABLED,
    'idor_enabled' => IDOR_ENABLED,
    'upload_enabled' => UPLOAD_ENABLED,
    'weak_auth_enabled' => WEAK_AUTH_ENABLED,
    'csrf_enabled' => CSRF_ENABLED,
    'password_protection_enabled' => PASSWORD_PROTECTION_ENABLED
];
```

#### Step 4: Modify hashPassword Function

**File:** `app/security/functions.php`

Update function:
```php
function hashPassword($password) {
    if (isProtectionEnabled('password_protection_enabled')) {
        // SECURE - Use bcrypt
        return password_hash($password, PASSWORD_DEFAULT);
    } else {
        // VULNERABLE - Use weak hashing (MD5)
        return md5($password);
    }
}
```

#### Step 5: Modify verifyPassword Function

**File:** `app/security/functions.php`

Update function:
```php
function verifyPassword($password, $hash) {
    if (isProtectionEnabled('password_protection_enabled')) {
        // SECURE - Use bcrypt verification
        return password_verify($password, $hash);
    } else {
        // VULNERABLE - Use MD5 comparison
        return md5($password) === $hash;
    }
}
```

### Verification Steps (After Implementation)

#### Test 1: Weak Password Hash

**Prerequisites:**
- Password Protection: **UNCHECKED**

**Page to Visit:** Registration page

**Input to Test:**
```php
Email: testuser@example.com
Password: TestPassword123!
```

**Expected Vulnerable Behavior:**
- Password is hashed using MD5
- Hash is 32 characters long
- Hash format: `5f4dcc3b5aa765d61d8327deb882cf99`
- Weak hashing is vulnerable to rainbow table attacks

**Expected Logs:**
- User registration succeeds
- MD5 hash stored in database

#### Test 2: Weak Password Verification

**Prerequisites:**
- Password Protection: **UNCHECKED**

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: testuser@example.com
Password: TestPassword123!
```

**Expected Vulnerable Behavior:**
- Login succeeds
- Password verified using MD5 comparison
- Weak verification is vulnerable to timing attacks

**Expected Logs:**
- Successful login logged
- Weak password verification occurred

## Dependency Analysis

```
Can be demonstrated independently: N/A

Required secondary vulnerability:
N/A (Toggle does not exist)

Reason:
Password protection toggle does not exist in the current implementation. All passwords are always hashed using bcrypt, and there is no way to demonstrate weak password hashing vulnerabilities. This is a design flaw that prevents educational demonstration of password security concepts.

Implementation Status:
❌ NOT IMPLEMENTED - Toggle does not exist
```

## Code Flow (Current Implementation)

```
User Registration
    ↓
app/security/auth.php: registerStudent/registerTeacher/registerAdmin()
    ↓
hashPassword($password)
    ↓
app/security/functions.php: hashPassword()
    ↓
password_hash($password, PASSWORD_DEFAULT)
    ↓
bcrypt hashing (always)
    ↓
Store in database
```

## Code Flow (Recommended Implementation)

```
Security Settings Page
    ↓
POST: Update password_protection_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define PASSWORD_PROTECTION_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('password_protection_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check PASSWORD_PROTECTION_ENABLED constant
    ↓
┌─────────────────────────────┐
│  PASSWORD_PROTECTION_ENABLED?│
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
hashPassword() / verifyPassword()
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → bcrypt        │
│         NO → MD5 (weak)     │
└─────────────────────────────┘
    ↓
Secure: password_hash() with bcrypt
Vulnerable: md5() (weak hashing)
```

## Expected Results

### Secure Mode Screenshots (Current State)

```
[SCREENSHOT: Database showing bcrypt password hashes]

[SCREENSHOT: Registration form with secure password handling]

[SCREENSHOT: Login with secure password verification]
```

### Vulnerable Mode Screenshots (After Implementation)

```
[SCREENSHOT: Security Settings with Password Protection UNCHECKED]

[SCREENSHOT: Database showing MD5 password hashes]

[SCREENSHOT: Registration form with weak password handling]
```

## Instructor Notes

### Common Mistakes

1. **Assuming toggle exists**
   - Students may look for password protection toggle
   - Explain that it's not currently implemented
   - This is a known design flaw
   - Document the recommended implementation

2. **Not understanding bcrypt**
   - Students may not know why bcrypt is secure
   - Explain work factor and salt
   - Compare with MD5/SHA1
   - Demonstrate rainbow table attacks

3. **Testing with weak passwords**
   - Even with bcrypt, weak passwords are vulnerable
   - Emphasize that hashing doesn't replace strong passwords
   - Discuss password policies
   - Explain brute force attacks

### Demo Tips

1. **Explain Current Implementation**
   - Show that bcrypt is always used
   - Explain why this is secure
   - Discuss the limitation for educational purposes
   - Recommend implementing the toggle

2. **Demonstrate Bcrypt Security**
   - Show bcrypt hash format
   - Explain work factor
   - Discuss salt generation
   - Compare with MD5/SHA1

3. **Use Password Cracking Tools**
   - Demonstrate John the Ripper
   - Show hashcat capabilities
   - Compare bcrypt vs MD5 cracking times
   - Emphasize computational cost

4. **Discuss Password Policies**
   - Minimum length requirements
   - Complexity requirements
   - Password rotation policies
   - Multi-factor authentication

### Reset Steps

After implementing and testing password protection:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "Password Protection"
   - Save settings

2. **Verify Secure State**
   - Register new user
   - Check database for bcrypt hash
   - Verify hash format
   - Check Current Status shows "Protected"

3. **Reset Database (if needed)**
   ```bash
   mysql -u root -p myeduconnect < database/init.sql
   ```

### Cleanup Steps

1. **Clear Test Users**
   - Remove users with weak hashes
   - Delete test accounts
   - Ensure clean state

2. **Review Database**
   - Check users table for MD5 hashes
   - Remove any weak hashes
   - Ensure all passwords use bcrypt

3. **Reset Passwords**
   - Force password reset for affected users
   - Send password reset emails
   - Ensure secure state

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **Rainbow Table Attacks**
   - How rainbow tables work
   - MD5 vs bcrypt resistance
   - Salt importance
   - Rainbow table generation

2. **Password Cracking**
   - John the Ripper usage
   - Hashcat configuration
   - GPU acceleration
   - Cracking time comparison

3. **Password Policy Enforcement**
   - Complexity requirements
   - Common password blacklists
   - Password strength meters
   - Breached password checking

4. **Remediation Techniques**
   - Bcrypt with appropriate work factor
   - Argon2 (modern alternative)
   - Password salting
   - Key derivation functions (PBKDF2)
   - Multi-factor authentication

### Assessment Questions

Test student understanding with:

1. Why is bcrypt preferred over MD5 for password hashing?
2. What is the purpose of salt in password hashing?
3. How does work factor affect password security?
4. Why should you never store passwords in plain text?
5. What are the limitations of password hashing alone?

### Implementation Priority

**HIGH PRIORITY** - Implement password protection toggle to enable educational demonstrations of:
- Weak vs strong password hashing
- Rainbow table attacks
- Password cracking techniques
- Remediation strategies

This is essential for comprehensive security training.

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+  
**Implementation Status:** NOT IMPLEMENTED - Toggle does not exist
