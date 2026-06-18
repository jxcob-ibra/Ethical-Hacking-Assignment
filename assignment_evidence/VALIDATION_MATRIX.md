# Validation Matrix

| Vulnerability | Enabled | Exploitable | Disabled | Re-Test Passed |
| --- | --- | --- | --- | --- |
| SQL Injection | Yes | Yes (login/search concat path) | Yes | Yes (prepared statements block payloads) |
| Stored XSS | Yes | Yes (raw render in vulnerable mode) | Yes | Yes (escaped output) |
| IDOR | Yes | Yes (`profile.php?id=` style access) | Yes | Yes (ownership validation) |
| Weak SSH Credentials | Yes | Yes (`student/password123` profile) | Yes | Yes (strong credential profile) |
| Backup File Exposure | Yes | Yes (`/backups/backup.sql`) | Yes | Yes (public copy removed) |
| Weak Password Hashing | Yes | Yes (MD5 hash generation) | Yes | Yes (bcrypt + migration) |
| HTTP API Communication | Yes | Yes (`http://.../api/ping.php`) | Yes | Yes (`https://.../api/ping.php`) |

> Execute full manual verification in Docker using the evidence README files in each category folder.
