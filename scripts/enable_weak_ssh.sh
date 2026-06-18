#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
CRED_FILE="$ROOT/storage/ssh/credentials.txt"
WEAK_PASS="password123"

echo "[*] Enabling weak SSH credentials (demo mode)"
mkdir -p "$ROOT/storage/ssh"
printf 'student:%s\n' "$WEAK_PASS" > "$CRED_FILE"

if command -v chpasswd >/dev/null 2>&1 && id student >/dev/null 2>&1; then
    printf 'student:%s\n' "$WEAK_PASS" | chpasswd -c SHA512
    echo "[+] Applied weak password to local SSH account: student"
fi

echo "[+] Weak credentials set to student/$WEAK_PASS"
echo "[+] File: storage/ssh/credentials.txt"
echo "[+] SSH endpoint (if enabled): localhost:2222"
