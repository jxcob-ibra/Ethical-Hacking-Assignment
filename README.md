# MyEduConnect - Ethical Hacking Training Platform

A vulnerable learning management system designed for cybersecurity education and ethical hacking training. This platform demonstrates common web application vulnerabilities with toggleable security controls for hands-on learning.

## Overview

MyEduConnect is a PHP-based learning management system that intentionally includes security vulnerabilities for educational purposes. Each vulnerability can be enabled or disabled through a central security control panel, allowing students to practice identification, exploitation, and remediation of security issues.

## Features

- **Vulnerability Control Panel**: Central admin interface to enable/disable individual security vulnerabilities
- **Demo Accounts**: Pre-configured accounts for testing (Admin, Teacher, Student)
- **Audit Logging**: Tracks user actions and security events
- **Docker Environment**: Complete containerized setup with web server, database, and phpMyAdmin
- **REST API**: JSON API endpoint for testing API security issues

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Git

### Setup Instructions

1. Clone the repository:
```bash
git clone <repository-url>
cd ethical-hacking--master
```

2. Start the application:
```bash
docker-compose up -d
```

3. Access the application:
- Web Application: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- MySQL: localhost:3307

### Docker Commands

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f

# Restart services
docker-compose restart

# Access MySQL container
docker exec -it ethical-hacking--master-mysql-1 mysql -u root -prootpassword myeduconnect
```

## Demo Accounts

| Role    | Email                    | Password    |
|---------|--------------------------|-------------|
| Admin   | admin@myeduconnect.com   | Admin123!   |
| Teacher | teacher@myeduconnect.com | Teacher123! |
| Student | student@myeduconnect.com | Student123! |

## Security Control Panel

Access the security control panel at: `/admin/security-settings.php`

This panel allows you to:
- Enable/disable individual vulnerabilities
- View current vulnerability status
- Access diagnostic tools
- View file permission status
- Access testing endpoints

## Vulnerabilities

The following vulnerabilities are implemented in this platform:

| Vulnerability | Database Key | Risk Level | Documentation |
|---------------|--------------|------------|---------------|
| SQL Injection | `sql_injection` | Critical | [assignment_evidence/sql-injection.md](assignment_evidence/sql-injection.md) |
| Stored XSS | `stored_xss` | High | [assignment_evidence/stored-xss.md](assignment_evidence/stored-xss.md) |
| IDOR | `idor` | High | [assignment_evidence/idor.md](assignment_evidence/idor.md) |
| Weak SSH Credentials | `weak_ssh_credentials` | High | [assignment_evidence/weak-ssh-credentials.md](assignment_evidence/weak-ssh-credentials.md) |
| Backup File Exposure | `backup_file_exposure` | High | [assignment_evidence/backup-file-exposure.md](assignment_evidence/backup-file-exposure.md) |
| Weak Password Hashing | `weak_password_hashing` | High | [assignment_evidence/weak-password-hashing.md](assignment_evidence/weak-password-hashing.md) |
| HTTP API Communication | `http_api_communication` | Medium | [assignment_evidence/http-api-communication.md](assignment_evidence/http-api-communication.md) |
| Weak File Permissions | `weak_file_permissions` | Medium | [assignment_evidence/weak-file-permissions.md](assignment_evidence/weak-file-permissions.md) |
| Exposed Database | `exposed_database` | Critical | [assignment_evidence/exposed-database.md](assignment_evidence/exposed-database.md) |
| Sudo Misconfiguration | `sudo_misconfiguration` | Critical | [assignment_evidence/sudo-misconfiguration.md](assignment_evidence/sudo-misconfiguration.md) |

## Documentation

Detailed documentation for each vulnerability is available in the `assignment_evidence/` directory. Each document includes:
- Purpose and location
- How to enable/disable the vulnerability
- Implementation details with code snippets
- Testing procedures with expected results
- Evidence to capture
- Known dependencies
- Remediation guidance

### Additional Documentation

- [Attack Testing Guide](docs/attack-testing/README.md) - Comprehensive testing instructions
- [SQL Injection Testing](docs/attack-testing/sql-injection.md) - Detailed SQLi testing guide
- [XSS Testing](docs/attack-testing/xss.md) - Detailed XSS testing guide
- [IDOR Testing](docs/attack-testing/idor.md) - Detailed IDOR testing guide
- [Authentication Testing](docs/attack-testing/authentication.md) - Authentication vulnerability guide
- [Password Testing](docs/attack-testing/password.md) - Password security guide
- [Session Testing](docs/attack-testing/session.md) - Session management guide

## Project Structure

```
ethical-hacking--master/
├── admin/                      # Admin interface pages
│   ├── security-settings.php   # Vulnerability control panel
│   ├── test-file-permissions.php # File permissions diagnostic
│   └── ...
├── api/                        # REST API endpoints
│   └── courses.php             # Course API with SQLi vulnerability
├── app/                        # Application core
│   ├── config/                 # Configuration files
│   │   ├── config.php          # Main configuration
│   │   └── database.php        # Database connection
│   └── security/               # Security functions
│       ├── auth.php            # Authentication functions
│       └── functions.php      # Security utility functions
├── assignment_evidence/        # Vulnerability documentation
├── database/                   # Database files
│   └── schema.sql              # Database schema and seed data
├── docker/                     # Docker configuration
│   └── Dockerfile              # Web container definition
├── scripts/                    # Vulnerability scripts
│   ├── enable_weak_ssh.sh      # Enable weak SSH credentials
│   ├── disable_weak_ssh.sh     # Disable weak SSH credentials
│   ├── enable_weak_sudo.sh     # Enable passwordless sudo
│   └── disable_weak_sudo.sh    # Disable passwordless sudo
├── storage/                    # Sensitive file storage
│   ├── backups/                # Database backups
│   └── ssh/                    # SSH credential files
├── student/                    # Student interface pages
├── teacher/                    # Teacher interface pages
├── docker-compose.yml          # Docker services configuration
└── .env                        # Environment variables
```

## Database Reset

To reset the database to initial state:

```bash
docker exec -it ethical-hacking--master-mysql-1 mysql -u root -prootpassword myeduconnect < database/schema.sql
```

## Known Design Flaws

Based on the codebase audit, the following design flaws have been identified:

1. **SQL Injection ↔ Weak Authentication Coupling**: The SQL Injection and Weak Authentication toggles are coupled in `app/security/auth.php` (lines 47-54). Password verification is only skipped when both toggles are disabled, preventing independent demonstration.

2. **Missing Toggles**: Some security features lack toggleable implementations:
   - Password Protection toggle (password_protection_enabled) - NOT IMPLEMENTED
   - Session Protection toggle (session_enabled) - NOT IMPLEMENTED

3. **Docker Port Mappings**: MySQL (3307) and phpMyAdmin (8081) port mappings remain active regardless of vulnerability toggle states.

## Safety Notes

- This platform is for **educational purposes only**
- Never deploy to production environments
- Use only in isolated lab environments
- Reset database after testing
- Do not use real credentials or personal data
- Ensure proper network isolation when running

## License

This project is for educational use. See LICENSE file for details.

## Support

For issues or questions, refer to the documentation in the `docs/` directory or the detailed vulnerability guides in `assignment_evidence/`.
