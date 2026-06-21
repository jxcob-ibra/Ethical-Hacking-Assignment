# IDOR (Insecure Direct Object Reference) Vulnerability

## Purpose
This vulnerability demonstrates how IDOR attacks occur when an application provides direct access to objects based on user-supplied input (IDs, URLs, parameters) without proper access control validation, allowing attackers to access unauthorized resources.

## Location
- **Control Panel**: `/admin/security-settings.php` - "IDOR Protection" checkbox
- **Database Key**: `idor` in `security_settings` table
- **Environment Variable**: `IDOR_ENABLED` in `.env` file
- **Implementation Files**:
  - `app/security/functions.php` (lines 282-307) - getUserById function
  - `student/profile.php` (lines 16-31) - Student profile access

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "IDOR Protection" checkbox
3. **To enable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Check the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `idor` row

## Implementation Details

### Vulnerable Mode (Toggle Disabled)
When `isVulnerabilityEnabled('idor')` returns true:

**Student Profile Access (student/profile.php, lines 18-23)**:
```php
if (isVulnerabilityEnabled('idor')) {
    // VULNERABLE MODE: direct object access without ownership checks.
    $student = getStudentByUserId($targetUserId);
    if (!$student) {
        redirect('dashboard.php', 'Student profile not found.', 'error');
    }
}
```
Allows access to any user ID without ownership validation.

**getUserById Function (app/security/functions.php, lines 302-307)**:
```php
} else {
    // VULNERABLE - Allow access to any user ID
    $query = "SELECT * FROM users WHERE user_id = ?";
    return dbSelectOne($query, [$userId]);
}
```
Returns user data for any ID without ownership check.

### Secure Mode (Toggle Enabled)
When `isVulnerabilityEnabled('idor')` returns false:

**Student Profile Access (student/profile.php, lines 24-31)**:
```php
} else {
    // SECURE MODE: enforce ownership, admin bypass handled in helper.
    $user = getUserById($targetUserId, true);
    if (!$user) {
        redirect('dashboard.php', 'Access denied.', 'error');
    }
    $student = getStudentByUserId($targetUserId);
}
```
Enforces ownership validation before access.

**getUserById Function (app/security/functions.php, lines 284-301)**:
```php
if ($checkOwnership && isVulnerabilityEnabled('idor')) {
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
Validates ownership: admins can access any user, non-admins can only access their own profile.

## Testing Procedures

### Test 1: Student Profile IDOR
**Prerequisites**: IDOR Protection UNCHECKED (vulnerable mode)

1. Login as student: `student@myeduconnect.com` / `Student123!`
2. Note your user ID (check URL or database)
3. Access another student's profile by modifying URL: `/student/profile.php?id=2` (where 2 is another student's ID)
4. **Expected Vulnerable Result**: Successfully view another student's profile with personal information
5. **Expected Secure Result**: Redirected to own profile or "Access denied" error

### Test 2: Admin Bypass Verification
**Prerequisites**: IDOR Protection CHECKED (secure mode)

1. Login as admin: `admin@myeduconnect.com` / `Admin123!`
2. Access student profile: `/student/profile.php?id=2`
3. **Expected Result**: Admin can view any user's profile (by design)
4. This demonstrates role-based access control works correctly

### Test 3: Secure Mode Ownership Check
**Prerequisites**: IDOR Protection CHECKED (secure mode)

1. Login as student: `student@myeduconnect.com` / `Student123!`
2. Try to access another student's profile: `/student/profile.php?id=2`
3. **Expected Result**: "Access denied" error or redirect to own profile
4. Verify you can only access your own profile: `/student/profile.php?id=<your_id>`

### Test 4: Vulnerable Mode Enumeration
**Prerequisites**: IDOR Protection UNCHECKED (vulnerable mode)

1. Login as student
2. Enumerate user IDs by trying sequential numbers: `/student/profile.php?id=1`, `/student/profile.php?id=2`, `/student/profile.php?id=3`, etc.
3. **Expected Vulnerable Result**: Can view all users' profiles
4. **Expected Secure Result**: Only your own profile accessible

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of viewing another student's profile via IDOR
- Screenshot of personal information exposed (name, email, student ID, etc.)
- Screenshot of successful enumeration of multiple user profiles
- Database query logs showing access to multiple user IDs

### Secure Mode Evidence
- Screenshot of "Access denied" error when trying to view another user's profile
- Screenshot of redirect to own profile
- Screenshot of admin successfully viewing any user's profile
- Audit logs showing unauthorized access attempts blocked

## Known Dependencies
**Independently Demonstrable**: Yes. IDOR protection is implemented independently without dependencies on other vulnerability toggles.

## Remediation
Always validate resource ownership before access:
- Check if current user owns the requested resource
- Implement role-based access control (RBAC)
- Use indirect object references (random UUIDs instead of sequential IDs)
- Validate authorization on every object access
- Log unauthorized access attempts for monitoring
- Implement access control matrices for complex permissions
