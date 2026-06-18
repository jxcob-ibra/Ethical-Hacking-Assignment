# Weak Password Hashing

- Vulnerability: MD5 hashing used when `weak_password_hashing` is enabled.
- Attack steps: enable toggle, register/update password, inspect DB hash length and type.
- Remediation: disable toggle to use bcrypt (`password_hash`).
- Re-test: create/change password again and verify bcrypt hash prefix.
- Required screenshots: MD5 hash in DB, bcrypt hash in DB, login success in both modes.
- Suggested tools: DB client, Hashcat.
- Expected before fix: MD5 hashes appear.
- Expected after fix: bcrypt hashes appear; md5 hashes auto-migrate on secure login.
