<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

// Test admin user role detection
$adminEmail = 'superadmin@scholaria.com'; // Change to actual admin email
$user = User::where('email', $adminEmail)->first();

if (!$user) {
    echo "User not found: $adminEmail\n";
    exit;
}

echo "=== USER INFO ===\n";
echo "User ID: " . $user->id . "\n";
echo "User Email: " . $user->email . "\n";

echo "\n=== ROLE DETECTION ===\n";

// Test hasRole method
if (method_exists($user, 'hasRole')) {
    echo "hasRole method exists: YES\n";
    
    $adminRoles = ['Admin', 'Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin'];
    foreach ($adminRoles as $adminRole) {
        $hasRole = $user->hasRole($adminRole);
        echo "Has role '$adminRole': " . ($hasRole ? 'YES' : 'NO') . "\n";
        
        if ($hasRole) {
            echo "✅ Admin role detected!\n";
            break;
        }
    }
    
    // Check if user has ANY admin role
    $hasAnyAdminRole = false;
    foreach ($adminRoles as $adminRole) {
        if ($user->hasRole($adminRole)) {
            $hasAnyAdminRole = true;
            break;
        }
    }
    
    echo "Has any admin role: " . ($hasAnyAdminRole ? 'YES' : 'NO') . "\n";
    
} else {
    echo "hasRole method exists: NO\n";
}

// Check all user roles
if (method_exists($user, 'getRoleNames')) {
    $allRoles = $user->getRoleNames();
    echo "All assigned roles: " . json_encode($allRoles->toArray()) . "\n";
} else {
    echo "getRoleNames method not found\n";
}

// Check legacy role field
echo "Legacy role field: " . ($user->role ?? 'NULL') . "\n";

echo "\n=== ROUTE TEST ===\n";
echo "Should redirect to: " . ($user->hasRole('Super Admin') ? 'admin.dashboard' : 'student.dashboard') . "\n";
