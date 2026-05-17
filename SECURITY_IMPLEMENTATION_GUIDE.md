# Security Implementation Guide

This document outlines the advanced security features implemented for the Scholaria Laravel project.

## IMPLEMENTED FEATURES

### 1. Multi-Factor Authentication (MFA)
- Google Authenticator (TOTP) integration
- QR code generation for easy setup
- Encrypted secret key storage
- 2FA verification during login

### 2. Role-Based Access Control (RBAC)
- Already implemented using Spatie Laravel Permission
- Roles: Admin, Teacher, Student
- Granular permissions system

### 3. Database Security
- Passwords already using Hash::make()
- Encrypted 2FA secrets using Crypt::encryptString()
- Input validation on all forms

### 4. Automated Database Backup
- Daily database backups at 2:00 AM
- Stored in `storage/app/backups`
- Keeps last 10 backups only

### 5. Security Hardening
- Login throttling (5 attempts per minute)
- Session regeneration after login
- CSRF protection enabled by default
- Rate limiting configured

## FILES CREATED/MODIFIED

### MFA Implementation
1. **Database Migration**
   - `database/migrations/2026_04_09_143438_add_2fa_fields_to_users_table.php`
   - Adds `google2fa_secret` and `google2fa_enabled` fields to users table

2. **User Model**
   - `app/Models/User.php`
   - Added MFA fields to fillable and hidden arrays

3. **Controller**
   - `app/Http/Controllers/TwoFactorAuthController.php`
   - Handles MFA setup, enable, disable, and verification

4. **Middleware**
   - `app/Http/Middleware/TwoFactorMiddleware.php`
   - Checks if 2FA is verified before allowing access

5. **Views**
   - `resources/views/auth/2fa-setup.blade.php`
   - `resources/views/auth/2fa-verify.blade.php`

6. **Routes**
   - Added in `routes/web.php`
   - `/2fa/setup` - Setup page
   - `/2fa/enable` - Enable 2FA
   - `/2fa/disable` - Disable 2FA
   - `/2fa/verify` - Verify 2FA code

### Backup System
1. **Command**
   - `app/Console/Commands/BackupDatabase.php`
   - Command: `php artisan backup:database`

2. **Scheduling**
   - `routes/console.php`
   - Scheduled daily at 2:00 AM

### Security Hardening
1. **Rate Limiting**
   - `bootstrap/app.php` - Added throttle configuration
   - `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Added login throttling

2. **Middleware Registration**
   - `bootstrap/app.php` - Registered `2fa` middleware alias

## MANUAL STEPS REQUIRED

### Step 1: Run Database Migrations
```bash
c:\xampp\php\php.exe artisan migrate
```

### Step 3: Publish Google2FA Configuration (Optional)
If you need to customize Google2FA settings:
```bash
c:\xampp\php\php.exe artisan vendor:publish --provider="PragmaRX\Google2FA\ServiceProvider"
```

### Step 4: Clear Configuration Cache
```bash
c:\xampp\php\php.exe artisan config:clear
c:\xampp\php\php.exe artisan cache:clear
```

### Step 5: Test the Application
```bash
c:\xampp\php\php.exe artisan serve
```

## USAGE INSTRUCTIONS

### Enabling 2FA for Users
1. Login to the application
2. Navigate to `/2fa/setup`
3. Scan the QR code with Google Authenticator app
4. Enter the verification code
5. Click "Enable 2FA"

### Disabling 2FA
Users can disable 2FA from their settings by providing their password.

### Login Flow with 2FA
1. User enters email/username and password
2. If 2FA is enabled, user is redirected to `/2fa/verify`
3. User enters the 6-digit code from their authenticator app
4. If valid, user is redirected to dashboard

### Manual Database Backup
To manually backup the database:
```bash
c:\xampp\php\php.exe artisan backup:database
```

Backups are stored in: `storage/app/backups/`

## ROUTES ADDED

| Route | Method | Description | Middleware |
|-------|--------|-------------|------------|
| /2fa/setup | GET | Show 2FA setup page | auth |
| /2fa/enable | POST | Enable 2FA | auth |
| /2fa/disable | POST | Disable 2FA | auth |
| /2fa/verify/show | GET | Show 2FA verification page | auth |
| /2fa/verify | POST | Verify 2FA code | auth |

## MIDDLEWARE

- `2fa` - Check if user has verified 2FA (if enabled)

Apply to routes that require 2FA verification:
```php
Route::middleware(['auth', '2fa'])->group(function () {
    // Protected routes
});
```

## SECURITY NOTES

1. **2FA Secret Encryption**: All 2FA secrets are encrypted using Laravel's Crypt facade
2. **Rate Limiting**: Login attempts are limited to 5 per minute per IP/username
3. **Session Security**: Session is regenerated after successful login
4. **Backup Retention**: Only last 10 backups are kept to save disk space
5. **CSRF Protection**: All forms include CSRF tokens automatically

## RBAC EXISTING SETUP

The project already has RBAC implemented using Spatie Laravel Permission:

**Roles:**
- Admin
- Teacher
- Student

**Permissions:**
- users.view, users.create, users.update, users.delete
- roles.manage, permissions.manage
- courses.view, courses.create, courses.update, courses.delete
- lessons.view, lessons.create, lessons.update, lessons.delete

**Seed the roles and permissions:**
```bash
c:\xampp\php\php.exe artisan db:seed --class=RolesAndPermissionsSeeder
```

## TROUBLESHOOTING

### QR Code Not Displaying
- Ensure `simplesoftwareio/simple-qrcode` is installed
- Check GD library is enabled in PHP

### Backup Command Fails
- Ensure mysqldump is in your system PATH
- Check database credentials in `.env` file
- Verify `storage/app/backups` directory is writable

### 2FA Verification Fails
- Check system time is synchronized
- Ensure Google Authenticator app time is correct
- Verify the secret key was saved correctly

## TESTING

1. Create a test user
2. Enable 2FA for the test user
3. Logout and login again
4. Verify 2FA code is required
5. Test with invalid codes
6. Test disabling 2FA

## PRODUCTION DEPLOYMENT CHECKLIST

- [ ] Run all migrations
- [ ] Install all required packages
- [ ] Seed roles and permissions
- [ ] Set up cron job for scheduler (if not using Laravel Forge)
- [ ] Configure backup storage permissions
- [ ] Test 2FA flow
- [ ] Test backup command
- [ ] Review rate limiting settings
- [ ] Enable HTTPS
- [ ] Set secure APP_KEY in production

## CRON JOB SETUP (For Backup Scheduling)

Add this to your server's crontab:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This will run the Laravel scheduler every minute, which will trigger the daily backup at 2:00 AM.
