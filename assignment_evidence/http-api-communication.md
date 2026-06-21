# HTTP API Communication Vulnerability

## Purpose
This vulnerability demonstrates how unencrypted HTTP communication can be intercepted, allowing attackers to capture sensitive data in transit. When enabled, the API endpoint accepts HTTP connections without enforcing HTTPS.

## Location
- **Control Panel**: `/admin/security-settings.php` - "HTTP API Communication" checkbox
- **Database Key**: `http_api_communication` in `security_settings` table
- **Environment Variable**: Not directly mapped (uses database toggle)
- **Implementation Files**:
  - `app/security/functions.php` (lines 70-77) - enforceApiTransportPolicy function
  - `api/courses.php` (line 12) - Calls enforceApiTransportPolicy

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "HTTP API Communication" checkbox
3. **To enable vulnerability**: Check the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `http_api_communication` row

## Implementation Details

### Vulnerable Mode (Toggle Enabled)
When `isVulnerabilityEnabled('http_api_communication')` returns true:

**API Transport Policy Function (app/security/functions.php, lines 70-77)**:
```php
function enforceApiTransportPolicy() {
    if (isVulnerabilityEnabled('http_api_communication')) {
        // VULNERABLE - Allow HTTP connections
        return;
    } else {
        // SECURE - Enforce HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            header('HTTP/1.1 403 Forbidden');
            die('HTTPS required for API access');
        }
    }
}
```
Allows HTTP connections without enforcing HTTPS encryption.

**API Endpoint (api/courses.php, line 12)**:
```php
enforceApiTransportPolicy();
```
Called at the start of the API endpoint to check transport security.

### Secure Mode (Toggle Disabled)
When `isVulnerabilityEnabled('http_api_communication')` returns false:

**API Transport Policy Function**:
```php
function enforceApiTransportPolicy() {
    if (isVulnerabilityEnabled('http_api_communication')) {
        return;
    } else {
        // SECURE - Enforce HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            header('HTTP/1.1 403 Forbidden');
            die('HTTPS required for API access');
        }
    }
}
```
Returns 403 Forbidden if connection is not over HTTPS.

## Testing Procedures

### Test 1: HTTP API Access (Vulnerable Mode)
**Prerequisites**: HTTP API Communication CHECKED (vulnerable mode)

1. Navigate to `/admin/security-settings.php`
2. Check "HTTP API Communication"
3. Click "Save Security Settings"
4. Access API via HTTP: `http://localhost:8080/api/courses.php`
5. **Expected Vulnerable Result**: API returns JSON data successfully
6. Use Wireshark or tcpdump to capture traffic
7. **Expected Result**: Can see unencrypted JSON data in network capture

### Test 2: HTTP API Access (Secure Mode)
**Prerequisites**: HTTP API Communication UNCHECKED (secure mode)

1. Navigate to `/admin/security-settings.php`
2. Uncheck "HTTP API Communication"
3. Click "Save Security Settings"
4. Access API via HTTP: `http://localhost:8080/api/courses.php`
5. **Expected Secure Result**: Returns 403 Forbidden with message "HTTPS required for API access"
6. Access API via HTTPS: `https://localhost:8443/api/courses.php` (if SSL configured)
7. **Expected Result**: API returns JSON data successfully

### Test 3: Traffic Interception with Wireshark
**Prerequisites**: HTTP API Communication CHECKED (vulnerable mode)

1. Start Wireshark and capture traffic on port 8080
2. Access API: `http://localhost:8080/api/courses.php`
3. Stop capture and analyze HTTP packets
4. **Expected Vulnerable Result**: Can see full JSON response in clear text
5. Look for sensitive data: user emails, course information, vulnerability statuses

### Test 4: HTTPS Verification
**Prerequisites**: HTTP API Communication UNCHECKED (secure mode), SSL configured

1. Access API via HTTPS: `https://localhost:8443/api/courses.php`
2. **Expected Result**: API returns JSON data successfully
3. Use Wireshark to capture traffic on port 8443
4. **Expected Result**: Traffic is encrypted (TLS), cannot read JSON data

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of successful API response via HTTP
- Screenshot of Wireshark capture showing unencrypted JSON data
- Screenshot of security settings with checkbox checked
- Network capture file showing clear text API data

### Secure Mode Evidence
- Screenshot of 403 Forbidden error when accessing via HTTP
- Screenshot of successful API response via HTTPS
- Screenshot of security settings with checkbox unchecked
- Wireshark capture showing encrypted TLS traffic

## Known Dependencies
**SSL Configuration**: For secure mode testing, the Docker container includes a self-signed SSL certificate configured in `docker/Dockerfile` (lines 35-43). HTTPS is available on port 443 (mapped to host port 8443 in docker-compose.yml if configured).

**API Endpoint**: The vulnerability specifically affects the REST API endpoint at `/api/courses.php`. Other endpoints may have different transport security implementations.

## Remediation
Always enforce HTTPS for API communication:
- Redirect all HTTP traffic to HTTPS
- Use HSTS (HTTP Strict Transport Security) headers
- Implement proper SSL/TLS configuration
- Use strong cipher suites and protocols
- Keep SSL certificates valid and up-to-date
- Disable weak protocols (SSLv2, SSLv3, TLS 1.0, TLS 1.1)
- Implement certificate pinning for mobile apps
- Use API keys or OAuth tokens for authentication
- Encrypt sensitive data even when using HTTPS (defense in depth)
