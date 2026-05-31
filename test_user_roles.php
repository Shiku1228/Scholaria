<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;

echo "Testing user roles and permissions...\n\n";

// Test admin user (ID 1)
$user = User::find(1);
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Has method hasRole: " . (method_exists($user, 'hasRole') ? 'YES' : 'NO') . "\n";
    
    if (method_exists($user, 'hasRole')) {
        echo "Roles: " . json_encode($user->getRoleNames()) . "\n";
        echo "Is Admin: " . ($user->hasRole('Admin') ? 'YES' : 'NO') . "\n";
        echo "Is Teacher: " . ($user->hasRole('Teacher') ? 'YES' : 'NO') . "\n";
        echo "Is Student: " . ($user->hasRole('Student') ? 'YES' : 'NO') . "\n";
    }
    echo "Legacy role field: " . $user->role . "\n";
    echo "Profile type: " . $user->profile_type . "\n";
} else {
    echo "Admin user not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test teacher user (ID 2)
$user = User::find(2);
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Has method hasRole: " . (method_exists($user, 'hasRole') ? 'YES' : 'NO') . "\n";
    
    if (method_exists($user, 'hasRole')) {
        echo "Roles: " . json_encode($user->getRoleNames()) . "\n";
        echo "Is Admin: " . ($user->hasRole('Admin') ? 'YES' : 'NO') . "\n";
        echo "Is Teacher: " . ($user->hasRole('Teacher') ? 'YES' : 'NO') . "\n";
        echo "Is Student: " . ($user->hasRole('Student') ? 'YES' : 'NO') . "\n";
    }
    echo "Legacy role field: " . $user->role . "\n";
    echo "Profile type: " . $user->profile_type . "\n";
} else {
    echo "Teacher user not found\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test student user (ID 3)
$user = User::find(3);
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Has method hasRole: " . (method_exists($user, 'hasRole') ? 'YES' : 'NO') . "\n";
    
    if (method_exists($user, 'hasRole')) {
        echo "Roles: " . json_encode($user->getRoleNames()) . "\n";
        echo "Is Admin: " . ($user->hasRole('Admin') ? 'YES' : 'NO') . "\n";
        echo "Is Teacher: " . ($user->hasRole('Teacher') ? 'YES' : 'NO') . "\n";
        echo "Is Student: " . ($user->hasRole('Student') ? 'YES' : 'NO') . "\n";
    }
    echo "Legacy role field: " . $user->role . "\n";
    echo "Profile type: " . $user->profile_type . "\n";
} else {
    echo "Student user not found\n";
}
