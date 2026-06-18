# Weak Password Storage + Database Exposure Vulnerability

## Purpose

This vulnerability demonstrates the critical security risks of:
1. **Storing passwords in plaintext** instead of using secure hashing
2. **Exposing sensitive user data** including passwords through an unprotected endpoint

These are two of the most serious security vulnerabilities that can lead to complete system compromise and massive data breaches.

## How It Works

The vulnerability is controlled by the `weak_password_hashing` security toggle in the Admin Panel:

- **Security OFF (Vulnerability Enabled):** 
  - Passwords are stored in plaintext in the database
  - A database exposure endpoint is created at `/admin/user-database-exposure.php`
  - Anyone with the URL can view all user credentials including passwords

- **Security ON (Vulnerability Disabled):** 
  - Passwords are stored using bcrypt hashing
  - The database exposure endpoint is removed
  - Accessing the endpoint returns 403 Forbidden

## Implementation Details

### Password Storage

The `hashPassword()` function in `app/security/functions.php` controls password storage:

```php
function hashPassword($password) {
    if (isVulnerabilityEnabled('weak_password_hashing')) {
        // VULNERABLE MODE: plaintext storage for demonstration.
        return $password;
    }
    // SECURE MODE: bcrypt hashing.
    return password_hash($password, PASSWORD_BCRYPT);
}
```

### Password Verification

The `verifyPassword()` function supports plaintext, MD5 (legacy), and bcrypt:

```php
function verifyPassword($password, $hash) {
    // Support plaintext, MD5, and bcrypt so existing users remain functional.
    if ($password === $hash) {
        // Plaintext match (vulnerable mode)
        return true;
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
        // MD5 hash match (legacy support)
        return md5($password) === $hash;
    }
    // Bcrypt hash match (secure mode)
    return password_verify($password, $hash);
}
```

### Database Exposure Endpoint

The `syncDatabaseExposure()` function in `app/security/functions.php` creates or removes the exposure endpoint:

```php
function syncDatabaseExposure($enabled)
{
    $root = dirname(__DIR__, 2);
    $exposureFile = $root . '/admin/user-database-exposure.php';
    
    if ($enabled) {
        // Create exposure endpoint that displays all user data including passwords
        // ...
    } else {
        // Remove exposure endpoint
        if (file_exists($exposureFile)) {
            unlink($exposureFile);
        }
    }
}
```

### Database Integration

The vulnerability is stored in the `security_settings` table:

```sql
INSERT INTO security_settings (vulnerability_name, description, enabled) VALUES
('weak_password_hashing', 'Stores passwords in plaintext instead of using bcrypt hashing.', 0);
```

## Security OFF Behavior (Vulnerability Enabled)

When the `weak_password_hashing` toggle is enabled:

1. **Password Storage:** New passwords are stored in plaintext in the database
2. **Database Exposure:** The file `admin/user-database-exposure.php` is created
3. **Access URL:** `http://localhost:8080/admin/user-database-exposure.php`
4. **Exposed Data:** User ID, email, password (plaintext), first name, last name, role, status

### Example Database Exposure Output

```
⚠️ VULNERABILITY: User Database Exposure
This page exposes sensitive user information including passwords.

User ID | Email                    | Password      | First Name | Last Name | Role    | Status
--------|--------------------------|---------------|------------|-----------|---------|--------
1       | admin@myeduconnect.com   | Admin123!     | Admin      | User      | admin   | active
4       | student1@myeduconnect.com| Student123!   | Alice      | Williams  | student | active
```

### Example Database Record (Vulnerable Mode)

```sql
-- Password stored in plaintext
INSERT INTO users (email, password, first_name, last_name, role) VALUES
('student1@myeduconnect.com', 'Student123!', 'Alice', 'Williams', 'student');
```

## Security ON Behavior (Vulnerability Disabled)

When the `weak_password_hashing` toggle is disabled:

1. **Password Storage:** New passwords are stored using bcrypt hashing
2. **Database Exposure:** The file `admin/user-database-exposure.php` is removed
3. **Access Result:** Accessing the endpoint returns 403 Forbidden
4. **Password Migration:** Existing plaintext/MD5 passwords are migrated to bcrypt on successful login

### Example Database Record (Secure Mode)

```sql
-- Password stored as bcrypt hash
INSERT INTO users (email, password, first_name, last_name, role) VALUES
('student1@myeduconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice', 'Williams', 'student');
```

### Access Denied Response

```html
<h1>403 Forbidden</h1>
<p>Access Denied</p>
```

## Impact of Exploitation

If an attacker exploits this vulnerability:

1. **Credential Theft:** All user passwords are immediately compromised
2. **Account Takeover:** Attackers can login as any user
3. **Privilege Escalation:** Admin accounts can be compromised
4. **Data Breach:** All user PII is exposed
5. **System Compromise:** Full control of the application
6. **Compliance Violations:** Violates GDPR, HIPAA, PCI DSS, and other regulations
7. **Reputation Damage:** Severe loss of user trust
8. **Legal Consequences:** Potential lawsuits and regulatory fines

## Why Plaintext Passwords Are Dangerous

1. **Immediate Compromise:** If the database is breached, all passwords are immediately readable
2. **No Protection:** No computational barrier to cracking
3. **Password Reuse:** Users often reuse passwords across sites
4. **Cascading Breaches:** One breach leads to compromises on other platforms
5. **No Time to React:** Users cannot change passwords before they're used

## Why Password Hashing Is Required

1. **One-Way Function:** Hashes cannot be reversed to obtain the original password
2. **Computational Cost:** Modern hashing algorithms (bcrypt, Argon2) are slow to compute
3. **Salting:** Prevents rainbow table attacks
4. **Time to React:** If database is breached, attackers must crack hashes before using passwords
5. **Industry Standard:** Required by all security frameworks and compliance regulations

## Remediation Strategy

### Secure Implementation

1. **Always Hash Passwords:** Never store passwords in plaintext
2. **Use Strong Algorithms:** Use bcrypt, Argon2, or scrypt (not MD5, SHA1, SHA256)
3. **Use PHP Built-in Functions:** `password_hash()` and `password_verify()`
4. **Never Expose Credentials:** Never create endpoints that expose user passwords
5. **Access Controls:** Implement proper authentication and authorization
6. **Least Privilege:** Database users should not have unnecessary permissions
7. **Encryption at Rest:** Encrypt sensitive data in the database
8. **Audit Logging:** Log all access to sensitive data

### PHP Secure Example

```php
// Secure password hashing
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Secure password verification
if (password_verify($inputPassword, $hashedPassword)) {
    // Login successful
}
```

### Database Security

1. **Network Isolation:** Database should not be directly accessible from the internet
2. **Firewall Rules:** Restrict database access to application servers only
3. **Encryption:** Use TLS for database connections
4. **Regular Backups:** Secure, encrypted backups stored off-site
5. **Access Monitoring:** Monitor and log all database access

### Web Server Configuration

```apache
# Apache - Deny access to admin directory
<Directory "/var/www/html/admin">
    Require ip 127.0.0.1
    Require ip 192.168.1.0/24
</Directory>
```

```nginx
# Nginx - Deny access to exposure endpoint
location /admin/user-database-exposure.php {
    deny all;
    return 404;
}
```

## Testing Instructions

See the complete testing guide in `WEAK_PASSWORD_DATABASE_EXPOSURE_TESTING_GUIDE.md`.
