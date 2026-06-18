# SQL Injection

- Vulnerability: SQL query concatenation in login/search when `sql_injection` is enabled.
- Attack steps: submit `' OR '1'='1'--` in login email and any password.
- Remediation: disable `sql_injection` to force prepared statements.
- Re-test: run same payload; expect login failure.
- Required screenshots: toggle ON, exploit request/response, toggle OFF, failed exploit.
- Suggested tools: Browser, Burp Suite, SQLMap.
- Expected before fix: bypass succeeds.
- Expected after fix: bypass blocked.
