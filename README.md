# MyEduConnect - Cybersecurity Training Platform

A controlled cybersecurity training platform built with PHP 8+, MySQL, HTML5, CSS3, JavaScript, and Bootstrap 5. MyEduConnect is designed as an educational environment for learning web application security through hands-on practice with real vulnerabilities.

## 🎯 Project Overview

MyEduConnect is a **Learning Management System (LMS)** that has been intentionally designed as a **cybersecurity training lab**. It provides a safe, controlled environment where students and security professionals can practice identifying and exploiting common web application vulnerabilities, then learn how to fix them.

### Purpose

- **Educational Training**: Learn web application security through hands-on practice
- **Vulnerability Demonstration**: Understand how real attacks work in controlled conditions
- **Security Testing**: Practice penetration testing techniques safely
- **Remediation Learning**: See how to implement proper security fixes

## ✨ Features

### Core LMS Features
- **User Management**: Student, Teacher, and Admin roles
- **Course System**: Course creation, enrollment, and management
- **Dashboard**: Role-specific dashboards with statistics
- **File Upload**: Course material upload system
- **Audit Logging**: Comprehensive activity tracking
- **Payment System**: Mock payment processing

### Security Lab Features
- **Vulnerability Toggle System**: Switch between vulnerable and secure modes
- **Global Security Mode**: One-click vulnerability enable/disable
- **Individual Attack Controls**: Fine-grained control over each vulnerability
- **Real Vulnerabilities**: Actual exploitable vulnerabilities (not simulations)
- **Secure Implementations**: See the proper security fixes side-by-side

## 🏗️ Architecture

### Technology Stack

**Backend:**
- PHP 8.2+ with Apache
- MySQL 8.0+ database
- PDO for secure database connections
- Docker containerization

**Frontend:**
- HTML5, CSS3, JavaScript
- Bootstrap 5 for responsive design
- Bootstrap Icons

**Security Features:**
- Environment-based configuration (.env)
- Centralized security toggle system
- Vulnerability/secure mode switching
- Comprehensive audit logging

### Project Structure

```
myeduconnect/
├── app/
│   ├── config/              # Configuration files
│   │   ├── config.php      # Main configuration with .env support
│   │   └── database.php    # Database connection class
│   ├── security/            # Security functions
│   │   ├── functions.php   # Utility functions with vulnerability toggles
│   │   └── auth.php        # Authentication with attack implementations
│   ├── uploads/             # File upload directory
│   ├── controllers/         # (Future MVC structure)
│   ├── models/              # (Future MVC structure)
│   └── views/               # (Future MVC structure)
├── database/
│   └── init.sql            # Database schema with sample data
├── docker/
│   ├── Dockerfile           # PHP/Apache container
│   └── docker-compose.yml   # Multi-container setup
├── admin/                   # Admin panel pages
├── teacher/                 # Teacher panel pages
├── student/                 # Student panel pages
├── assets/                  # Static assets (CSS, JS)
├── .env                     # Environment configuration
├── docker-compose.yml       # Docker orchestration (root level)
└── README.md                # This file
```

## 🚀 Setup Instructions

### Docker Setup (Recommended)

The easiest way to run MyEduConnect is using Docker:

```bash
# Clone the repository
cd myeduconnect

# Build and start containers
docker-compose up --build

# Access the application
# Web: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### Manual Setup

**Prerequisites:**
- PHP 8.0+
- MySQL 8.0+
- Apache with mod_rewrite

**Steps:**

1. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

2. **Create Database**
   ```sql
   CREATE DATABASE myeduconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import Schema**
   ```bash
   mysql -u root -p myeduconnect < database/init.sql
   ```

4. **Configure Web Server**
   - Point Apache to the project directory
   - Ensure uploads directory is writable: `chmod 755 app/uploads`

5. **Access Application**
   ```
   http://localhost/myeduconnect
   ```

## 🔐 Default Credentials

### Administrator
- **Email:** admin@myeduconnect.com
- **Password:** password

### Teacher
- **Email:** teacher1@myeduconnect.com
- **Password:** password

### Student
- **Email:** student1@myeduconnect.com
- **Password:** password

**⚠️ Important:** Change these passwords immediately after first login in production environments.

## 💥 Attack List

MyEduConnect demonstrates the following vulnerabilities with toggleable implementations:

### 1. SQL Injection (SQLi)
**Vulnerable Mode:**
- Raw SQL concatenation in login and search functions
- Allows login bypass: `' OR '1'='1`
- Allows data extraction via UNION-based attacks

**Secure Mode:**
- PDO prepared statements with parameterized queries
- Input validation and sanitization
- Proper error handling

**Demo:**
```bash
# Vulnerable login bypass
Email: admin@myeduconnect.com' OR '1'='1'--
Password: anything
```

### 2. Stored XSS (Cross-Site Scripting)
**Vulnerable Mode:**
- Unsanitized user input in profile bio and comments
- Allows script injection: `<script>alert('XSS')</script>`
- Stored in database, executes on page load

**Secure Mode:**
- `htmlspecialchars()` output encoding
- Content Security Policy headers
- Input validation

**Demo:**
```html
<!-- Vulnerable profile bio -->
<script>alert(document.cookie)</script>
```

### 3. IDOR (Insecure Direct Object Reference)
**Vulnerable Mode:**
- No ownership validation on resource access
- Access any user's data by changing ID in URL
- Example: `/student/profile.php?user_id=5`

**Secure Mode:**
- Session ownership validation
- Role-based access control
- Authorization checks before data access

**Demo:**
```bash
# Access another user's profile
http://localhost:8080/student/profile.php?user_id=2
```

### 4. File Upload Vulnerability
**Vulnerable Mode:**
- No file type validation
- Allows executable file uploads (.php, .exe)
- Original filenames preserved (path traversal possible)
- No MIME type checking

**Secure Mode:**
- Whitelist file extensions
- MIME type validation
- Random filename generation
- File size limits
- Upload directory permissions

**Demo:**
```bash
# Upload malicious PHP file
Upload: shell.php
Content: <?php system($_GET['cmd']); ?>
Access: http://localhost:8080/app/uploads/shell.php?cmd=ls
```

### 5. Weak Authentication
**Vulnerable Mode:**
- Password verification skipped in certain conditions
- Allows login bypass when combined with SQLi
- Weak password hashing (MD5) in some implementations

**Secure Mode:**
- Bcrypt password hashing (`password_hash()`)
- Always verify passwords
- Strong password requirements
- Session management

**Demo:**
```bash
# Combined with SQLi for complete bypass
Email: ' OR '1'='1'--
Password: (any password works)
```

### 6. CSRF (Cross-Site Request Forgery)
**Vulnerable Mode:**
- Missing CSRF tokens on forms
- Allows state-changing requests from external sites
- No origin validation

**Secure Mode:**
- CSRF tokens on all forms
- Token verification on submission
- SameSite cookie attributes

## 🎛️ How Toggle System Works

MyEduConnect uses a centralized security toggle system with two levels of control:

### Global Mode
- **Vulnerable:** All attacks enabled (default for training)
- **Secure:** All attacks disabled (production mode)

### Individual Toggles
Each vulnerability can be controlled independently:
- `SQLI_ENABLED`: SQL Injection protection
- `XSS_ENABLED`: XSS protection
- `IDOR_ENABLED`: IDOR protection
- `UPLOAD_ENABLED`: File upload protection
- `WEAK_AUTH_ENABLED`: Authentication protection
- `CSRF_ENABLED`: CSRF protection

### Configuration Methods

**Via .env file:**
```bash
SECURITY_MODE=vulnerable
SQLI_ENABLED=true
XSS_ENABLED=true
IDOR_ENABLED=true
```

**Via Admin Panel:**
1. Login as administrator
2. Navigate to Security Settings
3. Toggle individual vulnerabilities or global mode
4. Changes saved to both database and .env file

### Implementation Pattern

Each vulnerability follows this pattern:

```php
if (isProtectionEnabled('vulnerability_name')) {
    // SECURE IMPLEMENTATION
    // Use proper security measures
} else {
    // VULNERABLE IMPLEMENTATION
    // Allow attack to succeed
}
```

## 🔒 Security Fix Summary

| Vulnerability | Vulnerable Implementation | Secure Implementation |
|--------------|---------------------------|----------------------|
| SQL Injection | Raw SQL concatenation | PDO prepared statements |
| XSS | Unsanitized output | htmlspecialchars() encoding |
| IDOR | No ownership check | Session validation |
| File Upload | No validation | Whitelist + MIME check |
| Weak Auth | Skip password verify | Bcrypt + verification |
| CSRF | No tokens | CSRF token validation |

## 📚 Demo Guide

### Attacker Flow (Vulnerable Mode)

1. **SQL Injection Attack**
   - Navigate to login page
   - Enter: `' OR '1'='1'--` in email field
   - Enter any password
   - Successfully logged in without valid credentials

2. **XSS Attack**
   - Login as student
   - Go to profile edit
   - Enter in bio: `<script>alert('XSS')</script>`
   - Save profile
   - View profile - alert executes

3. **IDOR Attack**
   - Login as student
   - Change URL to: `/student/profile.php?user_id=2`
   - View another student's profile data

4. **File Upload Attack**
   - Login as teacher
   - Create PHP file with: `<?php phpinfo(); ?>`
   - Upload as course material
   - Access uploaded file at: `/app/uploads/filename.php`

### Secure Flow (Secure Mode)

1. **SQL Injection Protection**
   - Toggle SQLi protection ON
   - Try SQLi attack - fails with error
   - Only valid credentials work

2. **XSS Protection**
   - Toggle XSS protection ON
   - Enter script in bio
   - Script is escaped, doesn't execute

3. **IDOR Protection**
   - Toggle IDOR protection ON
   - Try accessing other user's profile
   - Access denied - can only view own profile

4. **File Upload Protection**
   - Toggle upload protection ON
   - Try uploading .php file
   - Rejected - only allowed file types accepted

## 🧪 Testing Checklist

### Vulnerable Mode Testing

- [ ] SQLi login bypass works
- [ ] SQLi data extraction works
- [ ] XSS script injection executes
- [ ] IDOR allows accessing other users' data
- [ ] File upload accepts .php files
- [ ] Weak auth allows password bypass
- [ ] CSRF allows unauthorized requests

### Secure Mode Testing

- [ ] SQLi attacks are blocked
- [ ] XSS is properly escaped
- [ ] IDOR access is denied
- [ ] File upload rejects malicious files
- [ ] Password verification enforced
- [ ] CSRF tokens validated
- [ ] All attacks fail appropriately

## 📝 Notes

### For Instructors
- Use vulnerable mode for attack demonstrations
- Use secure mode to show proper implementations
- Compare code side-by-side to teach fixes
- Monitor audit logs during attack attempts

### For Students
- Practice each attack in vulnerable mode first
- Study the secure implementation
- Understand why the fix works
- Try to bypass protections (should fail in secure mode)

### For Administrators
- Regularly review audit logs
- Monitor security toggle changes
- Keep system updated
- Use secure mode for production

## 🛠️ Troubleshooting

**Docker Issues:**
```bash
# Rebuild containers
docker-compose down
docker-compose up --build

# View logs
docker-compose logs web
docker-compose logs mysql
```

**Database Connection:**
- Check .env file credentials
- Ensure MySQL container is running
- Verify database exists

**File Upload Issues:**
- Check app/uploads directory permissions
- Verify UPLOAD_DIR in .env
- Check PHP upload limits

## 📄 License

This project is created for educational purposes as part of a cybersecurity training program.

## 👥 Credits

Developed as a comprehensive cybersecurity training platform for educational use.

---

**MyEduConnect** - Your Gateway to Cybersecurity Education
