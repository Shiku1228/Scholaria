# Sample Users with RBAC Permissions Guide

This guide provides detailed information about the sample users created for testing the Role-Based Access Control (RBAC) system in Scholaria.

## Sample User Accounts

### Admin Users

#### 1. Super Admin User
- **Email:** `superadmin@scholaria.com`
- **Password:** `password123`
- **Employee ID:** SA001
- **Department:** IT Administration
- **Role:** Super Admin
- **Description:** Full system access with all permissions

**Permissions:**
- ✅ All system permissions (users, courses, lessons, reports, settings)
- ✅ User management (create, edit, delete, suspend)
- ✅ Role and permission management
- ✅ System configuration
- ✅ Report viewing and export
- ✅ Content management (courses, lessons)

**Access Level:** 🔴 **FULL ACCESS** - Can access all system features and settings

---

#### 2. Content Admin User
- **Email:** `contentadmin@scholaria.com`
- **Password:** `password123`
- **Employee ID:** CA001
- **Department:** Academic Content
- **Role:** Content Admin
- **Description:** Manages courses, lessons, and basic user operations

**Permissions:**
- ✅ View and edit users
- ✅ Course management (view, create, edit, delete)
- ✅ Lesson management (view, create, edit, delete)
- ❌ User suspension
- ❌ Role/permission management
- ❌ System settings
- ❌ Reports access

**Access Level:** 🟡 **CONTENT FOCUSED** - Can manage educational content and basic user operations

---

#### 3. User Admin User
- **Email:** `useradmin@scholaria.com`
- **Password:** `password123`
- **Employee ID:** UA001
- **Department:** Human Resources
- **Role:** User Admin
- **Description:** Manages user accounts, roles, and permissions

**Permissions:**
- ✅ User management (view, create, edit, delete, suspend)
- ❌ Course/lesson management
- ❌ System settings
- ❌ Reports access
- ❌ Content management

**Access Level:** 🟡 **USER MANAGEMENT** - Focused on user account administration

---

#### 4. Report Admin User
- **Email:** `reportadmin@scholaria.com`
- **Password:** `password123`
- **Employee ID:** RA001
- **Department:** Analytics
- **Role:** Report Admin
- **Description:** Views reports, analytics, and exports data

**Permissions:**
- ✅ Report viewing
- ✅ Report export
- ✅ User viewing (read-only)
- ❌ User editing/creation/deletion
- ❌ Course/lesson management
- ❌ System settings
- ❌ Content management

**Access Level:** 🟢 **READ-ONLY ANALYTICS** - Can view and export reports, limited user viewing

---

#### 5. Settings Admin User
- **Email:** `settingsadmin@scholaria.com`
- **Password:** `password123`
- **Employee ID:** SA002
- **Department:** System Administration
- **Role:** Settings Admin
- **Description:** Manages system settings and configurations

**Permissions:**
- ✅ Settings viewing
- ✅ Settings editing
- ✅ User viewing (read-only)
- ❌ User editing/creation/deletion
- ❌ Course/lesson management
- ❌ Reports access
- ❌ Content management

**Access Level:** 🟡 **SYSTEM CONFIGURATION** - Can manage system settings and view user information

---

### Non-Admin Users

#### 6. Teacher Sample
- **Email:** `teacher@scholaria.com`
- **Password:** `password123`
- **Employee ID:** T001
- **Department:** Computer Science
- **Specialization:** Web Development
- **Role:** Teacher
- **Description:** Sample teacher account for testing teacher permissions

**Permissions:**
- ✅ Course management (own courses)
- ✅ Lesson management (own lessons)
- ✅ Student enrollment management
- ✅ Grade management
- ❌ User management
- ❌ System settings
- ❌ Reports (except class reports)

**Access Level:** 🔵 **EDUCATIONAL CONTENT** - Can manage own courses and student interactions

---

#### 7. Student Sample
- **Email:** `student@scholaria.com`
- **Password:** `password123`
- **Student Number:** S20240001
- **Year Level:** 3
- **Section:** BSIT-3A
- **Course:** Bachelor of Science in Information Technology
- **Role:** Student
- **Description:** Sample student account for testing student permissions

**Permissions:**
- ✅ Course enrollment
- ✅ Lesson viewing
- ✅ Assignment submission
- ✅ Grade viewing
- ❌ Course creation/editing
- ❌ User management
- ❌ System settings

**Access Level:** 🔵 **LEARNER ACCESS** - Can participate in courses and view own academic information

---

## Permission Matrix

| Permission | Super Admin | Content Admin | User Admin | Report Admin | Settings Admin | Teacher | Student |
|------------|-------------|---------------|------------|--------------|----------------|---------|---------|
| **Users** |
| users.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (limited) | ❌ |
| users.create | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| users.edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| users.delete | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| users.suspend | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Content** |
| courses.view | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (own) | ✅ (enrolled) |
| courses.create | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| courses.edit | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (own) | ❌ |
| courses.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| lessons.view | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (own) | ✅ (enrolled) |
| lessons.create | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (own) | ❌ |
| lessons.edit | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ (own) | ❌ |
| lessons.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **System** |
| roles.manage | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| permissions.manage | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| settings.view | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| settings.edit | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Reports** |
| reports.view | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ (class) | ❌ |
| reports.export | ✅ | ❌ | ❌ | ✅ | ❌ | ✅ (class) | ❌ |

---

## How to Use These Sample Users

### 1. Running the Seeder
To create these sample users, run:
```bash
php artisan db:seed --class=SampleUsersSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

### 2. Testing Scenarios

#### Super Admin Testing
- Login as `superadmin@scholaria.com`
- Verify access to all admin panels
- Test role assignment and permission management
- Verify system settings access

#### Content Admin Testing
- Login as `contentadmin@scholaria.com`
- Test course and lesson creation/editing
- Verify user viewing but not editing capabilities
- Confirm restricted access to settings and reports

#### User Admin Testing
- Login as `useradmin@scholaria.com`
- Test user creation, editing, and suspension
- Verify restricted access to content management
- Confirm inability to access system settings

#### Report Admin Testing
- Login as `reportadmin@scholaria.com`
- Test report viewing and export functionality
- Verify read-only user access
- Confirm restricted access to other admin functions

#### Settings Admin Testing
- Login as `settingsadmin@scholaria.com`
- Test system settings modification
- Verify read-only user access
- Confirm restricted access to content and reports

#### Teacher Testing
- Login as `teacher@scholaria.com`
- Test course creation and management
- Verify student enrollment capabilities
- Confirm restricted admin access

#### Student Testing
- Login as `student@scholaria.com`
- Test course enrollment and lesson viewing
- Verify assignment submission capabilities
- Confirm restricted access to admin functions

### 3. Security Testing
- Verify users cannot access features outside their permission set
- Test middleware protection on restricted routes
- Confirm proper role-based UI element visibility
- Validate API endpoint permissions

---

## Important Notes

### Security
- All sample users use the same default password: `password123`
- **IMPORTANT:** Change these passwords in production environments
- These accounts should be removed or secured before going live

### Customization
- You can modify the `SampleUsersSeeder.php` to create different test scenarios
- Add more sample users with specific permission combinations as needed
- Update the documentation accordingly

### Troubleshooting
- If users don't have expected permissions, ensure the `GranularAdminRolesAndPermissionsSeeder` runs first
- Check that role assignments are properly synced
- Verify middleware and policy configurations

---

## Quick Reference Cheat Sheet

| Role | Email | Primary Function | Access Level |
|------|-------|------------------|--------------|
| Super Admin | superadmin@scholaria.com | System Administration | 🔴 Full |
| Content Admin | contentadmin@scholaria.com | Content Management | 🟡 Content |
| User Admin | useradmin@scholaria.com | User Management | 🟡 Users |
| Report Admin | reportadmin@scholaria.com | Analytics & Reports | 🟢 Reports |
| Settings Admin | settingsadmin@scholaria.com | System Configuration | 🟡 Settings |
| Teacher | teacher@scholaria.com | Teaching & Course Management | 🔵 Educational |
| Student | student@scholaria.com | Learning & Course Participation | 🔵 Learner |

**Legend:**
- 🔴 **Full Access** - Complete system control
- 🟡 **Administrative Access** - Specific administrative functions
- 🟢 **Read-Only Access** - Viewing and basic operations
- 🔵 **Functional Access** - Role-specific functional permissions

This setup provides comprehensive testing coverage for your RBAC implementation, allowing you to verify that each role has exactly the right level of access for their responsibilities.
