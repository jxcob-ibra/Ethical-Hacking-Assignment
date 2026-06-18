# XSS (Cross-Site Scripting) Attack Testing Guide

## Attack Overview

**Description:** Cross-Site Scripting (XSS) is a client-side code injection attack where malicious scripts are injected into otherwise benign and trusted websites. XSS attacks occur when an attacker uses a web application to send malicious code to a different end user, typically in the form of a browser-side script.

**Learning Objectives:**
- Understand the three types of XSS attacks (Reflected, Stored, DOM-based)
- Learn how output encoding prevents XSS
- Practice identifying XSS vulnerabilities in web applications
- Learn remediation techniques using context-aware encoding

**Risk Level:** HIGH
- Can lead to session hijacking
- Allows credential theft
- Can result in malicious script execution
- May enable phishing attacks

**OWASP Mapping:** A03:2021 - Injection (formerly A7:2017 - Cross-Site Scripting)
- CWE-79: Cross-site Scripting
- CAPEC-63: Cross-Site Scripting

## Files Involved

```
admin/users.php
admin/announcements.php
app/security/functions.php
admin/security-settings.php
app/config/config.php
```

**Key Functions:**
- `sanitize()` in `app/security/functions.php` (lines 12-17)
- Output encoding in `admin/users.php` (line 204)
- Output encoding in `admin/announcements.php` (lines 30, 244, 261)

## Toggle Information

```
Protection Name:
XSS Protection

Database Key:
xss_enabled

Environment Variable:
XSS_ENABLED

Default State:
Enabled (true)

UI Location:
/admin/security-settings.php
Checkbox: "XSS Protection"
```

**Toggle Behavior:**
- **Checked (ON):** Protection enabled, uses htmlspecialchars() for output encoding
- **Unchecked (OFF):** Protection disabled, outputs raw data without encoding

## Secure Mode Verification

### How to Enable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "XSS Protection" checkbox is **CHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Protected" in Current Status section

### Verification Steps

#### Test 1: User Profile Output

**Page to Visit:** `/admin/users.php`

**Input to Test:**
```php
User "About Me" field: <script>alert('XSS')</script>
```

**Expected Secure Behavior:**
- Script is not executed
- Output is HTML-encoded: `&lt;script&gt;alert('XSS')&lt;/script&gt;`
- No alert dialog appears
- Display shows literal string

**Expected Logs:**
- Normal page load
- No JavaScript errors
- No XSS-related warnings

#### Test 2: Announcement Display

**Page to Visit:** `/admin/announcements.php`

**Input to Test:**
```php
Announcement Title: <img src=x onerror=alert('XSS')>
Announcement Content: <script>alert('XSS')</script>
```

**Expected Secure Behavior:**
- Scripts are not executed
- Output is HTML-encoded using htmlspecialchars()
- No alert dialog appears
- Display shows literal strings

**Expected Logs:**
- Normal page load
- No JavaScript errors
- No XSS-related warnings

### Code Verification

Check that the following code path is active:

```php
// admin/users.php line 204
if (isProtectionEnabled('xss_enabled')) {
    echo htmlspecialchars($user['about_me'] ?? '');
} else {
    echo $user['about_me'] ?? '';
}
```

```php
// admin/announcements.php line 244
if (isProtectionEnabled('xss_enabled')) {
    echo htmlspecialchars($announcement['title']);
}
```

## Vulnerable Mode Verification

### How to Disable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "XSS Protection" checkbox is **UNCHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Vulnerable" in Current Status section

### Verification Steps

#### Test 1: User Profile Output

**Page to Visit:** `/admin/users.php`

**Input to Test:**
```php
User "About Me" field: <script>alert('XSS')</script>
```

**Expected Vulnerable Behavior:**
- Script is executed
- Alert dialog appears with "XSS" message
- Output is not HTML-encoded
- JavaScript runs in browser context

**Expected Logs:**
- Normal page load
- JavaScript alert executed
- No server-side errors

#### Test 2: Announcement Display

**Page to Visit:** `/admin/announcements.php`

**Input to Test:**
```php
Announcement Title: <img src=x onerror=alert('XSS')>
Announcement Content: <script>alert('XSS')</script>
```

**Expected Vulnerable Behavior:**
- Scripts are executed
- Alert dialog appears
- Output is not HTML-encoded
- JavaScript runs in browser context

**Expected Logs:**
- Normal page load
- JavaScript alert executed
- No server-side errors

### Code Verification

Check that the following code path is active:

```php
// admin/users.php line 206
else {
    echo $user['about_me'] ?? '';
}
```

```php
// admin/announcements.php line 247
else {
    echo $announcement['title'];
}
```

## Dependency Analysis

```
Can be demonstrated independently: YES

Required secondary vulnerability:
None

Reason:
XSS protection is implemented independently without dependencies on other vulnerability toggles. The isProtectionEnabled('xss_enabled') check is used only to control output encoding behavior. No other toggles affect XSS protection, and XSS protection does not affect other toggles.

Implementation Status:
✅ CORRECT - Fully independent implementation
```

## Code Flow

```
Security Settings Page
    ↓
POST: Update xss_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define XSS_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('xss_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check XSS_ENABLED constant
    ↓
┌─────────────────────────────┐
│  XSS_ENABLED === true?      │
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
admin/users.php / admin/announcements.php
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → htmlspecialchars()│
│         NO → raw output     │
└─────────────────────────────┘
    ↓
Browser renders output
    ↓
Secure: Encoded HTML (safe)
Vulnerable: Raw HTML (XSS executes)
```

## Expected Results

### Secure Mode Screenshots

```
[SCREENSHOT: Security Settings with XSS Protection CHECKED]

[SCREENSHOT: User profile with encoded XSS payload displayed as text]

[SCREENSHOT: Announcement with encoded script tags visible]
```

### Vulnerable Mode Screenshots

```
[SCREENSHOT: Security Settings with XSS Protection UNCHECKED]

[SCREENSHOT: Alert dialog showing XSS script execution]

[SCREENSHOT: Browser console showing executed JavaScript]
```

## Instructor Notes

### Common Mistakes

1. **Testing with browser security features**
   - Modern browsers have built-in XSS protection
   - May block some XSS payloads automatically
   - Use browser developer tools to disable protections for testing
   - Or use older browser versions for demonstration

2. **Not understanding stored vs reflected XSS**
   - Students may confuse the two types
   - Explain that stored XSS persists in database
   - Reflected XSS is reflected in immediate response
   - Both are present in this application

3. **Forgetting to refresh page**
   - Changes to toggle require page refresh
   - Browser may cache previous output
   - Clear cache or use incognito mode for testing

### Demo Tips

1. **Start with Reflected XSS**
   - Easier to understand and demonstrate
   - Immediate feedback in browser
   - Good for introducing the concept

2. **Progress to Stored XSS**
   - More dangerous in real-world scenarios
   - Persists across sessions
   - Affects multiple users

3. **Use Safe Test Payloads**
   - Avoid destructive payloads
   - Use alert() for demonstration
   - Emphasize that real attacks can steal cookies, redirect users, etc.

4. **Demonstrate Context-Aware Encoding**
   - Show that htmlspecialchars() works for HTML context
   - Explain different contexts (HTML, JavaScript, URL, CSS)
   - Discuss why context matters for encoding

### Reset Steps

After demonstrating XSS:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "XSS Protection"
   - Save settings

2. **Clean Test Data**
   - Remove test users with XSS payloads
   - Delete test announcements with scripts
   - Clear any stored malicious content

3. **Verify Secure State**
   - Reload pages to ensure encoding is active
   - Test with XSS payloads to confirm they're safe
   - Check Current Status shows "Protected"

### Cleanup Steps

1. **Clear Browser Data**
   - Clear browser cache
   - Clear cookies
   - Close and reopen browser

2. **Review Database**
   - Check users table for XSS payloads
   - Check announcements table for scripts
   - Remove test entries if needed

3. **Reset Database (if needed)**
   ```bash
   mysql -u root -p myeduconnect < database/init.sql
   ```

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **DOM-based XSS**
   - Client-side rendering vulnerabilities
   - JavaScript-based data processing
   - URL hash manipulation

2. **XSS with Content Security Policy (CSP)**
   - How CSP prevents XSS
   - Bypass techniques
   - CSP header configuration

3. **XSS Phishing**
   - Credential harvesting
   - Fake login forms
   - Social engineering

4. **Remediation Techniques**
   - Context-aware encoding
   - Input validation
   - Content Security Policy (CSP)
   - HTTP-only cookies
   - Subresource Integrity (SRI)

### Assessment Questions

Test student understanding with:

1. What is the difference between reflected, stored, and DOM-based XSS?
2. Why is htmlspecialchars() not sufficient for all XSS contexts?
3. How does Content Security Policy (CSP) help prevent XSS?
4. What are the limitations of input validation for preventing XSS?
5. Why should you use HTTP-only cookies for session management?

### Additional Testing Scenarios

#### Scenario 1: Stored XSS in User Profiles

1. Disable XSS Protection
2. Create or edit a user
3. Enter XSS payload in "About Me" field
4. Save the user
5. View the user list page
6. Observe script execution

#### Scenario 2: Reflected XSS in Search

1. Disable XSS Protection
2. Navigate to courses page
3. Enter XSS payload in search field
4. Submit search
5. Observe script execution in results

#### Scenario 3: XSS in Announcement Titles

1. Disable XSS Protection
2. Create new announcement
3. Enter XSS payload in title field
4. Save announcement
5. View announcement list
6. Observe script execution

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+
