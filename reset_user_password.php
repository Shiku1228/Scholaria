<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Find and update the user
$user = User::where('email', 'student@example.com')->first();
if ($user) {
    $user->password = Hash::make('password');
    $user->save();
    
    echo "Password reset successfully!\n";
    echo "Email: student@example.com\n";
    echo "Password: password\n";
} else {
    echo "User not found!\n";
}
