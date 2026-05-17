<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

// Check if user already exists
$existingUser = User::where('email', 'student@example.com')->first();
if ($existingUser) {
    echo "User already exists!\n";
    exit;
}

// Create test user
$user = User::create([
    'name' => 'Test Student',
    'email' => 'student@example.com',
    'password' => Hash::make('password')
]);

// Assign Student role
$studentRole = Role::where('name', 'Student')->first();
if ($studentRole) {
    $user->assignRole($studentRole);
    echo "Test user created successfully with Student role!\n";
} else {
    echo "Test user created but no Student role found. Please run the roles seeder first.\n";
}

echo "Email: student@example.com\n";
echo "Password: password\n";
