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
