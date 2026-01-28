# Road and Infrastructure Department Login System

## Overview
This login system is specifically configured for the LGU Road and Infrastructure Department, connecting to the `lgu_road_infra` database with comprehensive security features and user management.

## Files Created

### Core Configuration
- `config/database.php` - Database connection class with error handling
- `config/auth.php` - Authentication and session management
- `config/security.php` - Security functions and validations

### Login System
- `login.php` - Main login authentication script (in road_infra_dept root)
- `user_and_access_management_module/login.html` - Updated login form with AJAX submission
- `logout.php` - User logout handler

## Database Setup

1. Execute the provided SQL script to create the `lgu_road_infra` database and tables:
   ```sql
   -- Run the complete SQL schema provided
   ```

2. Default admin user:
   - Email: `admin@lgu.gov.ph`
   - Password: `admin123`
   - Role: `admin`

## Security Features

### Authentication Security
- **Rate Limiting**: 5 failed attempts lock account for 15 minutes
- **Password Hashing**: Uses secure password verification
- **Session Management**: Secure session handling with expiration
- **Input Validation**: Comprehensive input sanitization
- **Login Attempt Logging**: Tracks all login attempts for security monitoring

### Login Protection
- Email format validation
- Account status verification (active, email verified)
- Suspicious activity detection
- IP-based monitoring

## User Roles & Redirects

After successful login, users are redirected based on their role:
- `admin` → `../admin/dashboard.php`
- `lgu_officer` → `../lgu-portal/dashboard.html`
- `engineer` → `dashboard.html` (within road_infra_dept)
- `citizen` → `../citizen.html`

## Usage

### For Protected Pages
Include the auth configuration at the top of protected pages:
```php
<?php
require_once 'config/auth.php';

// Require user to be logged in
$auth->requireLogin();

// Require specific role
$auth->requireRole('engineer');

// Or require any of multiple roles
$auth->requireAnyRole(['engineer', 'admin']);
?>
```

### Logout Link
```html
<a href="logout.php">Logout</a>
```

### Current User Information
```php
// Get user details
$userId = $auth->getUserId();
$userRole = $auth->getUserRole();
$userName = $auth->getUserFullName();
$userEmail = $auth->getUserEmail();

// Check roles
if ($auth->isEngineer()) {
    // Engineer-only content
}
```

## Database Tables

### Users Table
- Stores user credentials and profile information
- Includes status tracking (pending, active, inactive, suspended)
- Email verification flag
- Role-based access control

### Security Tables
- `user_sessions` - Active session tracking
- `login_attempts` - Failed/successful login logging
- `user_activity_log` - User activity auditing
- `password_resets` - Password reset token management

## Configuration

### Database Connection
Edit `config/database.php` to match your database settings:
```php
private $host = 'localhost';
private $username = 'root';
private $password = '';
private $database = 'lgu_road_infra';
```

### Security Settings
Modify security parameters in `config/security.php`:
```php
private static $maxLoginAttempts = 5;
private static $loginLockoutTime = 900; // 15 minutes
```

## File Structure
```
road_and_infra_dept/
├── config/
│   ├── database.php
│   ├── auth.php
│   └── security.php
├── user_and_access_management_module/
│   ├── login.html (updated)
│   └── ...
├── login.php
├── logout.php
└── [other modules...]
```

## Testing

1. Ensure the `lgu_road_infra` database is created and populated
2. Test login with default admin credentials
3. Verify role-based redirects work correctly
4. Test security features (invalid login, rate limiting, etc.)

## Notes

- All passwords are hashed using secure algorithms
- Sessions expire after 24 hours of inactivity
- Failed login attempts are logged for security monitoring
- The system automatically cleans up expired sessions
- Engineers are redirected to the department dashboard
- Other roles are redirected to appropriate main site dashboards

## Troubleshooting

### Common Issues
1. **Database Connection**: Check database credentials in `config/database.php`
2. **Session Issues**: Ensure PHP session directory is writable
3. **Redirect Failures**: Verify dashboard files exist at specified paths
4. **Permission Issues**: Check file permissions in the config directory

### Error Logging
All errors are logged to PHP error log. Check your server's error logs for detailed debugging information.

## Integration with Main Site

This login system is designed to work alongside the main LGU portal while maintaining separate authentication for the Road and Infrastructure Department. Users with appropriate roles can access both systems seamlessly.
