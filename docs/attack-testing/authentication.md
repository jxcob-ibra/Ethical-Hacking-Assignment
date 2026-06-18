# Authentication (Weak Authentication) Attack Testing Guide

## Attack Overview

**Description:** Weak Authentication vulnerabilities occur when an application fails to properly verify user credentials or implements weak authentication mechanisms. This can include missing password verification, weak password hashing, predictable session tokens, or lack of multi-factor authentication.

**Learning Objectives:**
- Understand the importance of strong password verification
- Learn how weak authentication leads to credential bypass
- Practice identifying authentication vulnerabilities
- Learn remediation techniques using bcrypt and proper verification

**Risk Level:** CRITICAL
- Can lead to unauthorized access
- Allows account takeover
- May enable privilege escalation
- Can result in complete system compromise

**OWASP Mapping:** A07:2021 - Identification and Authentication Failures (formerly A2:2017 - Broken Authentication)
- CWE-287: Improper Authentication
- CAPEC-94: Identity Spoofing

## Files Involved

```
app/security/auth.php
app/security/functions.php
admin/security-settings.php
app/config/config.php
```

**Key Functions:**
- `login()` in `app/security/auth.php` (lines 13-109)
- `verifyPassword()` in `app/security/functions.php` (lines 53-55)
- `hashPassword()` in `app/security/functions.php` (lines 46-48)

## Toggle Information

```
Protection Name:
Authentication Protection

Database Key:
weak_auth_enabled

Environment Variable:
WEAK_AUTH_ENABLED

Default State:
Enabled (true)

UI Location:
/admin/security-settings.php
Checkbox: "Authentication Protection"
```

**Toggle Behavior:**
- **Checked (ON):** Protection enabled, verifies password with bcrypt
- **Unchecked (OFF):** Protection disabled, skips password verification

## Secure Mode Verification

### How to Enable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "Authentication Protection" checkbox is **CHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Protected" in Current Status section

### Verification Steps

#### Test 1: Login with Correct Credentials

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com
Password: Admin123!
```

**Expected Secure Behavior:**
- Login succeeds
- Password is verified using bcrypt
- Session is established
- User redirected to appropriate dashboard

**Expected Logs:**
- Successful login logged in audit_logs table
- Last login timestamp updated in users table

#### Test 2: Login with Incorrect Password

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com
Password: WrongPassword123!
```

**Expected Secure Behavior:**
- Login fails
- Error message: "Invalid email or password"
- Password verification fails
- No session established

**Expected Logs:**
- Failed login attempt logged in audit_logs table
- No session created

### Code Verification

Check that the following code path is active:

```php
// app/security/auth.php line 38
if (isProtectionEnabled('weak_auth_enabled')) {
    // SECURE - Verify password with bcrypt
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}
```

## Vulnerable Mode Verification

### How to Disable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "Authentication Protection" checkbox is **UNCHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Vulnerable" in Current Status section

**⚠️ WARNING:** Disabling Authentication protection also has a dependency on SQL Injection protection due to a design flaw (see Dependency Analysis below).

### Verification Steps

#### Test 1: Login with Any Password (with SQLi Disabled)

**Prerequisites:**
- Authentication Protection: **UNCHECKED**
- SQL Injection Protection: **UNCHECKED**

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com
Password: anypassword
```

**Expected Vulnerable Behavior:**
- Login succeeds with ANY password
- Password verification is skipped
- Session is established
- User redirected to dashboard

**Expected Logs:**
- Successful login logged in audit_logs table
- Last login timestamp updated
- No password verification occurred

#### Test 2: Login with Any Password (with SQLi Enabled)

**Prerequisites:**
- Authentication Protection: **UNCHECKED**
- SQL Injection Protection: **CHECKED**

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com
Password: anypassword
```

**Expected Vulnerable Behavior:**
- Login fails (due to design flaw)
- Password verification still occurs even though Weak Auth is disabled
- This is the opposite of expected behavior

**Expected Logs:**
- Failed login attempt logged
- This behavior is incorrect and needs fixing

### Code Verification

Check that the following code path is active:

```php
// app/security/auth.php line 44
} else {
    // VULNERABLE - Skip password verification or use weak hashing
    // For demonstration, we'll accept any password when SQLi is also disabled
    // This allows login bypass attacks
    if (!isVulnerabilityEnabled('sqli_enabled')) {
        // Password verification skipped for SQLi demo
    } else {
        // Still verify password if SQLi is protected
        if (!verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
    }
}
```

## Dependency Analysis

```
Can be demonstrated independently: NO

Required secondary vulnerability:
SQL Injection (sqli_enabled)

Reason:
Lines 47-54 in app/security/auth.php create a coupling between Weak Authentication and SQL Injection toggles. When Weak Auth is disabled, password verification is only skipped if SQLi is also disabled. This creates a chained attack scenario where demonstrating weak authentication bypass requires disabling both protections.

Current Implementation (FLAWED):
if (!isVulnerabilityEnabled('sqli_enabled')) {
    // Password verification skipped for SQLi demo
} else {
    // Still verify password if SQLi is protected
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}

This means:
- Weak Auth disabled + SQLi disabled = Password verification skipped (chained attack)
- Weak Auth disabled + SQLi enabled = Password verification performed (incorrect behavior)

Impact:
Instructors cannot demonstrate weak authentication in isolation without also affecting SQL injection behavior. This violates the requirement that each vulnerability should be independently toggleable.

Recommended Fix:
Remove the dependency on sqli_enabled in password verification logic. Password verification should be controlled solely by the weak_auth_enabled toggle.
```

## Code Flow

```
Security Settings Page
    ↓
POST: Update weak_auth_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define WEAK_AUTH_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('weak_auth_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check WEAK_AUTH_ENABLED constant
    ↓
┌─────────────────────────────┐
│  WEAK_AUTH_ENABLED === true?│
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
app/security/auth.php: login()
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → verifyPassword()│
│         NO → skip verification│
└─────────────────────────────┘
    ↓
┌─────────────────────────────┐
│  FLAWED: Check SQLi state   │
│  if (!isVulnerabilityEnabled('sqli_enabled'))│
│         → Skip verification  │
│  else                        │
│         → Verify password    │
└─────────────────────────────┘
    ↓
Secure: verifyPassword() with bcrypt
Vulnerable: Skip verification (if SQLi also disabled)
```

## Expected Results

### Secure Mode Screenshots

```
[SCREENSHOT: Security Settings with Authentication Protection CHECKED]

[SCREENSHOT: Successful login with correct credentials]

[SCREENSHOT: Failed login with incorrect password]
```

### Vulnerable Mode Screenshots

```
[SCREENSHOT: Security Settings with Authentication Protection UNCHECKED]

[SCREENSHOT: Successful login with ANY password (when SQLi also disabled)]

[SCREENSHOT: Failed login with wrong password (when SQLi enabled - FLAWED BEHAVIOR)]
```

## Instructor Notes

### Common Mistakes

1. **Not disabling SQL Injection**
   - Students may fail to bypass authentication even with Weak Auth disabled
   - Remind them that password verification still occurs if SQLi is enabled
   - This is a known design flaw that needs to be explained
   - Both toggles must be disabled for full demonstration

2. **Testing with non-existent accounts**
   - Weak auth bypass only works with existing email addresses
   - Students may try random emails and think it's not working
   - Ensure they use valid demo accounts
   - Explain that email must exist in database

3. **Forgetting to reset between tests**
   - Previous toggle states may affect current test results
   - Always verify current toggle state before testing
   - Reset to secure mode after demonstration

### Demo Tips

1. **Start with Secure Mode**
   - Show that bcrypt verification works correctly
   - Demonstrate that incorrect passwords are rejected
   - Build confidence in secure implementation

2. **Explain the Dependency Flaw**
   - Clearly document the Weak Auth ↔ SQLi coupling
   - Explain why this is a design flaw
   - Discuss how to fix it for independent testing
   - This is a teaching moment about dependency management

3. **Use Safe Test Credentials**
   - Use demo accounts only
   - Never use real credentials
   - Emphasize that real attacks can be much more damaging

4. **Demonstrate Password Hashing**
   - Show how bcrypt works
   - Explain why bcrypt is secure
   - Compare with weak hashing (MD5, SHA1)
   - Discuss salt and work factor

### Reset Steps

After demonstrating weak authentication:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "Authentication Protection"
   - Check "SQL Injection Protection"
   - Save settings

2. **Verify Secure State**
   - Test login with correct credentials
   - Ensure incorrect passwords are rejected
   - Check Current Status shows "Protected"

3. **Reset Database (if needed)**
   ```bash
   mysql -u root -p myeduconnect < database/init.sql
   ```

### Cleanup Steps

1. **Clear Session Data**
   - Logout all demo accounts
   - Clear browser cookies and sessions

2. **Review Audit Logs**
   - Check `audit_logs` table for test entries
   - Document findings for educational records
   - Remove test entries if needed

3. **Reset Failed Attempts**
   - Clear any account lockouts
   - Reset login attempt counters
   - Ensure accounts are not locked

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **Password Hashing Algorithms**
   - Compare bcrypt vs MD5 vs SHA1
   - Show rainbow table attacks
   - Demonstrate salt importance
   - Explain work factor

2. **Multi-Factor Authentication**
   - How MFA prevents credential bypass
   - TOTP implementation
   - SMS-based 2FA
   - Hardware tokens

3. **Session Management**
   - Secure session generation
   - Session fixation prevention
   - Session timeout configuration
   - Secure cookie flags

4. **Remediation Techniques**
   - Strong password policies
   - Account lockout mechanisms
   - Password hashing best practices
   - Multi-factor authentication
   - Brute force protection

### Assessment Questions

Test student understanding with:

1. Why is bcrypt preferred over MD5 for password hashing?
2. What is the purpose of salt in password hashing?
3. How does the dependency between Weak Auth and SQLi violate independent testing?
4. What are the limitations of password complexity requirements?
5. Why should you never store passwords in plain text?

### Additional Testing Scenarios

#### Scenario 1: Password Bypass with Both Toggles Disabled

1. Disable Authentication Protection
2. Disable SQL Injection Protection
3. Navigate to login page
4. Enter valid email with ANY password
5. Observe successful login bypass

#### Scenario 2: Password Verification with SQLi Enabled (Flawed Behavior)

1. Disable Authentication Protection
2. Enable SQL Injection Protection
3. Navigate to login page
4. Enter valid email with wrong password
5. Observe login fails (incorrect behavior due to flaw)

#### Scenario 3: Secure Mode Verification

1. Enable Authentication Protection
2. Enable SQL Injection Protection
3. Test login with correct password → Success
4. Test login with wrong password → Failure
5. Verify secure behavior

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+
