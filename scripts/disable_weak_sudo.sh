#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
SUDOERS_FILE="/etc/sudoers.d/student_weak_sudo"
LOG_FILE="$ROOT/storage/ssh/sudo_toggle.log"

echo "[*] Disabling weak sudo configuration (secure mode)"
mkdir -p "$ROOT/storage/ssh"

# Remove the weak sudoers entry
if [ -f "$SUDOERS_FILE" ]; then
    rm -f "$SUDOERS_FILE"
    echo "[+] Removed weak sudo configuration"
else
    echo "[*] Weak sudo configuration was not present"
fi

# Log the change
echo "$(date '+%Y-%m-%d %H:%M:%S') - Disabled weak sudo: student privilege escalation removed" >> "$LOG_FILE"

echo "[+] Secure sudo configuration applied"
echo "[+] Student user cannot escalate privileges"
echo "[+] File: $SUDOERS_FILE (removed)"
