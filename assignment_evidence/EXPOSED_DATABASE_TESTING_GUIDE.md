# Exposed Database Vulnerability - Testing Guide

## Purpose

This guide provides step-by-step instructions for testing the "Exposed Database" vulnerability, which demonstrates a Server/OS Misconfiguration where the MySQL database port and phpMyAdmin are exposed to the host machine without proper access controls.

## Vulnerability Overview

**Category:** Server / OS Misconfiguration  
**Type:** Exposed Database Service  
**OWASP Category:** A05:2021 – Security Misconfiguration  
**CWE:** CWE-215 (Information Exposure Through Environment Variables) / CWE-497 (Exposure of Sensitive System Information to an Unauthorized Control Sphere)

## How It Works

The vulnerability is controlled by the `exposed_database` security toggle in the Admin Panel:

- **Vulnerable Mode (Toggle ON):** 
  - MySQL port 3307 is exposed to the host machine
  - phpMyAdmin port 8081 is exposed to the host machine
  - Students can connect directly to MySQL using credentials from docker-compose.yml
  - Students can access phpMyAdmin without authentication

- **Secure Mode (Toggle OFF):** 
  - Database should be isolated (logged in storage/database_exposure.log)
  - Ports remain exposed for lab purposes but vulnerability is marked as disabled
  - Access should be restricted in production environments

## Testing Instructions

### Prerequisites

1. Docker Desktop is running
2. Containers are started: `docker compose up -d`
3. Admin access is available (admin@myeduconnect.com / Admin123!)

---

## VULNERABLE MODE TESTING

### Step 1: Enable the Vulnerability

1. Navigate to: `http://localhost:8080/admin/security-settings.php`
2. Login as admin if required
3. Find the "Exposed Database" toggle
4. Enable the toggle (check the checkbox)
5. Click "Save Security Settings"
6. Verify success message appears

**Expected Result:**
- Toggle is checked (enabled)
- Success message: "Security Vulnerability Manager updated."
- Log file created at: `storage/database_exposure.log`

### Step 2: Verify MySQL Port Exposure

Open PowerShell or Command Prompt and run:

```powershell
# Test if MySQL port 3307 is accessible
Test-NetConnection -ComputerName localhost -Port 3307
```

**Expected Result:**
- `TcpTestSucceeded : True`
- Port 3307 is open and accepting connections

### Step 3: Connect to MySQL Directly

Open PowerShell or Command Prompt and run:

```powershell
# Connect to MySQL using exposed credentials
docker compose exec mysql mysql -u root -prootpassword -e "SHOW DATABASES;"
```

**Alternative: Connect from host (if MySQL client is installed):**
```powershell
mysql -h 127.0.0.1 -P 3307 -u root -prootpassword -e "SHOW DATABASES;"
```

**Expected Result:**
- Connection successful
- List of databases displayed:
  - `information_schema`
  - `myeduconnect`
  - `mysql`
  - `performance_schema`
  - `sys`

### Step 4: Query Sensitive Data

```powershell
# Query all users from the database
docker compose exec mysql mysql -u root -prootpassword myeduconnect -e "SELECT user_id, email, password, first_name, last_name, role FROM users;"
```

**Expected Result:**
- All user data displayed including:
  - User IDs
  - Email addresses
  - Passwords (hashed or plaintext depending on weak_password_hashing toggle)
  - Names
  - Roles

### Step 5: Access phpMyAdmin

1. Open browser and navigate to: `http://localhost:8081`
2. Login with credentials from docker-compose.yml:
   - **Username:** `root`
   - **Password:** `rootpassword`
3. Navigate to the `myeduconnect` database
4. Browse the `users` table

**Expected Result:**
- phpMyAdmin loads successfully
- Login succeeds with root credentials
- Full database access granted
- Can view, edit, delete any data

### Step 6: Verify Log File

Check the log file to confirm vulnerability state:

```powershell
# View the database exposure log
docker compose exec web cat /var/www/html/storage/database_exposure.log
```

**Expected Result:**
- Log entry showing: `Toggle: ON - Exposed Database`
- Log entry showing: `VULNERABLE MODE: MySQL port 3307 and phpMyAdmin port 8081 are exposed`
- Log entry showing connection command: `mysql -h 127.0.0.1 -P 3307 -u root -prootpassword`
- Log entry showing phpMyAdmin URL: `http://localhost:8081`

### Step 7: Verify Audit Log

1. Navigate to: `http://localhost:8080/admin/audit-logs.php`
2. Look for recent entries with action: `ENABLE_EXPOSED_DATABASE`

**Expected Result:**
- Audit log entry showing:
  - Action: `ENABLE_EXPOSED_DATABASE`
  - Table: `security_settings`
  - New values: `{"mode":"VULNERABLE","ports":["3307","8081"]}`

---

## SECURE MODE TESTING

### Step 1: Disable the Vulnerability

1. Navigate to: `http://localhost:8080/admin/security-settings.php`
2. Find the "Exposed Database" toggle
3. Disable the toggle (uncheck the checkbox)
4. Click "Save Security Settings"
5. Verify success message appears

**Expected Result:**
- Toggle is unchecked (disabled)
- Success message: "Security Vulnerability Manager updated."
- Log file updated at: `storage/database_exposure.log`

### Step 2: Verify Log File Update

```powershell
# View the database exposure log
docker compose exec web cat /var/www/html/storage/database_exposure.log
```

**Expected Result:**
- Log entry showing: `Toggle: OFF - Exposed Database`
- Log entry showing: `SECURE MODE: Database should be isolated (requires docker-compose restart in real scenario)`
- Log entry showing: `Note: For this lab, ports remain exposed but vulnerability is marked as disabled`

### Step 3: Verify Audit Log

1. Navigate to: `http://localhost:8080/admin/audit-logs.php`
2. Look for recent entries with action: `DISABLE_EXPOSED_DATABASE`

**Expected Result:**
- Audit log entry showing:
  - Action: `DISABLE_EXPOSED_DATABASE`
  - Table: `security_settings`
  - New values: `{"mode":"SECURE","ports":["3307","8081"]}`

### Step 4: Note on Port Exposure

**Important:** For this lab environment, the MySQL (3307) and phpMyAdmin (8081) ports remain exposed in docker-compose.yml regardless of the toggle state. This is because:

1. Docker Compose port mappings cannot be dynamically changed without restarting containers
2. The vulnerability toggle logs the security state for demonstration purposes
3. In a real production environment, this toggle would modify firewall rules or docker-compose.yml

**Expected Behavior:**
- Ports remain accessible (lab limitation)
- Vulnerability is marked as disabled in the database
- Log file indicates secure mode
- Audit trail shows the disable action

---

## EXPLOITATION SCENARIOS

### Scenario 1: Direct Database Access

**Attack Vector:** Attacker discovers exposed MySQL port and connects directly.

**Steps:**
1. Port scan target: `nmap -p 3307 localhost`
2. Connect to MySQL: `mysql -h 127.0.0.1 -P 3307 -u root -prootpassword`
3. Extract sensitive data: `SELECT * FROM users;`
4. Modify data: `UPDATE users SET role='admin' WHERE email='attacker@example.com';`

**Impact:**
- Complete database compromise
- Data theft
- Privilege escalation
- Data manipulation

### Scenario 2: phpMyAdmin Access

**Attack Vector:** Attacker discovers exposed phpMyAdmin and accesses database via web interface.

**Steps:**
1. Access phpMyAdmin: `http://localhost:8081`
2. Login with default credentials
3. Browse and modify database via web UI
4. Execute SQL commands via SQL tab

**Impact:**
- User-friendly database access
- No technical skills required
- Full database control via web interface

### Scenario 3: Credential Harvesting

**Attack Vector:** Attacker extracts credentials from docker-compose.yml or environment variables.

**Steps:**
1. Access docker-compose.yml file (if exposed)
2. Extract MySQL credentials: `rootpassword`
3. Connect to database
4. Extract user passwords (if stored in plaintext via weak_password_hashing)

**Impact:**
- Credential theft
- Account takeover
- Lateral movement to other systems

---

## REMEDIATION

### Secure Configuration

1. **Remove Port Exposures:**
   ```yaml
   # docker-compose.yml - SECURE CONFIGURATION
   mysql:
     image: mysql:8.0
     # Remove or comment out ports section
     # ports:
     #   - "3307:3306"
   
   phpmyadmin:
     image: phpmyadmin/phpmyadmin
     # Remove or comment out ports section
     # ports:
     #   - "8081:80"
   ```

2. **Use Docker Networks:**
   ```yaml
   # Keep database on internal network only
   networks:
     - internal-network
   
   networks:
     internal-network:
       internal: true
   ```

3. **Add Firewall Rules:**
   ```bash
   # Block external access to MySQL
   iptables -A INPUT -p tcp --dport 3307 -j DROP
   
   # Block external access to phpMyAdmin
   iptables -A INPUT -p tcp --dport 8081 -j DROP
   ```

4. **Enable phpMyAdmin Authentication:**
   ```yaml
   # Add authentication to phpMyAdmin
   environment:
     PMA_ARBITRARY: 0
     UPLOAD_LIMIT: 100M
   ```

5. **Use Strong Database Credentials:**
   ```yaml
   # Use strong, unique passwords
   MYSQL_ROOT_PASSWORD: $(openssl rand -base64 32)
   MYSQL_PASSWORD: $(openssl rand -base64 32)
   ```

### Best Practices

1. **Never expose database ports to the internet**
2. **Use VPN or SSH tunneling for database access**
3. **Implement least privilege database users**
4. **Enable database audit logging**
5. **Regularly rotate database credentials**
6. **Use secrets management (e.g., Docker Secrets, Vault)**
7. **Monitor database access logs**
8. **Implement network segmentation**
9. **Use TLS for database connections**
10. **Disable phpMyAdmin in production**

---

## TROUBLESHOOTING

### Issue: Cannot connect to MySQL on port 3307

**Solution:**
1. Verify containers are running: `docker compose ps`
2. Check port mapping: `docker compose ps`
3. Restart containers: `docker compose restart`
4. Check MySQL logs: `docker compose logs mysql`

### Issue: phpMyAdmin not accessible on port 8081

**Solution:**
1. Verify phpMyAdmin container is running: `docker compose ps`
2. Check port mapping: `docker compose ps`
3. Restart phpMyAdmin: `docker compose restart phpmyadmin`
4. Check phpMyAdmin logs: `docker compose logs phpmyadmin`

### Issue: Toggle not saving

**Solution:**
1. Check database connection: `docker compose exec web php -r "require 'app/config/config.php'; echo DB_HOST;"`
2. Check security_settings table: `docker compose exec mysql mysql -u root -prootpassword myeduconnect -e "SELECT * FROM security_settings;"`
3. Check PHP error logs: `docker compose logs web`

### Issue: Log file not created

**Solution:**
1. Check storage directory permissions: `docker compose exec web ls -la /var/www/html/storage/`
2. Create storage directory if missing: `docker compose exec web mkdir -p /var/www/html/storage/`
3. Set permissions: `docker compose exec web chmod 755 /var/www/html/storage/`

---

## SCREENSHOT CHECKLIST

### Vulnerable Mode Screenshots

1. **Security Settings - Toggle Enabled**
   - URL: `http://localhost:8080/admin/security-settings.php`
   - Show: Exposed Database toggle checked

2. **Port Scan Results**
   - Command: `Test-NetConnection -ComputerName localhost -Port 3307`
   - Show: TcpTestSucceeded : True

3. **MySQL Connection**
   - Command: `docker compose exec mysql mysql -u root -prootpassword -e "SHOW DATABASES;"`
   - Show: Database list including myeduconnect

4. **User Data Query**
   - Command: `docker compose exec mysql mysql -u root -prootpassword myeduconnect -e "SELECT user_id, email, password, first_name, last_name, role FROM users;"`
   - Show: All user data

5. **phpMyAdmin Login**
   - URL: `http://localhost:8081`
   - Show: Login page with root/rootpassword

6. **phpMyAdmin Dashboard**
   - URL: `http://localhost:8081`
   - Show: Database list with myeduconnect

7. **phpMyAdmin Users Table**
   - URL: `http://localhost:8081`
   - Show: users table with all data

8. **Log File**
   - Command: `docker compose exec web cat /var/www/html/storage/database_exposure.log`
   - Show: Vulnerable mode log entries

9. **Audit Log**
   - URL: `http://localhost:8080/admin/audit-logs.php`
   - Show: ENABLE_EXPOSED_DATABASE entry

### Secure Mode Screenshots

10. **Security Settings - Toggle Disabled**
    - URL: `http://localhost:8080/admin/security-settings.php`
    - Show: Exposed Database toggle unchecked

11. **Log File - Secure Mode**
    - Command: `docker compose exec web cat /var/www/html/storage/database_exposure.log`
    - Show: Secure mode log entries

12. **Audit Log - Disable**
    - URL: `http://localhost:8080/admin/audit-logs.php`
    - Show: DISABLE_EXPOSED_DATABASE entry

---

## CONCLUSION

This vulnerability demonstrates the critical importance of:
1. Never exposing database ports to the public internet
2. Using proper network segmentation
3. Implementing strong authentication for database management tools
4. Regularly auditing server configurations
5. Following security best practices for container deployments

The toggle system allows students to understand the difference between vulnerable and secure configurations, making it an effective educational tool for learning about server misconfigurations.
