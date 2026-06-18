# SSH Weak Credentials Vulnerability Testing Guide

## Overview
This guide provides complete instructions for testing the deliberate weak SSH credentials vulnerability implemented in the MyEduConnect security lab environment.

**Vulnerability Type:** Weak SSH Credentials  
**Purpose:** Security testing demonstration in a controlled lab environment  
**Target:** SSH service on port 2222  
**Weak Credentials:** student / password123

---

## A. Rebuild Instructions

### Step 1: Stop Existing Containers
```bash
docker-compose down
```

### Step 2: Rebuild the Web Container (with new SSH password)
```bash
docker-compose build --no-cache web
```

### Step 3: Start All Containers
```bash
docker-compose up -d
```

### Step 4: Verify Containers Are Running
```bash
docker-compose ps
```

Expected output should show:
- `myeduconnect-web` (status: Up)
- `myeduconnect-mysql` (status: Up)
- `myeduconnect-phpmyadmin` (status: Up)

---

## B. Verification Instructions

### Verify Container is Running
```bash
docker ps | grep myeduconnect-web
```

Expected: Container should be listed with status "Up"

### Verify Port is Exposed
```bash
docker port myeduconnect-web
```

Expected output should include:
- `2222/tcp -> 0.0.0.0:2222`
- `80/tcp -> 0.0.0.0:8080`
- `443/tcp -> 0.0.0.0:8443`

### Verify SSH Service is Active Inside Container
```bash
docker exec myeduconnect-web service ssh status
```

Expected: SSH service should be running

Alternative check:
```bash
docker exec myeduconnect-web ps aux | grep sshd
```

Expected: Should see sshd process running

---

## C. Nmap Discovery Test

### Command
```bash
nmap -sV -p 2222 localhost
```

### Expected Results
```
Starting Nmap 7.x.x ( https://nmap.org )
Nmap scan report for localhost (127.0.0.1)
Host is up (0.00XXs latency).

PORT     STATE SERVICE VERSION
2222/tcp open  ssh     OpenSSH 8.x.x (protocol 2.0)
Service Info: OS: Linux
```

### What This Proves
- **Port 2222 is open** - SSH service is accessible from the host
- **Service is SSH** - Confirms OpenSSH is running on the container
- **Version information** - Shows OpenSSH version, confirming it's a legitimate SSH service
- **Vulnerability exists** - An exposed SSH service with weak credentials represents a security vulnerability

### Alternative Nmap Commands
```bash
# More aggressive scan with OS detection
nmap -A -p 2222 localhost

# Scan all common ports to discover SSH
nmap -sV localhost
```

---

## D. SSH Login Test

### Command
```bash
ssh student@localhost -p 2222
```

### Expected Prompts
1. **First connection warning:**
   ```
   The authenticity of host '[localhost]:2222 ([127.0.0.1]:2222)' can't be established.
   ED25519 key fingerprint is SHA256:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.
   This key is not known by any other names
   Are you sure you want to continue connecting (yes/no/[fingerprint])?
   ```
   
   **Action:** Type `yes` and press Enter

2. **Password prompt:**
   ```
   student@localhost's password:
   ```
   
   **Action:** Type `password123` and press Enter

### Expected Successful Login Result
```
Welcome to Ubuntu X.XX.X LTS (GNU/Linux x.x.x-xxx-generic x86_64)

 * Documentation:  https://help.ubuntu.com
 * Management:     https://landscape.canonical.com
 * Support:        https://ubuntu.com/advantage

Last login: [date] from [IP]
student@myeduconnect-web:~$
```

### What This Proves
- **Authentication succeeded** - The weak password `password123` was accepted
- **Shell access granted** - User has obtained a command-line shell inside the container
- **Unauthorized access achieved** - An attacker could gain server access using predictable weak credentials
- **Vulnerability confirmed** - The system is vulnerable to brute-force or credential stuffing attacks

### Troubleshooting
If login fails:
```bash
# Check if SSH service is running
docker exec myeduconnect-web service ssh status

# Restart SSH service
docker exec myeduconnect-web service ssh restart

# Verify user exists
docker exec myeduconnect-web id student
```

---

## E. Security Impact Demonstration

After successful SSH login, execute the following commands to demonstrate the security impact:

### 1. Who Am I?
```bash
whoami
```

**Expected output:**
```
student
```

**What this proves:**
- Confirms you are logged in as the `student` user
- Demonstrates successful authentication with weak credentials
- Shows the attacker has established a user session on the server

### 2. Current Working Directory
```bash
pwd
```

**Expected output:**
```
/home/student
```

**What this proves:**
- Shows the attacker's current location in the file system
- Confirms access to the user's home directory
- Demonstrates the attacker can navigate the server's file structure

### 3. List Files
```bash
ls -la
```

**Expected output:**
```
total 24
drwxr-xr-x 1 student student 4096 [date] .
drwxr-xr-x 1 root    root    4096 [date] ..
-rw-r--r-- 1 student student  220 [date] .bash_logout
-rw-r--r-- 1 student student 3526 [date] .bashrc
-rw-r--r-- 1 student student  807 [date] .profile
```

**What this proves:**
- Attacker can view files in the user's home directory
- Demonstrates file system access and enumeration capabilities
- Shows potential for accessing sensitive files if permissions allow

### 4. Explore Web Application Files
```bash
ls -la /var/www/html
```

**Expected output:**
```
total XXX
drwxr-xr-x 1 www-data www-data 4096 [date] .
drwxr-xr-x 1 root     root     4096 [date] ..
-rw-r--r-- 1 www-data www-data XXXX [date] index.php
-rw-r--r-- 1 www-data www-data XXXX [date] login.php
-rw-r--r-- 1 www-data www-data XXXX [date] register.php
...
```

**What this proves:**
- Attacker can see web application files
- May be able to read source code if permissions allow
- Could potentially find configuration files with database credentials
- Demonstrates the risk of lateral movement within the server

### 5. Check System Information
```bash
uname -a
```

**Expected output:**
```
Linux myeduconnect-web [kernel-version] x86_64
```

**What this proves:**
- Attacker can gather system intelligence
- Helps identify potential exploits for the specific OS/kernel version
- Part of reconnaissance phase in an attack

### Why Successful Login Represents Unauthorized Server Access
1. **Weak credentials bypass** - The password `password123` is extremely weak and easily guessable
2. **No legitimate access needed** - An attacker doesn't need any prior knowledge or authorization
3. **Full shell access** - The attacker gains command-line access to the server
4. **Potential for privilege escalation** - The attacker may exploit local vulnerabilities to gain root access
5. **Data breach risk** - Access to application files could expose sensitive data
6. **Pivot point** - The compromised server can be used to attack other systems

---

## F. Assignment Evidence Collection

### Screenshots to Capture

1. **Nmap Scan Results**
   - Capture the full output of `nmap -sV -p 2222 localhost`
   - Should show port 2222 open with SSH service
   - **File name suggestion:** `nmap_ssh_discovery.png`

2. **SSH Connection Attempt**
   - Capture the initial SSH connection command
   - Include the host key verification prompt
   - **File name suggestion:** `ssh_connection_start.png`

3. **Password Entry**
   - Capture the password prompt
   - (Do not show the actual password being typed for security)
   - **File name suggestion:** `ssh_password_prompt.png`

4. **Successful Login**
   - Capture the successful login message and shell prompt
   - Should show `student@myeduconnect-web:~$`
   - **File name suggestion:** `ssh_login_success.png`

5. **whoami Command**
   - Capture output showing `student`
   - **File name suggestion:** `ssh_whoami.png`

6. **pwd Command**
   - Capture output showing `/home/student`
   - **File name suggestion:** `ssh_pwd.png`

7. **ls Command**
   - Capture output listing files in home directory
   - **File name suggestion:** `ssh_ls.png`

8. **Web Application Files**
   - Capture output of `ls -la /var/www/html`
   - Shows access to application code
   - **File name suggestion:** `ssh_web_files.png`

### Terminal Outputs to Save

Save the following terminal sessions as text files:

1. **nmap_scan.txt** - Full nmap scan output
   ```bash
   nmap -sV -p 2222 localhost > nmap_scan.txt
   ```

2. **ssh_session.txt** - Complete SSH session transcript
   - Include all commands executed and their outputs
   - Manually copy from terminal or use script command

### Nmap Evidence

- **Scan results showing open port 2222**
- **Service version detection (OpenSSH)**
- **Proof that SSH is accessible from external network**

### SSH Login Evidence

- **Successful authentication with weak password**
- **Shell access confirmation**
- **Command execution capabilities**
- **File system access demonstration**

### Additional Evidence for Report

1. **Container status:**
   ```bash
   docker ps > container_status.txt
   ```

2. **Port mapping:**
   ```bash
   docker port myeduconnect-web > port_mapping.txt
   ```

3. **SSH service status:**
   ```bash
   docker exec myeduconnect-web service ssh status > ssh_service_status.txt
   ```

---

## Summary of Changes

### Files Modified
1. **docker/Dockerfile** - Changed student user password from `Str0ng!Lab#Pass_2026` to `password123`

### Exact Code Change
**Before:**
```dockerfile
echo 'student:Str0ng!Lab#Pass_2026' | chpasswd -c SHA512
```

**After:**
```dockerfile
echo 'student:password123' | chpasswd
```

### Docker Changes
- No changes to docker-compose.yml (SSH already configured)
- Port 2222 already exposed
- SSH service already configured to start automatically

---

## Rollback Instructions

To revert the weak credentials vulnerability:

1. Edit `docker/Dockerfile`
2. Change line 47 back to:
   ```dockerfile
   echo 'student:Str0ng!Lab#Pass_2026' | chpasswd -c SHA512
   ```
3. Rebuild the container:
   ```bash
   docker-compose build --no-cache web
   docker-compose up -d
   ```

---

## How the Vulnerability Works

### Vulnerability Mechanism
1. **SSH Service Exposure:** The container runs OpenSSH Server exposed on port 2222
2. **Weak Password:** The student user account has a weak, predictable password (`password123`)
3. **Password Authentication:** SSH is configured to allow password-based authentication
4. **No Rate Limiting:** No brute-force protection is configured

### Attack Scenario
1. **Discovery:** Attacker uses Nmap to discover open SSH port (2222)
2. **Enumeration:** Attacker identifies the service as OpenSSH
3. **Credential Guessing:** Attacker tries common weak passwords
4. **Authentication:** Weak password `password123` is accepted
5. **Access Granted:** Attacker gains shell access to the container
6. **Post-Exploitation:** Attacker can explore files, potentially escalate privileges, or pivot to other systems

### Why This is a Vulnerability
- **Predictable credentials** - `password123` is a common weak password
- **No multi-factor authentication** - Single factor (password) only
- **Dictionary attack susceptible** - Easily cracked with wordlists
- **No account lockout** - Unlimited login attempts
- **Default-like credentials** - Similar to default/weak factory passwords

---

## What an Attacker Gains After Successful SSH Login

### Immediate Capabilities
1. **Command execution** - Run any command the student user is allowed to execute
2. **File system access** - Read, write (where permitted), and navigate files
3. **Network access** - Use the container as a pivot point to attack other services
4. **Process enumeration** - View running processes and identify potential targets

### Potential Attack Paths
1. **Privilege escalation** - Exploit local vulnerabilities to gain root access
2. **Data exfiltration** - Copy sensitive files from the container
3. **Lateral movement** - Access other containers via the Docker network
4. **Persistence** - Create backdoors or modify SSH keys for future access
5. **Cryptomining** - Use container resources for malicious purposes
6. **Botnet recruitment** - Add the compromised container to a botnet

### Specific Risks in This Environment
1. **Source code exposure** - Access to PHP application files
2. **Database credentials** - May find database credentials in configuration files
3. **User data access** - Potential access to uploaded files or user data
4. **Session hijacking** - Access to session files if stored on filesystem
5. **Application manipulation** - Ability to modify application code

---

## Security Best Practices (For Reference)

This vulnerability is intentionally implemented for educational purposes. In production, the following should be implemented:

1. **Strong passwords** - Minimum 12 characters, mixed case, numbers, symbols
2. **SSH key authentication** - Disable password authentication, use keys only
3. **Fail2Ban** - Implement brute-force protection
4. **Port knocking** - Hide SSH port until proper knock sequence
5. **Multi-factor authentication** - Require additional authentication factors
6. **Regular updates** - Keep SSH server and OS patched
7. **Network segmentation** - Restrict SSH access to specific IP ranges
8. **Monitoring and logging** - Log and monitor SSH access attempts
9. **Account lockout** - Implement temporary lockout after failed attempts
10. **Privilege separation** - Use sudo with minimal required permissions

---

## Conclusion

This testing guide provides complete instructions for demonstrating the weak SSH credentials vulnerability. The vulnerability allows an attacker to gain unauthorized access to the server using predictable weak credentials, highlighting the importance of using strong authentication mechanisms in production environments.

**Remember:** This vulnerability is intentionally implemented for security education in a controlled lab environment. Never deploy weak credentials in production systems.
