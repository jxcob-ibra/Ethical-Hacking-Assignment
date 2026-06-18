# Session Management Attack Testing Guide

## Attack Overview

**Description:** Session Management vulnerabilities occur when an application fails to properly secure user sessions. This can include session fixation, session hijacking, insecure session ID generation, missing timeout mechanisms, or insecure cookie configuration.

**Learning Objectives:**
- Understand the importance of secure session management
- Learn how session timeout prevents unauthorized access
- Practice identifying session vulnerabilities
- Learn remediation techniques using secure session configuration

**Risk Level:** HIGH
- Can lead to session hijacking
- Allows account takeover
- May enable privilege escalation
- Can result in unauthorized access to sensitive data

**OWASP Mapping:** A07:2021 - Identification and Authentication Failures (formerly A2:2017 - Broken Authentication)
- CWE-384: Session Fixation
- CWE-613: Insufficient Session Expiration
- CAPEC-603: Session Fixation

## Files Involved

```
app/security/auth.php
app/config/config.php
admin/security-settings.php
```

**Key Functions:**
- `checkSessionTimeout()` in `app/security/auth.php` (lines 427-435)
- Session configuration in `app/config/config.php` (lines 33-34)
- Session initialization in `app/config/config.php` (lines 72-76)

## Toggle Information

```
Protection Name:
Session Protection

Database Key:
session_enabled

Environment Variable:
SESSION_ENABLED

Default State:
N/A (Toggle does not exist)

UI Location:
/admin/security-settings.php
Checkbox: NOT IMPLEMENTED
```

**Toggle Behavior:**
- **NOT IMPLEMENTED** - No toggle currently exists for session protection
- Current implementation has hardcoded session timeout
- No option to demonstrate session vulnerabilities

## Current Implementation Status

### How Sessions Are Currently Handled

**Session Configuration:**
```php
// app/config/config.php line 33
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'myeduconnect_session');

// app/config/config.php line 34
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 3600);
```

**Session Timeout Function:**
```php
// app/security/auth.php line 427
function checkSessionTimeout() {
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > SESSION_LIFETIME) {
            logout();
            redirect(APP_URL . '/login.php', 'Session expired. Please login again.', 'warning');
        }
    }
}
```

**Session Initialization:**
```php
// app/config/config.php line 72
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
```

**Current Behavior:**
- Session timeout is hardcoded to 3600 seconds (1 hour)
- Session timeout check is called on every page load
- No toggle exists to disable session timeout
- No option to demonstrate session fixation vulnerabilities
- Session cookie configuration is not customizable

## Secure Mode Verification

### Current State

Since no toggle exists, the application is **ALWAYS** in secure mode for session management.

### Verification Steps

#### Test 1: Session Timeout

**Page to Visit:** Any authenticated page (e.g., `/student/dashboard.php`)

**Input to Test:**
```php
1. Login as student
2. Wait for 3600 seconds (1 hour)
3. Refresh page
```

**Expected Secure Behavior:**
- Session expires after 1 hour
- User is redirected to login page
- Message: "Session expired. Please login again."
- Session is destroyed

**Expected Logs:**
- Logout logged in audit_logs table
- Session timeout occurred

#### Test 2: Session Cookie Security

**Page to Visit:** Browser developer tools → Application → Cookies

**Input to Test:**
```php
1. Login as any user
2. Check session cookie properties
```

**Expected Secure Behavior:**
- Cookie has secure session name
- Cookie is HTTP-only (if configured)
- Cookie has SameSite attribute (if configured)
- Session ID is properly generated

**Expected Logs:**
- Normal session creation
- Secure cookie configuration

## Vulnerable Mode Verification

### Current State

**NOT POSSIBLE** - No toggle exists to enable session vulnerabilities. The application cannot demonstrate session fixation, session hijacking, or insecure session management.

### Recommended Implementation

To enable session vulnerability demonstrations, the following implementation is required:

#### Step 1: Add Toggle to Security Settings

**File:** `admin/security-settings.php`

Add checkbox:
```php
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="session_enabled" id="session_enabled" <?= $settings['session_enabled'] ? 'checked' : '' ?>>
    <label class="form-check-label fw-bold" for="session_enabled">
        <i class="bi bi-clock"></i> Session Protection
    </label>
</div>
<small class="text-muted">
    When OFF: Disables session timeout<br>
    When ON: Enforces session timeout
</small>
```

#### Step 2: Add Environment Variable

**File:** `app/config/config.php`

Add constant:
```php
define('SESSION_ENABLED', filter_var($_ENV['SESSION_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
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
    'session_enabled' => SESSION_ENABLED
];
```

#### Step 4: Modify checkSessionTimeout Function

**File:** `app/security/auth.php`

Update function:
```php
function checkSessionTimeout() {
    if (isProtectionEnabled('session_enabled')) {
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > SESSION_LIFETIME) {
                logout();
                redirect(APP_URL . '/login.php', 'Session expired. Please login again.', 'warning');
            }
        }
    }
    // If session protection is disabled, no timeout check
}
```

#### Step 5: Add Session Fixation Vulnerability (Optional)

**File:** `app/security/auth.php`

Add to login function for vulnerable mode:
```php
function login($email, $password) {
    // ... existing code ...
    
    if (!isProtectionEnabled('session_enabled')) {
        // VULNERABLE - Accept session ID from URL (session fixation)
        if (isset($_GET['session_id'])) {
            session_id($_GET['session_id']);
        }
    }
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    // ... rest of session setup ...
}
```

### Verification Steps (After Implementation)

#### Test 1: Disabled Session Timeout

**Prerequisites:**
- Session Protection: **UNCHECKED**

**Page to Visit:** Any authenticated page

**Input to Test:**
```php
1. Login as student
2. Wait for more than 3600 seconds (1 hour)
3. Refresh page
```

**Expected Vulnerable Behavior:**
- Session does not expire
- User remains logged in
- No timeout check occurs
- Session persists indefinitely

**Expected Logs:**
- No logout logged
- Session remains active

#### Test 2: Session Fixation (if implemented)

**Prerequisites:**
- Session Protection: **UNCHECKED**

**Page to Visit:** `/login.php?session_id=attacker_controlled_id`

**Input to Test:**
```php
Email: student@myeduconnect.com
Password: Student123!
```

**Expected Vulnerable Behavior:**
- Session ID is set to attacker-controlled value
- User logs in with fixed session ID
- Attacker can hijack session
- Session fixation vulnerability demonstrated

**Expected Logs:**
- Login succeeds
- Session ID set to provided value

## Dependency Analysis

```
Can be demonstrated independently: N/A

Required secondary vulnerability:
N/A (Toggle does not exist)

Reason:
Session protection toggle does not exist in the current implementation. Session timeout is always enforced, and there is no way to demonstrate session management vulnerabilities. This is a design flaw that prevents educational demonstration of session security concepts.

Implementation Status:
❌ NOT IMPLEMENTED - Toggle does not exist
```

## Code Flow (Current Implementation)

```
User Login
    ↓
app/security/auth.php: login()
    ↓
Set session variables
    ↓
$_SESSION['user_id'] = $user['user_id']
$_SESSION['login_time'] = time()
    ↓
Page Load
    ↓
checkSessionTimeout()
    ↓
Check elapsed time
    ↓
if (elapsed > SESSION_LIFETIME)
    ↓
logout() and redirect
```

## Code Flow (Recommended Implementation)

```
Security Settings Page
    ↓
POST: Update session_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define SESSION_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('session_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check SESSION_ENABLED constant
    ↓
┌─────────────────────────────┐
│  SESSION_ENABLED === true?  │
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
checkSessionTimeout()
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → Check timeout  │
│         NO → Skip timeout   │
└─────────────────────────────┘
    ↓
Secure: Enforce SESSION_LIFETIME
Vulnerable: No timeout check
```

## Expected Results

### Secure Mode Screenshots (Current State)

```
[SCREENSHOT: Session cookie configuration in browser]

[SCREENSHOT: Session timeout message after 1 hour]

[SCREENSHOT: Secure session initialization]
```

### Vulnerable Mode Screenshots (After Implementation)

```
[SCREENSHOT: Security Settings with Session Protection UNCHECKED]

[SCREENSHOT: Session persists beyond timeout]

[SCREENSHOT: Session fixation with URL parameter]
```

## Instructor Notes

### Common Mistakes

1. **Assuming toggle exists**
   - Students may look for session protection toggle
   - Explain that it's not currently implemented
   - This is a known design flaw
   - Document the recommended implementation

2. **Not understanding session timeout**
   - Students may not know why timeout is important
   - Explain session hijacking risks
   - Discuss shared computer scenarios
   - Explain timeout best practices

3. **Testing with short timeouts**
   - Current timeout is 1 hour (3600 seconds)
   - Students may not wait long enough
   - Recommend modifying SESSION_LIFETIME for testing
   - Or use browser tools to modify session timestamps

### Demo Tips

1. **Explain Current Implementation**
   - Show that session timeout is always enforced
   - Explain why this is secure
   - Discuss the limitation for educational purposes
   - Recommend implementing the toggle

2. **Demonstrate Session Security**
   - Show session cookie properties
   - Explain HTTP-only flag
   - Discuss SameSite attribute
   - Explain secure flag for HTTPS

3. **Modify Timeout for Testing**
   - Temporarily reduce SESSION_LIFETIME to 60 seconds
   - Demonstrate timeout behavior
   - Show logout and redirect
   - Restore original timeout after demo

4. **Discuss Session Fixation**
   - Explain the attack vector
   - Show how attackers can set session IDs
   - Discuss regeneration after login
   - Explain best practices

### Reset Steps

After implementing and testing session protection:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "Session Protection"
   - Save settings

2. **Verify Secure State**
   - Login as user
   - Wait for timeout period
   - Verify session expires
   - Check Current Status shows "Protected"

3. **Reset Database (if needed)**
   ```bash
   mysql -u root -p myeduconnect < database/init.sql
   ```

### Cleanup Steps

1. **Clear Session Data**
   - Logout all demo accounts
   - Clear browser cookies and sessions
   - Clear server-side session files

2. **Restore Timeout**
   - Reset SESSION_LIFETIME to original value (3600)
   - Update .env file if modified
   - Restart application if needed

3. **Review Audit Logs**
   - Check `audit_logs` table for session events
   - Document findings for educational records
   - Remove test entries if needed

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **Session Fixation**
   - How attackers set session IDs
   - URL parameter injection
   - Cookie manipulation
   - Session regeneration after login

2. **Session Hijacking**
   - Network sniffing
   - XSS-based session theft
   - Cross-site session theft
   - Man-in-the-middle attacks

3. **Session Cookie Security**
   - HTTP-only flag
   - Secure flag (HTTPS only)
   - SameSite attribute
   - Cookie prefix (__Secure-, __Host-)

4. **Remediation Techniques**
   - Session timeout configuration
   - Session regeneration after login
   - Secure cookie configuration
   - IP address binding
   - User agent verification
   - Concurrent session limits
   - Session invalidation on logout

### Assessment Questions

Test student understanding with:

1. Why is session timeout important for security?
2. What is the difference between session fixation and session hijacking?
3. How does the HTTP-only flag protect session cookies?
4. Why should session IDs be regenerated after login?
5. What are the limitations of session timeout alone?

### Implementation Priority

**HIGH PRIORITY** - Implement session protection toggle to enable educational demonstrations of:
- Session timeout bypass
- Session fixation attacks
- Session hijacking concepts
- Secure session configuration
- Cookie security best practices

This is essential for comprehensive security training.

### Additional Testing Scenarios

#### Scenario 1: Session Timeout Bypass (After Implementation)

1. Disable Session Protection
2. Login as student
3. Modify SESSION_LIFETIME to 60 seconds for testing
4. Wait 120 seconds
5. Refresh page
6. Observe session persists (timeout bypass)

#### Scenario 2: Session Fixation (After Implementation)

1. Disable Session Protection
2. Navigate to `/login.php?session_id=attacker123`
3. Login with valid credentials
4. Check session ID in browser
5. Observe session ID matches attacker-controlled value

#### Scenario 3: Secure Mode Verification

1. Enable Session Protection
2. Login as student
3. Wait for timeout period
4. Refresh page
5. Observe session expires and redirects to login

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+  
**Implementation Status:** NOT IMPLEMENTED - Toggle does not exist
