# Attack Testing Guide

This directory contains practical testing guides for every attack category supported by the MyEduConnect training platform. These guides allow instructors, students, and auditors to verify that each vulnerability can be enabled, disabled, and demonstrated independently.

## Overview

The MyEduConnect platform is an educational cybersecurity training system designed to demonstrate web application vulnerabilities and their mitigations in a controlled environment. The Security Control Panel allows administrators to enable or disable individual security controls to demonstrate specific attack vectors.

## Security Control Panel

The Security Control Panel is located at `/admin/security-settings.php` and provides:

- **Global Security Mode**: A master switch that overrides all individual toggles
  - `Vulnerable`: All attacks enabled (for demonstration)
  - `Secure`: All attacks disabled (for secure mode)

- **Individual Vulnerability Toggles**: Fine-grained control over each attack category
  - SQL Injection Protection
  - XSS Protection
  - IDOR Protection
  - Authentication Protection
  - File Upload Protection
  - CSRF Protection

## Attack Toggles

| Toggle Name | Database Key | Environment Variable | Default State | Purpose |
|-------------|--------------|---------------------|---------------|---------|
| SQL Injection Protection | `sqli_enabled` | `SQLI_ENABLED` | Enabled (true) | Controls parameterized queries vs string concatenation |
| XSS Protection | `xss_enabled` | `XSS_ENABLED` | Enabled (true) | Controls output encoding (htmlspecialchars) |
| IDOR Protection | `idor_enabled` | `IDOR_ENABLED` | Enabled (true) | Controls resource ownership validation |
| Authentication Protection | `weak_auth_enabled` | `WEAK_AUTH_ENABLED` | Enabled (true) | Controls password verification |
| File Upload Protection | `upload_enabled` | `UPLOAD_ENABLED` | Enabled (true) | Controls file validation (type, MIME, size) |
| CSRF Protection | `csrf_enabled` | `CSRF_ENABLED` | Enabled (true) | Controls CSRF token verification |

**Note:** Session and Password protection toggles are not currently implemented (see Design Flaws section).

## Dependency Matrix

| Attack Category | Independent Toggle | Depends on Other Vulnerabilities | Notes |
| --------------- | ------------------ | -------------------------------- | ----- |
| SQL Injection | **NO** | Weak Authentication | Lines 47-54 in auth.php create coupling |
| XSS | **YES** | None | Fully independent |
| IDOR | **YES** | None | Fully independent |
| Authentication | **NO** | SQL Injection | Lines 47-54 in auth.php create coupling |
| Password | **N/A** | Toggle does not exist | No toggle for weak password hashing |
| File Upload | **YES** | None | Fully independent |
| CSRF | **NO** | Not consistently implemented | Only one file implements CSRF checks |
| Session | **N/A** | Toggle does not exist | No toggle for session vulnerabilities |

## Design Flaws

The current implementation has the following design flaws that prevent independent demonstration of vulnerabilities:

### 1. SQL Injection ↔ Weak Authentication Coupling
- **Location:** `app/security/auth.php` lines 47-54
- **Issue:** Password verification behavior depends on SQLi state
- **Impact:** Cannot demonstrate SQLi without affecting authentication
- **Status:** Requires refactoring

### 2. Missing Session Vulnerability Toggle
- **Location:** Session timeout in `app/security/auth.php` lines 427-435
- **Issue:** No toggle exists to control session protection
- **Impact:** Cannot demonstrate session fixation/hijacking
- **Status:** Requires implementation

### 3. Inconsistent CSRF Implementation
- **Location:** Only `admin/announcements.php` implements CSRF verification
- **Issue:** CSRF toggle exists but is not applied consistently
- **Impact:** CSRF toggle is ineffective for demonstration
- **Status:** Requires implementation across all POST handlers

### 4. Missing Password Protection Toggle
- **Issue:** No toggle exists to demonstrate weak password hashing (MD5/sha1 vs bcrypt)
- **Impact:** Cannot demonstrate password hashing vulnerabilities
- **Status:** Requires implementation

## Testing Prerequisites

### System Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Docker (optional, for containerized deployment)

### Database Setup
```bash
# Import database schema
mysql -u root -p myeduconnect < database/schema.sql

# Import initial data
mysql -u root -p myeduconnect < database/init.sql
```

### Environment Configuration
Ensure `.env` file is configured with:
```env
DB_HOST=mysql
DB_NAME=myeduconnect
DB_USER=root
DB_PASS=rootpassword
SECURITY_MODE=vulnerable
SQLI_ENABLED=true
XSS_ENABLED=true
IDOR_ENABLED=true
UPLOAD_ENABLED=true
WEAK_AUTH_ENABLED=true
CSRF_ENABLED=true
```

## Demo Accounts

The following demo accounts are available for testing:

### Administrator
- **Email:** admin@myeduconnect.com
- **Password:** Admin123!
- **Role:** Administrator
- **Access:** Full administrative access including Security Control Panel

### Teacher
- **Email:** teacher@myeduconnect.com
- **Password:** Teacher123!
- **Role:** Teacher
- **Access:** Course management, student grading, material uploads

### Student
- **Email:** student@myeduconnect.com
- **Password:** Student123!
- **Role:** Student
- **Access:** Course enrollment, material viewing, profile management

**Note:** These accounts are created during database initialization. See `database/init.sql` for details.

## Database Reset Instructions

To reset the database to initial state:

```bash
# Option 1: Using MySQL command line
mysql -u root -p -e "DROP DATABASE IF EXISTS myeduconnect; CREATE DATABASE myeduconnect;"
mysql -u root -p myeduconnect < database/schema.sql
mysql -u root -p myeduconnect < database/init.sql

# Option 2: Using Docker (if using containerized setup)
docker-compose down -v
docker-compose up -d
docker exec -i myeduconnect-db mysql -uroot -prootpassword myeduconnect < database/schema.sql
docker exec -i myeduconnect-db mysql -uroot -prootpassword myeduconnect < database/init.sql
```

## Safety Notes

### ⚠️ Important Security Warnings

1. **Never Deploy to Production**
   - This platform is designed for educational purposes only
   - It intentionally contains security vulnerabilities
   - Deploying to production environments poses severe security risks

2. **Use Isolated Environment**
   - Run in a dedicated testing environment (VM, container, or isolated network)
   - Do not host on public-facing servers
   - Restrict network access to authorized users only

3. **Data Privacy**
   - Do not use real user data or credentials
   - Use only demo/test accounts
   - Clear sensitive data after testing sessions

4. **Access Control**
   - Limit access to trusted instructors and students
   - Use strong authentication for admin access
   - Monitor access logs regularly

5. **Backup Regularly**
   - Backup database before testing
   - Keep backups in secure location
   - Document any custom modifications

### Testing Best Practices

1. **Test One Vulnerability at a Time**
   - Disable other toggles when focusing on specific attack
   - Document toggle states before each test
   - Reset to secure mode after testing

2. **Document Findings**
   - Record successful exploit attempts
   - Note any unexpected behavior
   - Capture screenshots for educational materials

3. **Clean Up After Testing**
   - Reset database to initial state
   - Clear uploaded files
   - Re-enable all protections

4. **Educational Context**
   - Emphasize remediation techniques
   - Explain real-world impacts
   - Discuss defense-in-depth strategies

## Quick Start Guide

1. **Start the Application**
   ```bash
   docker-compose up -d
   # Or start web server manually
   ```

2. **Access Security Control Panel**
   - Navigate to `http://localhost:8080/admin/security-settings.php`
   - Login with admin credentials

3. **Configure Vulnerability Toggles**
   - Set Global Mode to "Vulnerable" for attack demonstrations
   - Enable/disable individual toggles as needed
   - Save settings

4. **Test Specific Vulnerability**
   - Refer to individual attack documentation in this folder
   - Follow verification steps for secure and vulnerable modes
   - Document results

5. **Reset After Testing**
   - Set Global Mode to "Secure"
   - Enable all individual protections
   - Reset database if needed

## Additional Resources

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- CWE/SANS Top 25: https://cwe.mitre.org/top25/
- Web Security Academy: https://portswigger.net/web-security

## Support

For issues or questions about the training platform:
- Review individual attack documentation
- Check database initialization logs
- Verify environment configuration
- Consult project README.md

---

**Last Updated:** June 16, 2026  
**Platform Version:** 2.0.0  
**Documentation Version:** 1.0
