# Weak Password Hashing Vulnerability

## Purpose
This vulnerability demonstrates how weak password hashing algorithms can lead to credential exposure. When enabled, passwords are stored in plaintext instead of using secure hashing algorithms like bcrypt.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Weak Password Hashing" checkbox
- **Database Key**: `weak_password_hashing` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `app/security/functions.php` (lines 46-68) - hashPassword and verifyPassword functions
  - `app/security/auth.php` (lines 139, 214, 283) - Registration functions calling hashPassword

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Weak Password Hashing" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `weak_password_hashing` row

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('weak_password_hashing')` returns true:

**Password Hashing Function (app/security/functions.php, lines 46-51)**:
```php
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        // VULNERABLE - Store password in plaintext
        return $password;
    } else {
        // SECURE - Use bcrypt
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
```
Stores passwords in plaintext without any hashing.

**Password Verification Function (app/security/functions.php, lines 53-68)**:
```php
function verifyPassword($password, $hash) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        // VULNERABLE - Direct string comparison
        return $password === $hash;
    } else {
        // SECURE - Use bcrypt verification
        return password_verify($password, $hash);
    }
}
```
Compares plaintext passwords directly without hashing.

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('weak_password_hashing')` returns false:

**Password Hashing Function**:
```php
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        return $password;
    } else {
        // SECURE - Use bcrypt
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
```
Uses bcrypt with PASSWORD_BCRYPT algorithm.

**Password Verification Function**:
```php
function verifyPassword($password, $hash) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        return $password === $hash;
    } else {
        // SECURE - Use bcrypt verification
        return password_verify($password, $hash);
    }
}
```
Uses `password_verify()` for secure bcrypt comparison.

## Testing Procedures

### Test 1: Register with Weak Password Hashing
**Prerequisites**: Weak Password Hashing CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Weak Password Hashing"
3. Click "Save Security Settings"
4. Navigate to `/register.php`
5. Register a new student with password: `TestPass123!`
6. Access database: `SELECT password FROM users WHERE email = '<registered_email>';`
7. **Expected Vulnerable Result**: Password stored as plaintext: `TestPass123!`
8. **Expected Secure Result**: Password stored as bcrypt hash: `$2y$10$...`

### Test 2: Login with Weak Password Hashing
**Prerequisites**: Weak Password Hashing CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "Weak Password Hashing"
3. Click "Save Security Settings"
4. Navigate to `/login.php`
5. Login with existing credentials
6. **Expected Result**: Login succeeds (plaintext comparison works)
7. Access database to verify password is stored in plaintext

### Test 3: Secure Mode Verification
**Prerequisites**: Weak Password Hashing UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Weak Password Hashing"
3. Click "Save Security Settings"
4. Navigate to `/register.php`
5. Register a new student with password: `TestPass123!`
6. Access database: `SELECT password FROM users WHERE email = '<registered_email>';`
7. **Expected Result**: Password stored as bcrypt hash starting with `$2y$10$`
8. Hash should be 60 characters long

### Test 4: Database Verification
**Prerequisites**: Any toggle state

1. Access MySQL: `docker exec -it ethical-hacking--master-mysql-1 mysql -u root -prootpassword myeduconnect`
2. Query: `SELECT user_id, email, password FROM users LIMIT 5;`
3. **Expected Vulnerable Mode**: Passwords visible in plaintext
4. **Expected Secure Mode**: Passwords visible as bcrypt hashes

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of database showing plaintext passwords
- Screenshot of security settings with checkbox checked
- Screenshot of successful registration and login
- Database query output showing readable passwords

### Secure Mode Evidence
- Screenshot of database showing bcrypt hashes
- Screenshot of security settings with checkbox unchecked
- Screenshot of successful registration and login
- Database query output showing hashed passwords (starting with `$2y$10$`)

## Known Dependencies
**Independently Demonstrable**: Yes. Password hashing toggle is implemented independently without dependencies on other vulnerability toggles.

**Existing Passwords**: When toggling from secure to vulnerable mode, existing bcrypt-hashed passwords will not automatically convert to plaintext. Only newly registered or updated passwords will use the selected hashing method.

## Remediation
Always use strong password hashing algorithms:
- Use bcrypt with appropriate work factor (PASSWORD_BCRYPT)
- Consider Argon2 for modern applications (PASSWORD_ARGON2ID)
- Never store passwords in plaintext
- Never use weak algorithms (MD5, SHA1, SHA256 without salt)
- Always use a unique salt per password (bcrypt handles this automatically)
- Implement password complexity requirements
- Use password_hash() and password_verify() in PHP
- Regularly update hashing algorithms as technology advances
