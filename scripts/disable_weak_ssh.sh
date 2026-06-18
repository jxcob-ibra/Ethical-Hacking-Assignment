#!/usr/bin/env sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
CRED_FILE="$ROOT/storage/ssh/credentials.txt"
STRONG_PASS="Str0ng!Lab#Pass_2026"

echo "[*] Disabling weak SSH credentials (secure mode)"
mkdir -p "$ROOT/storage/ssh"
printf 'student:%s\n' "$STRONG_PASS" > "$CRED_FILE"

if command -v chpasswd >/dev/null 2>&1 && id student >/dev/null 2>&1; then
    printf 'student:%s\n' "$STRONG_PASS" | chpasswd -c SHA512
    echo "[+] Applied strong password to local SSH account: student"
fi

echo "[+] Strong credential profile applied"
echo "[+] File: storage/ssh/credentials.txt"
