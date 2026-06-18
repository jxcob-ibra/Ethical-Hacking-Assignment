# Stored XSS

- Vulnerability: profile/about fields are rendered raw when `stored_xss` is enabled.
- Attack steps: store `<script>alert(1)</script>` in profile/about.
- Remediation: disable `stored_xss` so output is escaped with `htmlspecialchars`.
- Re-test: use the same payload and refresh viewer page.
- Required screenshots: payload input, execution popup, escaped output after disabling.
- Suggested tools: Browser, Burp Suite.
- Expected before fix: script executes.
- Expected after fix: payload rendered as text.
