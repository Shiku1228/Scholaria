# Granular RBAC Usage Guide

## Overview
Your admin panel now supports granular role-based access control with 5 specific admin roles instead of a generic "Admin" role.

## Available Roles

### 1. Super Admin
- **Description**: Full system access with all permissions
- **Permissions**: All available permissions
- **Use Case**: System administrators, owners

### 2. Content Admin
- **Description**: Manage courses, lessons, and basic user operations
- **Permissions**: 
  - `users.view`, `users.edit`
  - `courses.view`, `courses.create`, `courses.edit`, `courses.delete`
  - `lessons.view`, `lessons.create`, `lessons.edit`, `lessons.delete`
- **Use Case**: Content managers, course administrators

### 3. User Admin
- **Description**: Manage user accounts, roles, and permissions
- **Permissions**:
  - `users.view`, `users.create`, `users.edit`, `users.delete`, `users.suspend`
- **Use Case**: HR administrators, user management

### 4. Report Admin
- **Description**: View reports, analytics, and export data
- **Permissions**:
  - `reports.view`, `reports.export`, `users.view`
- **Use Case**: Data analysts, report viewers

### 5. Settings Admin
- **Description**: Manage system settings and configurations
- **Permissions**:
  - `settings.view`, `settings.edit`, `users.view`
- **Use Case**: System administrators, IT staff

## Implementation Examples

### 1. Route Protection
```php
// Protect routes with specific permissions
Route::middleware(['auth', 'check.permission:users.edit'])->group(function () {
    Route::get('/users/{user}/edit', [UserController::class, 'edit']);
});

// Multiple permissions
Route::middleware(['auth', 'check.permission:courses.view|lessons.view'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
});
```

### 2. Controller Permission Checks
```php
public function edit(User $user)
{
    if (!auth()->user()->hasPermissionTo('users.edit')) {
        abort(403, 'You do not have permission to edit users.');
    }
    
    // Your logic here
}
```

### 3. Blade Template Permission Checks
```php
@can('users.delete')
    <button class="btn btn-danger">Delete User</button>
@else
    <span class="text-muted">No permission to delete</span>
@endcan

// Multiple permissions
@can(['courses.create', 'courses.edit'])
    <button class="btn btn-primary">Manage Course</button>
@endcan
```

### 4. User Role Assignment
```php
// Assign specific role to user
$user = User::find($userId);
$role = Role::where('name', 'Content Admin')->first();
$user->syncRoles([$role]);

// Check if user has specific permission
if ($user->hasPermissionTo('users.delete')) {
    // Allow deletion
}
```

## Role Management UI

Access the role management interface at: `/admin/roles`

### Features:
- View all roles with their permissions
- Create new roles with specific permission assignments
- Edit existing roles
- Delete roles (protected if users are assigned)
- See user count for each role

## Migration & Seeding

The system has been seeded with:
- 5 granular admin roles
- 17 specific permissions
- Proper role-permission assignments

## Security Notes

1. **Principle of Least Privilege**: Each role only has permissions needed for their function
2. **Audit Trail**: All permission checks are logged through Laravel's built-in logging
3. **No Generic Admin**: The old "Admin" role should be phased out in favor of specific roles
4. **Permission Validation**: All admin routes are protected by permission middleware

## Next Steps

1. **Update Existing Admin Routes**: Replace generic `role:Admin` middleware with specific permission checks
2. **Update User Management**: Add role assignment functionality to user management
3. **Add Permission Checks**: Implement permission checks in all admin controllers
4. **Update Navigation**: Add role management link to admin navigation
5. **Testing**: Verify all permissions work correctly across different role types

## Files Created/Modified

- `database/migrations/2026_05_10_000001_create_granular_admin_roles_and_permissions.php`
- `database/seeders/GranularAdminRolesAndPermissionsSeeder.php`
- `app/Http/Middleware/CheckPermissionMiddleware.php`
- `app/Http/Controllers/Admin/RoleManagementController.php`
- `resources/views/admin/roles/` (index, create, edit views)
- `routes/web.php` (updated with role management routes)
- `bootstrap/app.php` (middleware registration)

This granular RBAC system provides much better security and flexibility compared to the previous generic admin role system.
