# Exposed Database Service - Security Vulnerability Report

## Title
Exposed Database Service

## OWASP Category
A05:2021 – Security Misconfiguration

## CWE
- **CWE-215:** Information Exposure Through Environment Variables
- **CWE-497:** Exposure of Sensitive System Information to an Unauthorized Control Sphere
- **CWE-532:** Insertion of Sensitive Information into Log File

## CVSS v3.1 Score
**Base Score: 7.5 (HIGH)**
- **Attack Vector (AV):** Network (N)
- **Attack Complexity (AC):** Low (L)
- **Privileges Required (PR):** None (N)
- **User Interaction (UI):** None (N)
- **Scope (S):** Unchanged (U)
- **Confidentiality Impact (C):** High (H)
- **Integrity Impact (I):** High (H)
- **Availability Impact (A):** None (N)

**Vector String:** CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/N:N

## Description

The Exposed Database Service vulnerability occurs when database ports and management interfaces are exposed to the host machine without proper access controls. In this lab environment, MySQL (port 3307) and phpMyAdmin (port 8081) are exposed through Docker Compose port mappings, allowing unauthorized direct access to the database.

### Vulnerability Details

**Affected Components:**
- MySQL database service (port 3307:3306)
- phpMyAdmin web interface (port 8081:80)
- Docker Compose configuration (docker-compose.yml)

**Exposure Mechanism:**
1. Docker Compose port mappings expose MySQL to host machine on port 3307
2. phpMyAdmin is exposed to host machine on port 8081
3. Database credentials are stored in plaintext in docker-compose.yml
4. No authentication required for phpMyAdmin access
5. No network isolation between database and external networks

**Data at Risk:**
- User credentials (email, password hashes)
- Personal information (names, addresses, phone numbers)
- Academic records (enrollments, grades, payments)
- Administrative data (courses, announcements, audit logs)
- System configuration data

## Attack Scenario

### Scenario 1: Direct Database Access via MySQL Client

**Attacker Steps:**
1. Perform port scanning to identify open MySQL port: `nmap -p 3307 localhost`
2. Extract credentials from docker-compose.yml or environment variables
3. Connect to MySQL using exposed credentials:
   ```bash
   mysql -h 127.0.0.1 -P 3307 -u root -prootpassword
   ```
4. Query sensitive data:
   ```sql
   SELECT user_id, email, password, first_name, last_name, role FROM users;
   ```
5. Modify data for privilege escalation:
   ```sql
   UPDATE users SET role='admin' WHERE email='attacker@example.com';
   ```
6. Delete audit logs to cover tracks:
   ```sql
   DELETE FROM audit_logs WHERE action LIKE '%EXPOSED_DATABASE%';
   ```

**Outcome:** Complete database compromise, data theft, privilege escalation

### Scenario 2: phpMyAdmin Web Interface Access

**Attacker Steps:**
1. Access phpMyAdmin at `http://localhost:8081`
2. Login with default credentials (root/rootpassword)
3. Browse database structure via web interface
4. Export entire database via Export functionality
5. Modify or delete records via web UI
6. Execute arbitrary SQL queries via SQL tab

**Outcome:** User-friendly database access, no technical skills required, full database control

### Scenario 3: Credential Harvesting and Lateral Movement

**Attacker Steps:**
1. Access exposed docker-compose.yml file (if accessible)
2. Extract MySQL root password: `rootpassword`
3. Extract application database credentials
4. If weak_password_hashing is enabled, extract plaintext passwords
5. Use harvested credentials to access user accounts
6. Attempt credential reuse on other systems

**Outcome:** Credential theft, account takeover, lateral movement to other systems

## Business Impact

### Confidentiality Impact (HIGH)
- **Personal Data Exposure:** All user PII (names, emails, addresses, phone numbers) is accessible
- **Credential Theft:** Password hashes (or plaintext) can be extracted and cracked
- **Academic Records:** Student grades, enrollments, and course data exposed
- **Financial Data:** Payment information and transaction history accessible
- **Administrative Data:** Internal communications, announcements, and system configuration exposed

### Integrity Impact (HIGH)
- **Data Manipulation:** Attackers can modify grades, enrollments, and payments
- **Privilege Escalation:** User roles can be changed to gain admin access
- **Data Deletion:** Critical records can be deleted causing data loss
- **Audit Trail Tampering:** Audit logs can be modified or deleted
- **System Disruption:** Database corruption can cause application downtime

### Availability Impact (NONE)
- Database remains accessible during exploitation
- No denial-of-service impact from this vulnerability alone

### Compliance Impact
- **GDPR Violations:** Unauthorized access to personal data
- **FERPA Violations:** Exposure of student education records
- **PCI DSS Violations:** Exposure of payment card data
- **HIPAA Violations:** Exposure of protected health information
- **Legal Liability:** Potential lawsuits and regulatory fines
- **Reputation Damage:** Loss of trust from students, faculty, and partners

### Financial Impact
- **Regulatory Fines:** Up to 4% of global revenue for GDPR violations
- **Legal Costs:** Litigation and settlement expenses
- **Remediation Costs:** Forensic investigation, system hardening, and monitoring
- **Revenue Loss:** Student enrollment decline due to security concerns
- **Insurance Premiums:** Increased cybersecurity insurance costs

## Remediation

### Immediate Actions

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

2. **Use Docker Internal Networks:**
   ```yaml
   # Keep database on internal network only
   networks:
     - internal-network
   
   networks:
     internal-network:
       internal: true
   ```

3. **Implement Firewall Rules:**
   ```bash
   # Block external access to MySQL
   iptables -A INPUT -p tcp --dport 3307 -j DROP
   
   # Block external access to phpMyAdmin
   iptables -A INPUT -p tcp --dport 8081 -j DROP
   ```

### Long-Term Security Measures

1. **Secrets Management:**
   - Use Docker Secrets or external vault (HashiCorp Vault, AWS Secrets Manager)
   - Never store credentials in docker-compose.yml
   - Rotate database credentials regularly
   - Use strong, unique passwords (minimum 32 characters)

2. **Network Segmentation:**
   - Implement DMZ for web-facing services
   - Isolate database servers on private networks
   - Use VPN or SSH tunneling for database access
   - Implement network access control lists (ACLs)

3. **Authentication & Authorization:**
   - Enable phpMyAdmin authentication (htpasswd or OAuth)
   - Implement least privilege database users
   - Use role-based access control (RBAC)
   - Enable multi-factor authentication (MFA)

4. **Encryption:**
   - Enable TLS for database connections
   - Encrypt data at rest in the database
   - Use encrypted volumes for database storage
   - Implement SSL/TLS for phpMyAdmin

5. **Monitoring & Logging:**
   - Enable database audit logging
   - Monitor database access logs for suspicious activity
   - Implement real-time alerting for unauthorized access
   - Regular security audits and penetration testing

6. **phpMyAdmin Security:**
   - Disable phpMyAdmin in production environments
   - Use command-line tools for database administration
   - If phpMyAdmin is required, implement IP whitelisting
   - Enable phpMyAdmin configuration storage for additional security

### Secure Configuration Example

```yaml
# docker-compose.yml - SECURE CONFIGURATION
version: '3.8'

services:
  web:
    build:
      context: .
      dockerfile: docker/Dockerfile
    container_name: myeduconnect-web
    ports:
      - "8080:80"
      - "8443:443"
    environment:
      - DB_HOST=mysql
      - DB_NAME=myeduconnect
      - DB_USER=${DB_USER}  # Use environment variable
      - DB_PASS=${DB_PASS}  # Use environment variable
    depends_on:
      - mysql
    networks:
      - myeduconnect-network

  mysql:
    image: mysql:8.0
    container_name: myeduconnect-mysql
    environment:
      MYSQL_ROOT_PASSWORD_FILE=/run/secrets/mysql_root_password  # Use Docker Secrets
      MYSQL_DATABASE: myeduconnect
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD_FILE=/run/secrets/mysql_password  # Use Docker Secrets
    # NO PORT EXPOSURE - Database is only accessible via internal network
    volumes:
      - mysql-data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - myeduconnect-network
    secrets:
      - mysql_root_password
      - mysql_password

  # phpMyAdmin REMOVED in production - use command-line tools instead

networks:
  myeduconnect-network:
    internal: true  # Prevent external access

volumes:
  mysql-data:

secrets:
  mysql_root_password:
    file: ./secrets/mysql_root_password.txt
  mysql_password:
    file: ./secrets/mysql_password.txt
```

## Retest Procedure

### Verification Steps

1. **Verify Port Exposures Removed:**
   ```bash
   # Check if MySQL port is accessible from host
   Test-NetConnection -ComputerName localhost -Port 3307
   
   # Expected: TcpTestSucceeded : False
   ```

2. **Verify phpMyAdmin Not Accessible:**
   ```bash
   # Try to access phpMyAdmin
   curl http://localhost:8081
   
   # Expected: Connection refused or timeout
   ```

3. **Verify Internal Network Access:**
   ```bash
   # Connect to MySQL from web container (should work)
   docker compose exec web mysql -h mysql -u ${DB_USER} -p${DB_PASS} myeduconnect -e "SELECT 1;"
   
   # Expected: Query succeeds
   ```

4. **Verify External Access Blocked:**
   ```bash
   # Try to connect from host (should fail)
   mysql -h 127.0.0.1 -P 3307 -u root -p
   
   # Expected: Connection refused
   ```

5. **Verify Secrets Management:**
   ```bash
   # Check that credentials are not in docker-compose.yml
   grep -i "password" docker-compose.yml
   
   # Expected: No plaintext passwords found
   ```

6. **Verify Network Isolation:**
   ```bash
   # Check network configuration
   docker network inspect ethical-hacking--master_myeduconnect-network
   
   # Expected: "Internal": true
   ```

### Automated Testing Script

```bash
#!/bin/bash
# exposed_database_retest.sh

echo "=== Exposed Database Retest Procedure ==="

# Test 1: MySQL Port Exposure
echo "Test 1: Checking MySQL port 3307..."
if nc -z localhost 3307 2>/dev/null; then
    echo "FAIL: MySQL port 3307 is exposed"
    exit 1
else
    echo "PASS: MySQL port 3307 is not exposed"
fi

# Test 2: phpMyAdmin Port Exposure
echo "Test 2: Checking phpMyAdmin port 8081..."
if nc -z localhost 8081 2>/dev/null; then
    echo "FAIL: phpMyAdmin port 8081 is exposed"
    exit 1
else
    echo "PASS: phpMyAdmin port 8081 is not exposed"
fi

# Test 3: Internal Network Access
echo "Test 3: Checking internal network access..."
docker compose exec -T web mysql -h mysql -u ${DB_USER} -p${DB_PASS} myeduconnect -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "PASS: Internal network access works"
else
    echo "FAIL: Internal network access broken"
    exit 1
fi

# Test 4: Credential Exposure
echo "Test 4: Checking for exposed credentials..."
if grep -i "MYSQL_ROOT_PASSWORD.*=" docker-compose.yml | grep -v "FILE"; then
    echo "FAIL: Database credentials exposed in docker-compose.yml"
    exit 1
else
    echo "PASS: Database credentials not exposed in docker-compose.yml"
fi

echo "=== All Tests Passed ==="
exit 0
```

### Ongoing Monitoring

1. **Port Monitoring:**
   - Implement continuous port scanning
   - Alert on unexpected port exposures
   - Monitor Docker Compose configuration changes

2. **Access Logging:**
   - Enable database query logging
   - Monitor phpMyAdmin access logs
   - Alert on suspicious database access patterns

3. **Configuration Drift Detection:**
   - Monitor docker-compose.yml for unauthorized changes
   - Implement configuration version control
   - Use infrastructure-as-code with security scanning

4. **Regular Security Assessments:**
   - Monthly penetration testing
   - Quarterly vulnerability scanning
   - Annual security audit

## Conclusion

The Exposed Database Service vulnerability represents a critical security misconfiguration that can lead to complete database compromise. This vulnerability is particularly dangerous because:

1. **High Impact:** Exposes all sensitive data including credentials, PII, and financial information
2. **Low Complexity:** Requires minimal technical skills to exploit
3. **No Privileges Required:** Can be exploited by unauthenticated attackers
4. **Easy Discovery:** Exposed ports are easily found through port scanning

The implementation in this lab environment provides a togglable vulnerability that allows students to understand the risks of database exposure and learn proper security practices for container deployments. The vulnerability is properly integrated with the project's security toggle system and includes comprehensive logging and audit trail capabilities.

**Key Takeaways:**
- Never expose database ports to the public internet
- Use proper network segmentation and internal networks
- Implement secrets management for database credentials
- Disable database management tools (phpMyAdmin) in production
- Regularly audit server configurations for security misconfigurations
- Implement monitoring and alerting for unauthorized access
