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
    printf 'student:%s\n' "$WEAK_PASS | chpasswd -c SHA512
    echo "[+] Applied weak password to local SSH account: student"
fi

echo "[+] Weak credentials set to student/$WEAK_PASS"
echo "[+] File: storage/ssh/credentials.txt"
echo "[+] SSH endpoint (if enabled): localhost:2222"
```
Sets SSH password to "password123" for the student user.

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

echo "[*] Disabling weak SSH credentials (restoring secure mode)"
mkdir -p "$ROOT/storage/ssh"
printf 'student:%s\n' "$STRONG_PASS" > "$CRED_FILE"

if command -v chpasswd >/dev/null 2>&1 && id student >/dev/null 2>&1; then
    printf 'student:%s\n' "$STRONG_PASS | chpasswd -c SHA512
    echo "[+] Restored strong password to local SSH account: student"
fi

echo "[+] Secure credentials restored to student/$STRONG_PASS"
echo "[+] File: storage/ssh/credentials.txt"
```
Restores SSH password to "Str0ng!Lab#Pass_2026" for the student user.

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

### Test 1: SSH Login with Weak Credentials
**Prerequisites**: Weak SSH Credentials CHECKED (vulnerable mode), Docker container running

1. Ensure Docker container is running: `docker-compose up -d`
2. Access SSH via port 2222 (mapped in docker-compose.yml)
3. Attempt SSH login: `ssh -p 2222 student@localhost`
4. Enter password: `password123`
5. **Expected Vulnerable Result**: Successful SSH login with weak password
6. **Expected Secure Result**: Login denied (password incorrect)

### Test 2: SSH Login with Strong Credentials
**Prerequisites**: Weak SSH Credentials UNCHECKED (secure mode), Docker container running

1. Ensure Docker container is running
2. Access SSH via port 2222
3. Attempt SSH login: `ssh -p 2222 student@localhost`
4. Enter password: `Str0ng!Lab#Pass_2026`
5. **Expected Result**: Successful SSH login with strong password
6. Try weak password: `password123`
7. **Expected Result**: Login denied

### Test 3: Credential File Verification
**Prerequisites**: Any toggle state

1. Check credential file: `cat storage/ssh/credentials.txt`
2. **Expected Vulnerable Mode**: Shows `student:password123`
3. **Expected Secure Mode**: Shows `student:Str0ng!Lab#Pass_2026`

### Test 4: Toggle Verification via Security Settings
**Prerequisites**: Docker container running

1. Navigate to `/admin/security-settings.php`
2. Check "Weak SSH Credentials"
3. Click "Save Security Settings"
4. Check `storage/ssh/credentials.txt` file
5. **Expected Result**: Password changed to `password123`
6. Uncheck "Weak SSH Credentials"
7. Click "Save Security Settings"
8. Check `storage/ssh/credentials.txt` file
9. **Expected Result**: Password changed to `Str0ng!Lab#Pass_2026`

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
