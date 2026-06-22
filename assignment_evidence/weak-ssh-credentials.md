# Weak SSH Credentials Vulnerability

## Purpose
This vulnerability demonstrates how weak SSH credentials can be exploited for unauthorized system access. When enabled, the SSH service uses a weak, easily guessable password for the student user account.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Weak SSH Credentials" checkbox
- **Database Key**: `weak_ssh_credentials` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `scripts/enable_weak_ssh.sh` - Script to set weak SSH password
  - `scripts/disable_weak_ssh.sh` - Script to restore secure SSH password
  - `docker/Dockerfile` (lines 45-51) - Creates SSH user and configures sudo access
  - `app/security/functions.php` (lines 699-716) - syncSshCredentials function

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Weak SSH Credentials" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `weak_ssh_credentials` row
6. The `applyVulnerabilitySideEffects()` function in `functions.php` executes the appropriate script

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('weak_ssh_credentials')` returns true:

**Script Execution (scripts/enable_weak_ssh.sh)**:
```bash
#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
CRED_FILE="$ROOT/storage/ssh/credentials.txt"
WEAK_PASS="password123"

echo "[*] Enabling weak SSH credentials (demo mode)"
mkdir -p "$ROOT/storage/ssh"
printf 'student:%s\n' "$WEAK_PASS" > "$CRED_FILE"

if command -v chpasswd >/dev/null 2>&1 && id student >/dev/null 2>&1; then
    echo "student:$WEAK_PASS" | chpasswd
    echo "[+] Applied weak password to local SSH account: student"
    # Force SSH daemon to reload configuration
    pkill -HUP sshd || service ssh restart || service sshd restart || true
    sleep 1
    echo "[+] SSH daemon reloaded"
else
    echo "[!] chpasswd or student user not available, only updating credential file"
fi

echo "[+] Weak credentials set to student/$WEAK_PASS"
echo "[+] File: storage/ssh/credentials.txt"
echo "[+] SSH endpoint (if enabled): localhost:2222"
```
Sets SSH password to "password123" for the student user and forces SSH daemon reload to apply changes immediately.

**Docker Configuration (docker/Dockerfile, lines 45-51)**:
```dockerfile
RUN useradd -m -s /bin/bash student || true && \
    echo 'student:Str0ng!Lab#Pass_2026' | chpasswd && \
    mkdir -p /var/run/sshd && \
    sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/' /etc/ssh/sshd_config && \
    echo 'www-data ALL=(ALL) NOPASSWD: /var/www/html/scripts/enable_weak_ssh.sh, /var/www/html/scripts/disable_weak_ssh.sh, /var/www/html/scripts/enable_weak_sudo.sh, /var/www/html/scripts/disable_weak_sudo.sh' >> /etc/sudoers
```
Creates student user with initial strong password, enables password authentication, and configures sudo access for vulnerability scripts.

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('weak_ssh_credentials')` returns false:

**Script Execution (scripts/disable_weak_ssh.sh)**:
```bash
#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
CRED_FILE="$ROOT/storage/ssh/credentials.txt"
STRONG_PASS="Str0ng!Lab#Pass_2026"

echo "[*] Disabling weak SSH credentials (secure mode)"
mkdir -p "$ROOT/storage/ssh"
printf 'student:%s\n' "$STRONG_PASS" > "$CRED_FILE"

if command -v chpasswd >/dev/null 2>&1 && id student >/dev/null 2>&1; then
    echo "student:$STRONG_PASS" | chpasswd
    echo "[+] Applied strong password to local SSH account: student"
    # Force SSH daemon to reload configuration
    pkill -HUP sshd || service ssh restart || service sshd restart || true
    sleep 1
    echo "[+] SSH daemon reloaded"
else
    echo "[!] chpasswd or student user not available, only updating credential file"
fi

echo "[+] Strong credential profile applied"
echo "[+] File: storage/ssh/credentials.txt"
```
Restores SSH password to "Str0ng!Lab#Pass_2026" for the student user and forces SSH daemon reload to apply changes immediately.

**Side Effect Function (app/security/functions.php, lines 699-716)**:
```php
function syncSshCredentials($enabled) {
    $scriptPath = __DIR__ . '/../../scripts/';
    $script = $enabled ? 'enable_weak_ssh.sh' : 'disable_weak_ssh.sh';
    $fullPath = $scriptPath . $script;
    
    if (file_exists($fullPath)) {
        $output = shell_exec("sudo $fullPath 2>&1");
        logAudit('SSH_CREDENTIALS_CHANGE', 'system', null, $output);
        return true;
    }
    return false;
}
```
Executes the appropriate script based on toggle state.

## Testing Procedures

### IMPORTANT: Setup Steps Before Testing

**Step 1: Make scripts executable inside Docker container**
```bash
docker exec myeduconnect-web chmod +x /var/www/html/scripts/enable_weak_ssh.sh
docker exec myeduconnect-web chmod +x /var/www/html/scripts/disable_weak_ssh.sh
```

**Step 2: Rebuild the Docker container** (required after script changes)
```bash
docker-compose down
docker-compose up -d --build
```

**Step 3: Verify container is running**
```bash
docker-compose ps
```
Ensure the `web` container shows status as "Up".

**Step 4: Verify SSH service is running**
```bash
docker exec myeduconnect-web service ssh status
```
If not running, start it:
```bash
docker exec myeduconnect-web service ssh start
```

### Test 1: SSH Login with Weak Credentials (Vulnerable Mode)
**Prerequisites**: Weak SSH Credentials CHECKED (vulnerable mode), Docker container running

1. Navigate to `/admin/security-settings.php`
2. Check the "Weak SSH Credentials" checkbox
3. Click "Save Security Settings"
4. Wait 2-3 seconds for the script to execute
5. Verify the script ran successfully by checking the log: `docker exec myeduconnect-web cat /var/www/html/storage/ssh_toggle.log`
6. Attempt SSH login: `ssh -p 2222 student@localhost`
7. When prompted for password, enter: `password123`
8. **Expected Result**: Successful SSH login with weak password
9. **If login fails**: Check the SSH toggle log for errors

### Test 2: SSH Login with Strong Credentials (Secure Mode)
**Prerequisites**: Weak SSH Credentials UNCHECKED (secure mode), Docker container running

1. Navigate to `/admin/security-settings.php`
2. Uncheck the "Weak SSH Credentials" checkbox
3. Click "Save Security Settings"
4. Wait 2-3 seconds for the script to execute
5. Verify the script ran successfully by checking the log: `docker exec myeduconnect-web cat /var/www/html/storage/ssh_toggle.log`
6. Attempt SSH login: `ssh -p 2222 student@localhost`
7. When prompted for password, enter: `Str0ng!Lab#Pass_2026`
8. **Expected Result**: Successful SSH login with strong password
9. Try weak password: `password123`
10. **Expected Result**: Login denied (Permission denied)

### Test 3: Credential File Verification
**Prerequisites**: Any toggle state

1. Check credential file inside container: `docker exec myeduconnect-web cat /var/www/html/storage/ssh/credentials.txt`
2. **Expected Vulnerable Mode**: Shows `student:password123`
3. **Expected Secure Mode**: Shows `student:Str0ng!Lab#Pass_2026`

### Test 4: Direct Script Execution (Debug Mode)
**Prerequisites**: Docker container running

If the toggle isn't working, execute the script directly inside the container:

**To enable weak credentials:**
```bash
docker exec myeduconnect-web sudo sh /var/www/html/scripts/enable_weak_ssh.sh
```

**To disable weak credentials (restore strong):**
```bash
docker exec myeduconnect-web sudo sh /var/www/html/scripts/disable_weak_ssh.sh
```

Then test SSH login immediately after.

### Test 5: Verify SSH Service Status
**Prerequisites**: Docker container running

1. Check if SSH service is running: `docker exec myeduconnect-web service ssh status` or `docker exec myeduconnect-web service sshd status`
2. **Expected Result**: SSH service should be running
3. If not running, start it: `docker exec myeduconnect-web service ssh start`

## Troubleshooting

### Issue: SSH login fails even after toggling vulnerability

**Solution 1: Check script execution logs**
```bash
docker exec myeduconnect-web cat /var/www/html/storage/ssh_toggle.log
```
Look for errors in the output. Common errors include:
- "Script not found" - scripts need to be made executable
- "Permission denied" - sudo access issue
- "chpasswd: command not found" - chpasswd not installed in container

**Solution 2: Execute script directly**
If the toggle isn't working, execute the script directly inside the container:
```bash
# To enable weak credentials
docker exec myeduconnect-web sudo sh /var/www/html/scripts/enable_weak_ssh.sh

# To disable weak credentials
docker exec myeduconnect-web sudo sh /var/www/html/scripts/disable_weak_ssh.sh
```

**Solution 3: Verify password was actually changed**
```bash
docker exec myeduconnect-web cat /var/www/html/storage/ssh/credentials.txt
```
This should show the current password setting.

**Solution 4: Check if student user exists**
```bash
docker exec myeduconnect-web id student
```
If the user doesn't exist, you may need to recreate the container:
```bash
docker-compose down
docker-compose up -d --build
```

**Solution 5: Manually set password (emergency fix)**
⚠️ **WARNING**: This is a temporary fix that overrides the toggle. After testing, you must reset the password or the toggle will not work correctly.

If scripts aren't working, manually set the password:
```bash
# For weak password (vulnerable mode) - TESTING ONLY
docker exec myeduconnect-web bash -c "echo 'student:password123' | chpasswd"
docker exec myeduconnect-web pkill -HUP sshd

# After testing, RESET to strong password so toggle works again:
docker exec myeduconnect-web bash -c "echo 'student:Str0ng!Lab#Pass_2026' | chpasswd"
docker exec myeduconnect-web pkill -HUP sshd
```

**IMPORTANT**: After using the emergency fix, the toggle mechanism will be broken until you reset the password to the strong password. The toggle scripts expect the password to be in the correct state before switching.

**Solution 6: Check SSH daemon configuration**
```bash
docker exec myeduconnect-web grep PasswordAuthentication /etc/ssh/sshd_config
```
Should show `PasswordAuthentication yes`. If not, fix it:
```bash
docker exec myeduconnect-web sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/' /etc/ssh/sshd_config
docker exec myeduconnect-web sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/' /etc/ssh/sshd_config
docker exec myeduconnect-web pkill -HUP sshd
```

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of successful SSH login with `password123`
- Screenshot of `storage/ssh/credentials.txt` showing weak password
- Screenshot of security settings with checkbox checked
- Terminal output showing script execution

### Secure Mode Evidence
- Screenshot of failed SSH login with weak password
- Screenshot of successful SSH login with strong password
- Screenshot of `storage/ssh/credentials.txt` showing strong password
- Screenshot of security settings with checkbox unchecked

## Known Dependencies
**Docker Required**: SSH access is only available within the Docker container environment. The SSH service runs on port 2222 (mapped from container port 22).

**Sudo Access**: The web server user (www-data) has sudo access to run the SSH credential scripts, which is required for the toggle to function.

## Remediation
Always use strong, unique SSH credentials:
- Use passwords with minimum 12 characters, including uppercase, lowercase, numbers, and special characters
- Implement SSH key-based authentication instead of passwords
- Disable password authentication entirely when using keys
- Use fail2ban or similar tools to block brute force attempts
- Regularly rotate SSH credentials
- Implement multi-factor authentication (MFA) for SSH access
- Limit SSH access to specific IP addresses using firewall rules
