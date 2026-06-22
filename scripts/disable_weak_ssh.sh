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
