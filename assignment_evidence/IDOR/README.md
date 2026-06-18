# IDOR

- Vulnerability: direct object access without ownership checks when `idor` is enabled.
- Attack steps: open `student/profile.php?id=<other_user_id>` or teacher student detail URLs.
- Remediation: disable `idor` to enforce ownership validation.
- Re-test: retry same ID tampering URL.
- Required screenshots: tampered URL success, toggle OFF, access denied response.
- Suggested tools: Browser, Burp Suite.
- Expected before fix: unauthorized records are viewable.
- Expected after fix: unauthorized access denied.
