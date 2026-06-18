# Weak Password Storage + Database Exposure Testing Guide

## SECTION A - Project Startup

### Prerequisites
- Windows 10/11
- Docker Desktop installed and running
- Git (optional, for cloning)

### Startup Commands

Open PowerShell or Command Prompt in the project directory:

```powershell
# Stop any existing containers
docker compose down

# Rebuild the containers
docker compose build

# Start the containers in detached mode
docker compose up -d
```

### Verify Startup

```powershell
# Check container status
docker compose ps

# View logs (if needed)
docker compose logs -f
```

Expected output: All containers should show "Up" status.

### Access the Application

- **Main Application:** http://localhost:8080
- **Admin Panel:** http://localhost:8080/admin/dashboard.php
- **Default Admin Credentials:**
  - Email: admin@myeduconnect.com
  - Password: password

---

## SECTION B - Security OFF Test

### Step 1: Disable Security Using the Security Toggle

1. Navigate to: http://localhost:8080/login.php
2. Login with admin credentials:
   - Email: admin@myeduconnect.com
   - Password: password
3. Navigate to: http://localhost:8080/admin/security-settings.php
4. Find the "Weak Password Hashing" toggle
5. Enable the toggle (check the checkbox)
6. Click "Save Security Settings"

**Screenshot 1 Required:** Security Settings page with Weak Password Hashing toggle enabled

### Step 2: Create a New User Account

1. Navigate to: http://localhost:8080/register.php
2. Fill in the registration form with test data:
   - **First Name:** Test
   - **Last Name:** Student
   - **Email:** teststudent@example.com
   - **Phone:** 1234567890
   - **Password:** TestPassword123
   - **Confirm Password:** TestPassword123
   - **Student ID:** STU9999
   - **Date of Birth:** 2000-01-01
   - **Grade Level:** Grade 12
   - **Parent Name:** Test Parent
   - **Parent Email:** parent@example.com
   - **Parent Phone:** 9876543210
3. Accept the Terms of Service
4. Click "Create Account"

**Screenshot 2 Required:** Registration form filled with test data

**Screenshot 3 Required:** Registration success message

### Step 3: Verify Password Storage in Database

1. Access the database using Docker:
   ```powershell
   docker compose exec mysql mysql -u root -proot myeduconnect
   ```

2. Query the users table:
   ```sql
   SELECT user_id, email, password, first_name, last_name, role FROM users WHERE email = 'teststudent@example.com';
   ```

3. Verify the password field shows plaintext: `TestPassword123`

**Screenshot 4 Required:** Database query result showing plaintext password

4. Exit the database:
   ```sql
   EXIT;
   ```

### Step 4: Access the Database Exposure Mechanism

1. Navigate to: http://localhost:8080/admin/user-database-exposure.php
2. Verify the page loads and displays user data
3. Verify the password column shows plaintext passwords

**Screenshot 5 Required:** Database exposure page showing all user passwords in plaintext

### Step 5: Verify Vulnerability Exists

Confirm the following:
- [ ] Password stored as plaintext in database
- [ ] Password visible on database exposure page
- [ ] Database exposure page is accessible without authentication
- [ ] All user credentials are exposed

**Expected Results:**
- Database shows: `teststudent@example.com | TestPassword123`
- Exposure page shows password column with readable plaintext
- No authentication required to view exposure page

---

## SECTION C - Security ON Test

### Step 1: Enable Security

1. Navigate to: http://localhost:8080/admin/security-settings.php
2. Find the "Weak Password Hashing" toggle
3. Disable the toggle (uncheck the checkbox)
4. Click "Save Security Settings"

**Screenshot 6 Required:** Security Settings page with Weak Password Hashing toggle disabled

### Step 2: Create Another User Account

1. Navigate to: http://localhost:8080/register.php
2. Fill in the registration form with different test data:
   - **First Name:** Secure
   - **Last Name:** Student
   - **Email:** securestudent@example.com
   - **Phone:** 1234567890
   - **Password:** SecurePassword123
   - **Confirm Password:** SecurePassword123
   - **Student ID:** STU8888
   - **Date of Birth:** 2000-01-01
   - **Grade Level:** Grade 12
   - **Parent Name:** Secure Parent
   - **Parent Email:** secureparent@example.com
   - **Parent Phone:** 9876543210
3. Accept the Terms of Service
4. Click "Create Account"

**Screenshot 7 Required:** Registration form filled with secure test data

**Screenshot 8 Required:** Registration success message

### Step 3: Verify Password Storage

1. Access the database:
   ```powershell
   docker compose exec mysql mysql -u root -proot myeduconnect
   ```

2. Query the users table:
   ```sql
   SELECT user_id, email, password, first_name, last_name, role FROM users WHERE email = 'securestudent@example.com';
   ```

3. Verify the password field shows a bcrypt hash (starts with `$2y$10$`)

**Screenshot 9 Required:** Database query result showing bcrypt hash

4. Exit the database:
   ```sql
   EXIT;
   ```

### Step 4: Attempt Database Exposure Attack

1. Navigate to: http://localhost:8080/admin/user-database-exposure.php
2. Verify access is denied (403 Forbidden or 404 Not Found)

**Screenshot 10 Required:** Access denied message (403 Forbidden or 404 Not Found)

### Step 5: Verify Vulnerability is Remediated

Confirm the following:
- [ ] Password stored as bcrypt hash in database
- [ ] Password is NOT readable (appears as hash)
- [ ] Database exposure page returns 403/404
- [ ] User credentials are protected

**Expected Results:**
- Database shows: `securestudent@example.com | $2y$10$...` (bcrypt hash)
- Exposure page shows: "403 Forbidden" or "404 Not Found"
- Passwords are not visible in plaintext

---

## SECTION D - Validation of Other Vulnerabilities

### Verify SQL Injection Still Functions

1. Enable SQL Injection toggle in Security Settings
2. Attempt SQL injection on login page
3. Verify bypass works as expected
4. Disable SQL Injection toggle
5. Verify bypass no longer works

### Verify XSS Still Functions

1. Enable Stored XSS toggle in Security Settings
2. Inject script in user profile
3. Verify script executes
4. Disable Stored XSS toggle
5. Verify script is sanitized

### Verify IDOR Still Functions

1. Enable IDOR toggle in Security Settings
2. Access another user's profile directly
3. Verify access is granted
4. Disable IDOR toggle
5. Verify access is denied

### Verify Backup File Exposure Still Functions

1. Enable Backup File Exposure toggle in Security Settings
2. Access http://localhost:8080/backups/backup.sql
3. Verify file is downloadable
4. Disable Backup File Exposure toggle
5. Verify file is no longer accessible

### Verify Application Features Remain Unchanged

1. Test normal user registration
2. Test normal user login
3. Test course enrollment
4. Test profile updates
5. Verify all features work correctly

---

## SECTION E - Troubleshooting

### Container Won't Start

```powershell
# Check Docker Desktop is running
# Check port 8080 is not in use
netstat -ano | findstr :8080

# Rebuild containers
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Database Connection Issues

```powershell
# Check MySQL container status
docker compose ps mysql

# View MySQL logs
docker compose logs mysql

# Restart MySQL container
docker compose restart mysql
```

### Exposure Endpoint Not Created

1. Verify the vulnerability toggle is enabled
2. Check file exists in container:
   ```powershell
   docker compose exec app ls -la /var/www/html/admin/user-database-exposure.php
   ```
3. Check PHP error logs:
   ```powershell
   docker compose logs app
   ```

### Password Still Hashed When Vulnerability Enabled

1. Verify the vulnerability toggle is enabled
2. Check the `hashPassword()` function in `app/security/functions.php`
3. Clear browser cache and retry registration
4. Create a new user (existing users won't change)

---

## SECTION F - Cleanup

### Reset Database

```powershell
# Stop containers
docker compose down

# Remove volumes (WARNING: deletes all data)
docker compose down -v

# Restart with fresh database
docker compose up -d
```

### Remove Test Users

```sql
-- Access database
docker compose exec mysql mysql -u root -proot myeduconnect

-- Delete test users
DELETE FROM users WHERE email IN ('teststudent@example.com', 'securestudent@example.com');

-- Exit
EXIT;
```

---

## Summary of Required Screenshots

1. **Security Toggle OFF** - Security Settings page with Weak Password Hashing enabled
2. **User Registration (OFF)** - Registration form filled with test data
3. **Registration Success (OFF)** - Success message after registration
4. **Database Query (OFF)** - Database showing plaintext password
5. **Database Exposure Page** - Exposure page showing all user passwords
6. **Security Toggle ON** - Security Settings page with Weak Password Hashing disabled
7. **User Registration (ON)** - Registration form filled with secure test data
8. **Registration Success (ON)** - Success message after registration
9. **Database Query (ON)** - Database showing bcrypt hash
10. **Access Denied** - 403/404 when accessing exposure page
11. **Before/After Comparison** - Side-by-side comparison of plaintext vs hash

---

## Testing Checklist

### Security OFF Mode
- [ ] Toggle enabled successfully
- [ ] User registered successfully
- [ ] Password stored in plaintext
- [ ] Exposure page accessible
- [ ] Passwords visible on exposure page
- [ ] All credentials exposed

### Security ON Mode
- [ ] Toggle disabled successfully
- [ ] User registered successfully
- [ ] Password stored as bcrypt hash
- [ ] Exposure page returns 403/404
- [ ] Passwords not visible
- [ ] Credentials protected

### Regression Testing
- [ ] SQL Injection still works when enabled
- [ ] XSS still works when enabled
- [ ] IDOR still works when enabled
- [ ] Backup File Exposure still works when enabled
- [ ] Normal application features work correctly
- [ ] No breaking changes introduced

---

## Notes

- The vulnerability toggle controls BOTH password storage AND database exposure
- Existing users with plaintext passwords will be migrated to bcrypt on next successful login
- The exposure endpoint is dynamically created/removed based on toggle state
- All changes are isolated to the `weak_password_hashing` vulnerability
- Other vulnerabilities remain unaffected
