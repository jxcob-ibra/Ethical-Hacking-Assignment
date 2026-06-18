# Backup File Exposure

- Vulnerability: backup file is exposed at `/backups/backup.sql` when enabled.
- Attack steps: enable `backup_file_exposure`, browse the backup URL.
- Remediation: disable vulnerability to remove public copy and keep backup in storage.
- Re-test: request same URL after disable.
- Required screenshots: accessible backup file, toggle OFF, inaccessible backup file.
- Suggested tools: Browser, Curl.
- Expected before fix: backup file is downloadable.
- Expected after fix: file is not accessible from web path.
