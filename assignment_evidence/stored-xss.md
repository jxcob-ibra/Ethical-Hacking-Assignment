# Stored XSS (Cross-Site Scripting) Vulnerability

## Purpose
This vulnerability demonstrates how stored XSS attacks occur when user-supplied input is stored in the database and later displayed to other users without proper output encoding.

## Location
- **Control Panel**: `/admin/security-settings.php` - "XSS Protection" checkbox
- **Database Key**: `stored_xss` in `security_settings` table
- **Environment Variable**: `XSS_ENABLED` in `.env` file
- **Implementation Files**:
  - `index.php` (lines 189-197) - Announcements display
  - `admin/users.php` (lines 202-210) - User list display
  - `admin/announcements.php` (lines 30-39, 243-269) - Announcement creation and display
  - `student/profile.php` (lines 189-196, 242-248) - Profile display and edit
  - `app/security/auth.php` (line 337) - Profile update

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "XSS Protection" checkbox
3. **To enable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Check the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `stored_xss` row

## Implementation Details

### Vulnerable Mode (Toggle Disabled)
When `isVulnerabilityEnabled('stored_xss')` returns true:

**Announcements Display (index.php, lines 189-197)**:
```php
<p><?php echo $announcement['content']; ?></p>
```
Outputs raw HTML without encoding.

**User List Display (admin/users.php, lines 202-210)**:
```php
<td>
    <?php
    if (!isVulnerabilityEnabled('stored_xss')) {
        echo htmlspecialchars($user['about_me'] ?? '');
    } else {
        echo $user['about_me'] ?? '';
    }
    ?>
</td>
```
Outputs raw "About Me" field without encoding.

**Announcement Creation (admin/announcements.php, lines 30-39)**:
```php
if (!isVulnerabilityEnabled('stored_xss'))
{
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
}
else
{
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
}
```
Stores raw input without sanitization.

**Announcement Display (admin/announcements.php, lines 243-269)**:
```php
if (!isVulnerabilityEnabled('stored_xss'))
{
    echo htmlspecialchars($announcement['title']);
}
else
{
    echo $announcement['title'];
}
```
Outputs raw HTML without encoding.

**Profile Display (student/profile.php, lines 189-196)**:
```php
<p><strong>About Me:</strong>
    <?php
    if (!isVulnerabilityEnabled('stored_xss')) {
        echo htmlspecialchars($student['about_me'] ?? '');
    } else {
        echo $student['about_me'] ?? '';
    }
    ?>
</p>
```
Outputs raw "About Me" field without encoding.

**Profile Update (app/security/auth.php, line 337)**:
```php
$query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, about_me = ?  WHERE user_id = ?";
dbUpdate($query, [
    sanitize($data['first_name']),
    sanitize($data['last_name']),
    sanitize($data['phone'] ?? ''),
    sanitize($data['address'] ?? ''),
    !isVulnerabilityEnabled('stored_xss') ? sanitize($data['about_me'] ?? '') : ($data['about_me'] ?? ''),
    $userId
]);
```
Stores raw input when XSS is enabled.

### Secure Mode (Toggle Enabled)
When `isVulnerabilityEnabled('stored_xss')` returns false:

**All Output Points**:
```php
echo htmlspecialchars($data);
```
Uses `htmlspecialchars()` to convert special characters to HTML entities.

**Input Points**:
```php
$data = sanitize($input);
```
Uses `sanitize()` function which applies `htmlspecialchars()`.

## Testing Procedures

### Test 1: Stored XSS in Announcements
**Prerequisites**: XSS Protection UNCHECKED (vulnerable mode)

1. Login as admin: `admin@myeduconnect.com` / `Admin123!`
2. Navigate to `/admin/announcements.php`
3. Enter title: "Test Announcement"
4. Enter content payload: `<script>alert('XSS')</script>`
5. Click "Create Announcement"
6. Navigate to `/index.php` (home page)
7. **Expected Vulnerable Result**: JavaScript alert executes
8. **Expected Secure Result**: Content displayed as text: `<script>alert('XSS')</script>`

### Test 2: Stored XSS in User Profile
**Prerequisites**: XSS Protection UNCHECKED (vulnerable mode)

1. Login as student: `student@myeduconnect.com` / `Student123!`
2. Navigate to `/student/profile.php`
3. In "About Me" field, enter payload: `<img src=x onerror=alert('XSS')>`
4. Click "Update Profile"
5. Navigate to `/admin/users.php` (as admin)
6. **Expected Vulnerable Result**: JavaScript alert executes when viewing user list
7. **Expected Secure Result**: Content displayed as text: `<img src=x onerror=alert('XSS')>`

### Test 3: Stored XSS in Announcement Title
**Prerequisites**: XSS Protection UNCHECKED (vulnerable mode)

1. Login as admin
2. Navigate to `/admin/announcements.php`
3. Enter title payload: `<script>alert('Title XSS')</script>`
4. Enter content: "Test content"
5. Click "Create Announcement"
6. Navigate to `/index.php`
7. **Expected Vulnerable Result**: JavaScript alert executes from title
8. **Expected Secure Result**: Title displayed as text

### Test 4: Secure Mode Verification
**Prerequisites**: XSS Protection CHECKED (secure mode)

1. Login as admin
2. Navigate to `/admin/announcements.php`
3. Enter title: "Test"
4. Enter content payload: `<script>alert('XSS')</script>`
5. Click "Create Announcement"
6. Navigate to `/index.php`
7. **Expected Result**: Content displayed as escaped text, no alert
8. Login as student
9. Navigate to `/student/profile.php`
10. Enter payload in "About Me": `<img src=x onerror=alert('XSS')>`
11. Click "Update Profile"
12. Navigate to `/admin/users.php`
13. **Expected Result**: Content displayed as escaped text, no alert

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of JavaScript alert executing on home page
- Screenshot of JavaScript alert executing in admin user list
- Screenshot of announcement creation with XSS payload
- Database screenshot showing raw HTML stored

### Secure Mode Evidence
- Screenshot of escaped HTML displayed as text
- Screenshot of no JavaScript alerts executing
- Database screenshot showing encoded entities stored
- Browser developer tools showing HTML entities in DOM

## Known Dependencies
**Independently Demonstrable**: Yes. XSS protection is implemented independently without dependencies on other vulnerability toggles.

## Remediation
Always apply output encoding when displaying user-supplied data:
- Use `htmlspecialchars()` for HTML context
- Use `json_encode()` for JavaScript context
- Use `urlencode()` for URL context
- Validate and sanitize input on submission
- Implement Content Security Policy (CSP) headers
- Use framework templating engines that auto-escape (Twig, Blade, etc.)
