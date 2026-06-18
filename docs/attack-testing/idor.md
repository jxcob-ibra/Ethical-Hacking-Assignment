# IDOR (Insecure Direct Object Reference) Attack Testing Guide

## Attack Overview

**Description:** Insecure Direct Object Reference (IDOR) occurs when an application provides direct access to objects based on user-supplied input without proper access control validation. Attackers can manipulate identifiers (IDs, URLs, parameters) to access unauthorized resources belonging to other users.

**Learning Objectives:**
- Understand how IDOR vulnerabilities occur
- Learn the importance of access control checks
- Practice identifying IDOR vulnerabilities
- Learn remediation techniques using ownership validation

**Risk Level:** HIGH
- Can lead to unauthorized data access
- Allows viewing other users' private information
- Can result in data modification or deletion
- May enable privilege escalation

**OWASP Mapping:** A01:2021 - Broken Access Control (formerly A5:2017 - Broken Access Control)
- CWE-639: Insecure Direct Object Reference
- CAPEC-639: IDOR

## Files Involved

```
app/security/functions.php
teacher/edit-course.php
admin/security-settings.php
app/config/config.php
```

**Key Functions:**
- `getUserById()` in `app/security/functions.php` (lines 282-307)
- IDOR check in `teacher/edit-course.php` (line 33)

## Toggle Information

```
Protection Name:
IDOR Protection

Database Key:
idor_enabled

Environment Variable:
IDOR_ENABLED

Default State:
Enabled (true)

UI Location:
/admin/security-settings.php
Checkbox: "IDOR Protection"
```

**Toggle Behavior:**
- **Checked (ON):** Protection enabled, validates resource ownership before access
- **Unchecked (OFF):** Protection disabled, allows access to any resource ID

## Secure Mode Verification

### How to Enable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "IDOR Protection" checkbox is **CHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Protected" in Current Status section

### Verification Steps

#### Test 1: User Profile Access

**Page to Visit:** `/student/profile.php` (as student)

**Input to Test:**
```php
URL: /student/profile.php?user_id=2
(Where user_id=2 belongs to another student)
```

**Expected Secure Behavior:**
- Access denied or redirected to own profile
- Cannot view other user's profile
- Ownership validation occurs
- Error message or redirect displayed

**Expected Logs:**
- Access attempt logged in audit_logs table
- Unauthorized access attempt recorded
- No sensitive data exposed

#### Test 2: Course Edit Access

**Page to Visit:** `/teacher/edit-course.php` (as teacher)

**Input to Test:**
```php
URL: /teacher/edit-course.php?course_id=5
(Where course_id=5 belongs to another teacher)
```

**Expected Secure Behavior:**
- Access denied or redirected
- Cannot edit other teacher's course
- Ownership validation checks instructor_id
- Error message: "You do not have permission to edit this course"

**Expected Logs:**
- Access attempt logged in audit_logs table
- Unauthorized access attempt recorded
- No course modification occurs

### Code Verification

Check that the following code path is active:

```php
// app/security/functions.php line 284
if ($checkOwnership && isProtectionEnabled('idor_enabled')) {
    // SECURE - Check if current user owns the resource
    $currentUserId = getCurrentUserId();
    $currentUserRole = getCurrentUserRole();
    
    // Admins can view any user
    if ($currentUserRole === 'admin') {
        $query = "SELECT * FROM users WHERE user_id = ?";
        return dbSelectOne($query, [$userId]);
    }
    
    // Non-admins can only view their own profile
    if ($currentUserId != $userId) {
        return null;
    }
    
    $query = "SELECT * FROM users WHERE user_id = ?";
    return dbSelectOne($query, [$userId]);
}
```

```php
// teacher/edit-course.php line 33
if (isProtectionEnabled('idor_enabled')) {
    if ($course['instructor_id'] != $teacher['teacher_id']) {
        redirect('courses.php', 'You do not have permission to edit this course', 'error');
    }
}
```

## Vulnerable Mode Verification

### How to Disable Protection

1. Navigate to `/admin/security-settings.php`
2. Ensure "IDOR Protection" checkbox is **UNCHECKED**
3. Click "Save Security Settings"
4. Verify the setting shows "Vulnerable" in Current Status section

### Verification Steps

#### Test 1: User Profile Access

**Page to Visit:** `/student/profile.php` (as student)

**Input to Test:**
```php
URL: /student/profile.php?user_id=2
(Where user_id=2 belongs to another student)
```

**Expected Vulnerable Behavior:**
- Access granted to other user's profile
- Can view other user's private information
- No ownership validation occurs
- Sensitive data exposed

**Expected Logs:**
- Normal page load
- No access control warnings
- Data retrieved successfully

#### Test 2: Course Edit Access

**Page to Visit:** `/teacher/edit-course.php` (as teacher)

**Input to Test:**
```php
URL: /teacher/edit-course.php?course_id=5
(Where course_id=5 belongs to another teacher)
```

**Expected Vulnerable Behavior:**
- Access granted to other teacher's course
- Can edit course content
- No ownership validation occurs
- Course modification allowed

**Expected Logs:**
- Normal page load
- No access control warnings
- Course update logged (but not as unauthorized)

### Code Verification

Check that the following code path is active:

```php
// app/security/functions.php line 302
} else {
    // VULNERABLE - Allow access to any user ID
    $query = "SELECT * FROM users WHERE user_id = ?";
    return dbSelectOne($query, [$userId]);
}
```

```php
// teacher/edit-course.php
// When IDOR protection is disabled, the ownership check is skipped
```

## Dependency Analysis

```
Can be demonstrated independently: YES

Required secondary vulnerability:
None

Reason:
IDOR protection is implemented independently without dependencies on other vulnerability toggles. The isProtectionEnabled('idor_enabled') check is used only to control ownership validation behavior. No other toggles affect IDOR protection, and IDOR protection does not affect other toggles.

Implementation Status:
✅ CORRECT - Fully independent implementation
```

## Code Flow

```
Security Settings Page
    ↓
POST: Update idor_enabled
    ↓
Database UPDATE + .env file update
    ↓
app/config/config.php
    ↓
Define IDOR_ENABLED constant
    ↓
app/security/functions.php
    ↓
isProtectionEnabled('idor_enabled')
    ↓
┌─────────────────────────────┐
│  SECURITY_MODE === 'secure'? │
│         YES → return true    │
│         NO → continue        │
└─────────────────────────────┘
    ↓
Check IDOR_ENABLED constant
    ↓
┌─────────────────────────────┐
│  IDOR_ENABLED === true?     │
│         YES → return true    │
│         NO → return false   │
└─────────────────────────────┘
    ↓
getUserById() / edit-course.php
    ↓
┌─────────────────────────────┐
│  isProtectionEnabled()?     │
│         YES → Validate owner │
│         NO → Allow any ID   │
└─────────────────────────────┘
    ↓
┌─────────────────────────────┐
│  Ownership check            │
│  Admin: bypass check        │
│  User: match current user ID │
└─────────────────────────────┘
    ↓
Secure: Return null if not owner
Vulnerable: Return data regardless
```

## Expected Results

### Secure Mode Screenshots

```
[SCREENSHOT: Security Settings with IDOR Protection CHECKED]

[SCREENSHOT: Access denied message when trying to view another user's profile]

[SCREENSHOT: Permission error when trying to edit another teacher's course]
```

### Vulnerable Mode Screenshots

```
[SCREENSHOT: Security Settings with IDOR Protection UNCHECKED]

[SCREENSHOT: Successfully viewing another user's profile via IDOR]

[SCREENSHOT: Successfully editing another teacher's course via IDOR]
```

## Instructor Notes

### Common Mistakes

1. **Testing with admin account**
   - Admins can bypass IDOR checks by design
   - Students may think protection isn't working
   - Always test with non-admin accounts (student, teacher)
   - Explain role-based access control

2. **Not using sequential IDs**
   - IDOR is easier to demonstrate with sequential IDs
   - Random IDs make manual testing harder
   - Use database to find valid user/course IDs
   - Explain that real-world attacks often use enumeration

3. **Forgetting to check ownership parameter**
   - Some functions require $checkOwnership parameter
   - Default behavior may not check ownership
   - Review function signatures before testing
   - Document which functions support ownership checks

### Demo Tips

1. **Start with User Profile IDOR**
   - Easiest to understand and demonstrate
   - Clear visual feedback (profile data)
   - Good for introducing the concept

2. **Progress to Resource IDOR**
   - More complex scenarios (courses, files)
   - Different ownership models
   - Demonstrates broader applicability

3. **Use Multiple User Roles**
   - Demonstrate with student accounts
   - Demonstrate with teacher accounts
   - Show that role-based access works correctly

4. **Explain Horizontal vs Vertical IDOR**
   - Horizontal: Same role, different resources
   - Vertical: Lower privilege accessing higher privilege resources
   - Both are important to understand

### Reset Steps

After demonstrating IDOR:

1. **Re-enable Protection**
   - Navigate to `/admin/security-settings.php`
   - Check "IDOR Protection"
   - Save settings

2. **Verify Secure State**
   - Test with different user IDs
   - Ensure access is denied for unauthorized resources
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
   - Check `audit_logs` table for unauthorized access attempts
   - Document findings for educational records
   - Remove test entries if needed

3. **Reset Modified Resources**
   - Revert any course modifications
   - Restore original user data
   - Ensure clean state for next demonstration

### Advanced Demonstrations

For advanced classes, demonstrate:

1. **IDOR with UUIDs**
   - How random IDs reduce risk
   - UUID enumeration challenges
   - When UUIDs are still vulnerable

2. **IDOR with Access Control Lists**
   - Complex permission models
   - Group-based access control
   - Hierarchical ownership

3. **IDOR in APIs**
   - REST API IDOR vulnerabilities
   - JSON API endpoints
   - API key authentication issues

4. **Remediation Techniques**
   - Indirect object references
   - Access control matrices
   - Role-based access control (RBAC)
   - Attribute-based access control (ABAC)

### Assessment Questions

Test student understanding with:

1. What is the difference between horizontal and vertical IDOR?
2. Why do administrators typically bypass IDOR checks?
3. How can indirect object references prevent IDOR?
4. What are the limitations of sequential IDs for security?
5. How does role-based access control help prevent IDOR?

### Additional Testing Scenarios

#### Scenario 1: Student Profile Enumeration

1. Disable IDOR Protection
2. Login as student (user_id=1)
3. Access /student/profile.php?user_id=2
4. Observe successful access to another student's profile
5. Try user_id=3, 4, 5 to enumerate all students

#### Scenario 2: Course Edit via IDOR

1. Disable IDOR Protection
2. Login as teacher (teacher_id=1)
3. Access /teacher/edit-course.php?course_id=5 (belongs to teacher_id=2)
4. Observe successful access to edit form
5. Modify course title and save
6. Verify modification succeeded

#### Scenario 3: Admin Bypass Verification

1. Enable IDOR Protection
2. Login as admin
3. Access /student/profile.php?user_id=2
4. Observe that admin can still access (by design)
5. Explain role-based access control

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Tested With:** PHP 7.4+, MySQL 5.7+
