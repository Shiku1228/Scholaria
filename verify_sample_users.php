<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\User;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$app->make(\Illuminate\Contracts\Console\Kernel::class);

$users = User::where('email', 'like', '%@scholaria.com')->get(['email', 'name']);

if ($users->count() === 0) {
    echo "❌ No sample users found.\n";
} else {
    echo "✅ Found " . $users->count() . " sample users:\n";
    foreach ($users as $user) {
        echo "   - {$user->email} ({$user->name})\n";
    }
}
