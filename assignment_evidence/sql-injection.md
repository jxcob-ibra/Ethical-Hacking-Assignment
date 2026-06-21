# SQL Injection Vulnerability

## Purpose
This vulnerability demonstrates how SQL injection attacks occur when user input is concatenated directly into SQL queries without proper parameterization or escaping.

## Location
- **Control Panel**: `/admin/security-settings.php` - "SQL Injection Protection" checkbox
- **Database Key**: `sql_injection` in `security_settings` table
- **Environment Variable**: `SQLI_ENABLED` in `.env` file
- **Implementation Files**:
  - `app/security/auth.php` (lines 14-44) - Login function
  - `app/security/functions.php` (lines 319-347) - searchCourses function
  - `api/courses.php` (lines 89-123) - REST API endpoint

## How to Enable/Disable
1. Navigate to `/admin/security-settings.php`
2. Locate the "SQL Injection Protection" checkbox
3. **To enable vulnerability**: Uncheck the checkbox and click "Save Security Settings"
4. **To disable vulnerability**: Check the checkbox and click "Save Security Settings"
5. The toggle updates the `enabled` column in the `security_settings` table for the `sql_injection` row

## Implementation Details

### Vulnerable Mode (Toggle Disabled)
When `isVulnerabilityEnabled('sql_injection')` returns true:

**Login (app/security/auth.php, lines 34-44)**:
```php
$query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
$db = Database::getInstance()->getConnection();
$stmt = $db->query($query);
$user = $stmt->fetch();
```
Uses raw string concatenation without parameterization.

**Course Search (app/security/functions.php, lines 319-332)**:
```php
$query = "SELECT course_id, title, description, category, price 
          FROM courses 
          WHERE title LIKE '%" . $keyword . "%' AND status = 'published'";
$stmt = $db->query($query);
```
Concatenates search keyword directly into query.

**REST API (api/courses.php, lines 89-109)**:
```php
$query = "SELECT course_id, title, description, category, price 
          FROM courses WHERE title LIKE '%" . $search . "%' AND status = 'published'";
$stmt = $db->query($query);
```
Uses concatenated query in API endpoint.

### Secure Mode (Toggle Enabled)
When `isVulnerabilityEnabled('sql_injection')` returns false:

**Login (app/security/auth.php, lines 20-26)**:
```php
$user = dbSelectOne(
    "SELECT * FROM users WHERE email = ? AND status = 'active'",
    [$email]
);
if (!$user || !verifyPassword($password, $user['password'])) {
    return ['success' => false, 'message' => 'Invalid email or password'];
}
```
Uses parameterized query with prepared statements.

**Course Search (app/security/functions.php, lines 334-347)**:
```php
$query = "SELECT course_id, title, description, category, price 
          FROM courses 
          WHERE title LIKE ? AND status = 'published'";
$searchTerm = "%" . $keyword . "%";
$courses = dbSelect($query, [$searchTerm]);
```
Uses parameterized query with bound parameters.

**REST API (api/courses.php, lines 111-123)**:
```php
$query = "SELECT course_id, title, description, category, price 
          FROM courses WHERE title LIKE :search AND status = 'published'";
$stmt = $db->prepare($query);
$searchTerm = "%" . $search . "%";
$stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
$stmt->execute();
```
Uses PDO prepared statements with named parameters.

## Testing Procedures

### Test 1: Login Bypass via SQL Injection
**Prerequisites**: SQL Injection Protection UNCHECKED (vulnerable mode)

1. Navigate to `/login.php`
2. Enter payload in email field: `' OR '1'='1`
3. Enter any password
4. Click "Login"
5. **Expected Vulnerable Result**: Login succeeds (bypasses authentication)
6. **Expected Secure Result**: Login fails with "Invalid email or password"

### Test 2: Course Search SQL Injection
**Prerequisites**: SQL Injection Protection UNCHECKED (vulnerable mode)

1. Navigate to `/courses.php`
2. Enter payload in search field: `' UNION SELECT 1,2,3,4,5-- `
3. Click "Search"
4. **Expected Vulnerable Result**: SQL error or unexpected data returned
5. **Expected Secure Result**: No results or safe search behavior

### Test 3: REST API SQL Injection
**Prerequisites**: SQL Injection Protection UNCHECKED (vulnerable mode)

1. Access: `http://localhost:8080/api/courses.php?search=' OR '1'='1`
2. **Expected Vulnerable Result**: Returns all courses or SQL error
3. **Expected Secure Result**: Returns empty results or safe search

### Test 4: Secure Mode Verification
**Prerequisites**: SQL Injection Protection CHECKED (secure mode)

1. Navigate to `/login.php`
2. Enter payload: `' OR '1'='1`
3. Enter any password
4. Click "Login"
5. **Expected Result**: Login fails with "Invalid email or password"
6. Navigate to `/courses.php`
7. Enter payload: `' UNION SELECT 1,2,3,4,5-- `
8. Click "Search"
9. **Expected Result**: No SQL injection occurs, safe query execution

## Expected Results

### Vulnerable Mode Evidence
- Screenshot of successful login with SQLi payload
- Screenshot of SQL error message from course search
- Screenshot of API response showing injection success
- Database logs showing concatenated queries

### Secure Mode Evidence
- Screenshot of failed login attempt with SQLi payload
- Screenshot of safe course search results
- Screenshot of API response showing parameterized query
- Database logs showing prepared statements

## Known Dependencies
**Design Flaw**: SQL Injection toggle is coupled with Weak Authentication toggle in `app/security/auth.php` (lines 47-54). When Weak Auth is disabled, password verification is only skipped if SQLi is also disabled. This creates a chained attack scenario.

## Remediation
Always use parameterized queries or prepared statements:
- Use PDO prepared statements with `prepare()` and `bindParam()`
- Never concatenate user input directly into SQL queries
- Validate and sanitize all user inputs
- Use ORM frameworks that handle SQL escaping automatically
