# SQL Injection Attack Testing Guide

## Attack Overview

**Description:** SQL Injection is a code injection technique that exploits vulnerabilities in an application's software by inserting malicious SQL statements into an entry field. This attack can allow attackers to view, modify, or delete data in the database, bypass authentication, or execute administrative operations.

**Learning Objectives:**
- Understand how SQL injection vulnerabilities occur
- Learn the difference between parameterized queries and string concatenation
- Practice identifying and exploiting SQL injection points
- Learn remediation techniques using prepared statements

**Risk Level:** CRITICAL
- Can lead to complete database compromise
- Allows unauthorized data access
- Can result in data deletion or modification
- May enable authentication bypass

**OWASP Mapping:** A01:2021 - Broken Access Control (formerly A1:2017 - Injection)
- CWE-89: SQL Injection
- CAPEC-66: SQL Injection

## Files Involved

```
app/security/auth.php
app/security/functions.php
app/config/database.php
admin/security-settings.php
app/config/config.php
```

**Key Functions:**
- `login()` in `app/security/auth.php` (lines 13-109)
- `searchCourses()` in `app/security/functions.php` (lines 439-483)
- `isProtectionEnabled()` in `app/security/functions.php` (lines 512-545)

## Toggle Information

```
Protection Name:
SQL Injection Protection

Database Key:
sqli_enabled

Environment Variable:
SQLI_ENABLED

Default State:
Enabled (true)

UI Location:
/admin/security-settings.php
Checkbox: "SQL Injection Protection"
```

**Toggle Behavior:**
- **Checked (ON):** Protection enabled, uses parameterized queries
- **Unchecked (OFF):** Protection disabled, uses vulnerable string concatenation

## Secure Mode Verification

### How to Enable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "SQL Injection Protection" checkbox is **CHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Protected" in Current Status section

### Verification Steps

#### Test 1: Login Function

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com' OR '1'='1
Password: anypassword
```

**Expected Secure Behavior:**
- Login fails with "Invalid email or password" message
- No authentication bypass occurs
- Query uses parameterized statement: `WHERE email = ?`

**Expected Logs:**
- Failed login attempt logged in audit_logs table
- No SQL errors in application logs

#### Test 2: Search Function

**Page to Visit:** `/courses.php`

**Input to Test:**
```php
Search: test' OR '1'='1
```

**Expected Secure Behavior:**
- Search returns no results or literal string match
- No SQL syntax errors
- Query uses parameterized statement: `WHERE c.title LIKE ?`

**Expected Logs:**
- Normal search query executed
- No SQL errors in application logs

### Code Verification

Check that the following code path is active:

```php
// app/security/auth.php line 15
if (isProtectionEnabled('sqli_enabled')) {
    // SECURE VERSION - Use prepared statements
    $query = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $user = dbSelectOne($query, [$email]);
}
```

## Vulnerable Mode Verification

### How to Disable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "SQL Injection Protection" checkbox is **UNCHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Vulnerable" in Current Status section

**⚠️ WARNING:** Disabling SQL Injection protection also affects password verification behavior due to a design flaw (see Dependency Analysis below).

### Verification Steps

#### Test 1: Login Function (with Weak Auth Disabled)

**Prerequisites:**
- SQL Injection Protection: **UNCHECKED**
- Authentication Protection: **UNCHECKED**

**Page to Visit:** `/login.php`

**Input to Test:**
```php
Email: admin@myeduconnect.com' OR '1'='1
Password: anypassword
```

**Expected Vulnerable Behavior:**
- Login succeeds with ANY password
- Authentication bypass occurs
- Query uses string concatenation: `WHERE email = '$email'`

**Expected Logs:**
- Successful login logged in audit_logs table
- User logged in as administrator

#### Test 2: Search Function

**Page to Visit:** `/courses.php`

**Input to Test:**
```php
Search: test' OR '1'='1
```

**Expected Vulnerable Behavior:**
- SQL syntax error or unexpected results
- Query uses string concatenation: `WHERE c.title = '$keyword'`

**Expected Logs:**
- SQL error in application logs
- Potential database error message displayed

### Code Verification

Check that the following code path is active:

```php
// app/security/auth.php line 56
} else {
    // VULNERABLE VERSION - Raw SQL concatenation
    $query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
    $user = dbSelectOne($query);
}
```

## Dependency Analysis

```
Can be demonstrated independently: NO

Required secondary vulnerability:
Weak Authentication (weak_auth_enabled)

Reason:
Lines 47-54 in app/security/auth.php create a coupling between SQL Injection and Weak Authentication toggles. When SQLi is disabled, password verification is only skipped if Weak Authentication is also disabled. This creates a chained attack scenario where demonstrating SQLi bypass requires disabling both protections.

Current Implementation (FLAWED):
if (!isVulnerabilityEnabled('sqli_enabled')) {
    // Password verification skipped for SQLi demo
}

This means:
- SQLi disabled + Weak Auth disabled = Password verification skipped (chained attack)
- SQLi disabled + Weak Auth enabled = Password verification performed (no bypass)

Impact:
Instructors cannot demonstrate SQL injection in isolation without also affecting authentication behavior. This violates the requirement that each vulnerability should be independently toggleable.
```

**Recommended Fix:**
Remove the dependency on `sqli_enabled` in password verification logic. Password verification should be controlled solely by the `weak_auth_enabled` toggle.

## Code Flow

```
Security Settings Page
    ↓
POST: Update sqli_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define SQLI_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('sqli_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check SQLI_ENABLED constant
    ↓
┌─────────────────────────────┐
│  SQLI_ENABLED === true?     │
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
app/security/auth.php: login()
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → Secure path   │
│         NO → Vulnerable path│
└─────────────────────────────┘
    ↓
Secure: Parameterized query
Vulnerable: String concatenation
    ↓
app/config/database.php
    ↓
dbSelectOne() / dbSelect()
    ↓
PDO::prepare() → execute()
```

## Expected Results

### Secure Mode Screenshots

```
[SCREENSHOT: Security Settings with SQL Injection Protection CHECKED]

[SCREENSHOT: Login page with failed SQLi attempt]

[SCREENSHOT: Database logs showing parameterized query]
```

### Vulnerable Mode Screenshots

```
[SCREENSHOT: Security Settings with SQL Injection Protection UNCHECKED]

[SCREENSHOT: Successful login bypass with SQLi payload]

[SCREENSHOT: Database logs showing raw SQL concatenation]
```

## Instructor Notes

### Common Mistakes

1. **Forgetting to disable Weak Authentication**
   - Students may fail to bypass login even with SQLi disabled
   - Remind them that password verification still occurs if Weak Auth is enabled
   - This is a known design flaw that needs to be explained

2. **Not resetting between tests**
   - Previous toggle states may affect current test results
   - Always verify current toggle state before testing
   - Reset to secure mode after demonstration

3. **Testing with wrong input format**
   - SQLi payloads require specific syntax (quotes, operators)
   - Ensure students understand basic SQL syntax
   - Provide examples of working payloads

### Demo Tips

1. **Start with Secure Mode**
   - Show that parameterized queries prevent injection
   - Demonstrate that the application works correctly with protection
   - Build confidence in secure implementation

2. **Gradual Vulnerability Introduction**
   - First show the vulnerable code in the source
   - Explain why it's vulnerable
   - Then demonstrate the exploit
   - Finally show the fix

3. **Use Safe Test Payloads**
   - Avoid destructive payloads (DROP TABLE, DELETE)
   - Use read-only payloads for demonstration
   - Emphasize that real attacks can be much more damaging

4. **Explain the Dependency Flaw**
   - Clearly document the SQLi ↔ Weak Auth coupling
   - Explain why this is a design flaw
   - Discuss how to fix it for independent testing

### Reset Steps

After demonstrating SQL injection:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "SQL Injection Protection"
   - Check "Authentication Protection"
   - Save settings

2. **Verify Secure State**
   - Test login with normal credentials
   - Ensure SQLi payloads no longer work
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
   - Remove test entries if needed
   - Document findings for educational records

3. **Reset File Uploads**
   - Remove any files uploaded during testing
   - Clear upload directory if applicable

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **Blind SQL Injection**
   - Time-based attacks
   - Boolean-based attacks
   - Error-based attacks

2. **Second-Order SQL Injection**
   - Stored procedures
   - Database triggers
   - Indirect data manipulation

3. **Remediation Techniques**
   - Input validation
   - Output encoding
   - Least privilege database accounts
   - Web Application Firewalls (WAF)

### Assessment Questions

Test student understanding with:

1. Why does parameterized querying prevent SQL injection?
2. What is the difference between first-order and second-order SQL injection?
3. How would you fix the dependency flaw between SQLi and Weak Auth?
4. What are the limitations of input validation for preventing SQL injection?
5. Why is least privilege important for database accounts?

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+
