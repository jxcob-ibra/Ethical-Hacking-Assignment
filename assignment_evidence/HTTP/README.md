# HTTP API Communication

- Vulnerability: API base URL uses plain HTTP when `http_api_communication` is enabled.
- Attack steps: enable toggle and call `http://localhost:8080/api/ping.php`; capture with Wireshark.
- Remediation: disable toggle and call `https://localhost:8443/api/ping.php`.
- Re-test: compare packet visibility before/after.
- Required screenshots: HTTP request capture, HTTPS request capture, toggle state.
- Suggested tools: Wireshark, Browser, Curl.
- Expected before fix: traffic is plaintext HTTP.
- Expected after fix: traffic is TLS-encrypted HTTPS.
