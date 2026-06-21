# Sudo Misconfiguration Vulnerability

## Purpose
This vulnerability demonstrates how misconfigured sudo privileges can lead to privilege escalation. When enabled, the student user is granted passwordless sudo access to all commands, allowing unauthorized system-level access.

## Location
- **Control Panel**: `/admin/security-settings.php` - "Sudo Misconfiguration" checkbox
- **Database Key**: `sudo_misconfiguration` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `scripts/enable_weak_sudo.sh` - Script to enable passwordless sudo
  - `scripts/disable_weak_sudo.sh` - Script to disable passwordless sudo
  - `docker/Dockerfile` (line 51) - Initial sudo configuration for vulnerability scripts
  - `app/security/functions.php` (lines 824-847) - syncSudoConfiguration function

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "Sudo Misconfiguration" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `sudo_misconfiguration` row
6. The `applyVulnerabilitySideEffects()` function in `functions.php` calls `syncSudoConfiguration()`

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('sudo_misconfiguration')` returns true:

**Sudo Configuration Sync Function (app/security/functions.php, lines 824-847)**:
```php
function syncSudoConfiguration($enabled) {
    $scriptPath = __DIR__ . '/../../scripts/';
    $script = $enabled ? 'enable_weak_sudo.sh' : 'disable_weak_sudo.sh';
    $fullPath = $scriptPath . $script;
    
    if (file_exists($fullPath)) {
        $output = shell_exec("sudo $fullPath 2>&1");
        logAudit('SUDO_CONFIG_CHANGE', 'system', null, $output);
        return true;
    }
    return false;
}
```
Executes the enable script to grant passwordless sudo access.

**Enable Weak Sudo Script (scripts/enable_weak_sudo.sh)**:
```bash
#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
SUDOERS_FILE="/etc/sudoers.d/student_weak_sudo"
LOG_FILE="$ROOT/storage/ssh/sudo_toggle.log"

echo "[*] Enabling weak sudo configuration (demo mode)"
mkdir -p "$ROOT/storage/ssh"

# Create sudoers entry allowing student to run all commands without password
echo 'student ALL=(ALL) NOPASSWD:ALL' > "$SUDOERS_FILE"
chmod 440 "$SUDOERS_FILE"

# Log the change
echo "$(date '+%Y-%m-%d %H:%M:%S') - Enabled weak sudo: student can run ALL commands without password" >> "$LOG_FILE"

echo "[+] Weak sudo configuration applied"
echo "[+] Student user can now escalate privileges without password"
echo "[+] File: $SUDOERS_FILE"
```
Creates sudoers entry allowing student to run all commands with `NOPASSWD:ALL`.

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('sudo_misconfiguration')` returns false:

**Disable Weak Sudo Script (scripts/disable_weak_sudo.sh)**:
```bash
#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
SUDOERS_FILE="/etc/sudoers.d/student_weak_sudo"
LOG_FILE="$ROOT/storage/ssh/sudo_toggle.log"

echo "[*] Disabling weak sudo configuration (restoring secure mode)"
mkdir -p "$ROOT/storage/ssh"

# Remove sudoers entry
if [ -f "$SUDOERS_FILE" ]; then
    rm "$SUDOERS_FILE"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Disabled weak sudo: removed passwordless sudo for student" >> "$LOG_FILE"
    echo "[+] Weak sudo configuration removed"
else
    echo "[!] No weak sudo configuration found"
fi

echo "[+] Student user now requires password for sudo access"
```
Removes the sudoers entry, requiring password for sudo access.

**Docker Configuration (docker/Dockerfile, line 51)**:
```dockerfile
echo 'www-data ALL=(ALL) NOPASSWD: /var/www/html/scripts/enable_weak_ssh.sh, /var/www/html/scripts/disable_weak_ssh.sh, /var/www/html/scripts/enable_weak_sudo.sh, /var/www/html/scripts/disable_weak_sudo.sh' >> /etc/sudoers
```
Allows web server user to execute the sudo configuration scripts.

## Testing Procedures

### Test 1: Passwordless Sudo (Vulnerable Mode)
**Prerequisites**: Sudo Misconfiguration CHECKED (vulnerable mode), Docker container running

1. Navigate to `/admin/security-settings.php`
2. Check "Sudo Misconfiguration"
3. Click "Save Security Settings"
4. SSH into container as student: `ssh -p 2222 student@localhost`
5. Password: `password123` (if Weak SSH Credentials enabled) or `Str0ng!Lab#Pass_2026` (if secure)
6. Run: `sudo whoami`
7. **Expected Vulnerable Result**: Returns `root` without password prompt
8. Run: `sudo cat /etc/shadow`
9. **Expected Vulnerable Result**: Displays shadow file contents without password

### Test 2: Passwordless Sudo (Secure Mode)
**Prerequisites**: Sudo Misconfiguration UNCHECKED (secure mode), Docker container running

1. Navigate to `/admin/security-settings.php`
2. Uncheck "Sudo Misconfiguration"
3. Click "Save Security Settings"
4. SSH into container as student: `ssh -p 2222 student@localhost`
5. Run: `sudo whoami`
6. **Expected Secure Result**: Password prompt appears
7. Enter student password
8. **Expected Result**: Command executes only after password verification

### Test 3: Sudo Log Verification
**Prerequisites**: Any toggle state

1. Check sudo toggle log: `cat storage/ssh/sudo_toggle.log`
2. **Expected Result**: Shows timestamped entries for enable/disable actions
3. Verify sudoers file: `cat /etc/sudoers.d/student_weak_sudo` (if exists)
4. **Expected Vulnerable Mode**: Contains `student ALL=(ALL) NOPASSWD:ALL`
5. **Expected Secure Mode**: File does not exist

### Test 4: Privilege Escalation Test
**Prerequisites**: Sudo Misconfiguration CHECKED (vulnerable mode)

1. SSH into container as student
2. Run: `sudo bash`
3. **Expected Vulnerable Result**: Root shell obtained without password
4. Run: `id`
5. **Expected Result**: Shows `uid=0(root) gid=0(root)`
6. Run: `sudo useradd -m testuser`
7. **Expected Vulnerable Result**: New user created without password

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of successful `sudo whoami` without password prompt
- Screenshot of root shell obtained via `sudo bash`
- Screenshot of `/etc/shadow` file contents accessed via sudo
- Screenshot of security settings with checkbox checked
- Screenshot of sudoers file showing `NOPASSWD:ALL` entry

### Secure Mode Evidence
- Screenshot of password prompt when running sudo commands
- Screenshot of failed sudo attempt without password
- Screenshot of security settings with checkbox unchecked
- Verification that sudoers file does not exist
- Screenshot of sudo toggle log showing disable action

## Known Dependencies
**Docker Required**: Sudo access is only available within the Docker container environment. The student user must exist in the container.

**Sudoers File Location**: The vulnerability uses `/etc/sudoers.d/student_weak_sudo` for the sudoers entry. This file must be writable by the web server user (via sudo).

**SSH Access**: Testing requires SSH access to the container on port 2222.

## Remediation
Never grant passwordless sudo access to non-admin users:
- Require password authentication for all sudo commands
- Use specific command restrictions instead of `ALL`
- Implement sudoers rules with explicit command lists
- Use `sudo -k` to force password re-authentication
- Regularly audit sudoers configuration
- Implement sudo logging and monitoring
- Use privilege separation for specific tasks only
- Consider using polkit or similar for fine-grained permissions
- Remove unused sudo entries regularly
- Implement time-based sudo ticket expiration
