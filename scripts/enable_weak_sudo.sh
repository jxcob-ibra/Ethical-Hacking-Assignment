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
