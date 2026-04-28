<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SECURITY TESTING ===\n\n";

// Test 1: Argon2 Password Hashing
echo "1. Testing Argon2 Password Hashing:\n";
$testPassword = 'TestPassword123!';
$hashed = Hash::make($testPassword);
echo "   Hashed password: " . substr($hashed, 0, 20) . "...\n";
echo "   Algorithm used: " . (str_contains($hashed, '$argon2id$') ? 'Argon2id ✅' : 'Other ❌') . "\n";
echo "   Password verification: " . (Hash::check($testPassword, $hashed) ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test 2: Database Encryption
echo "2. Testing Database Encryption:\n";
$testUser = User::first();
if ($testUser) {
    echo "   Testing user: " . $testUser->email . "\n";
    
    // Check encrypted fields in database
    $dbUser = DB::table('users')->where('id', $testUser->id)->first();
    
    if ($dbUser->first_name) {
        echo "   First name encrypted: " . (str_contains($dbUser->first_name, 'eyJ') ? '✅ ENCRYPTED' : '❌ NOT ENCRYPTED') . "\n";
        echo "   Decrypted first name: " . $testUser->first_name . "\n";
    }
    
    if ($dbUser->student_number) {
        echo "   Student number encrypted: " . (str_contains($dbUser->student_number, 'eyJ') ? '✅ ENCRYPTED' : '❌ NOT ENCRYPTED') . "\n";
        echo "   Decrypted student number: " . $testUser->student_number . "\n";
    }
} else {
    echo "   No users found in database ❌\n";
}
echo "\n";

// Test 3: OAuth Configuration
echo "3. Testing OAuth Configuration:\n";
$googleClientId = env('GOOGLE_CLIENT_ID');
$githubClientId = env('GITHUB_CLIENT_ID');

echo "   Google OAuth configured: " . ($googleClientId ? '✅ YES' : '❌ NO (Set GOOGLE_CLIENT_ID in .env)') . "\n";
echo "   GitHub OAuth configured: " . ($githubClientId ? '✅ YES' : '❌ NO (Set GITHUB_CLIENT_ID in .env)') . "\n";
echo "   Socialite package installed: " . (class_exists('Laravel\Socialite\Facades\Socialite') ? '✅ YES' : '❌ NO') . "\n\n";

// Test 4: JWT Configuration
echo "4. Testing JWT Configuration:\n";
$jwtSecret = env('JWT_SECRET');
echo "   JWT Secret configured: " . ($jwtSecret ? '✅ YES' : '❌ NO (Set JWT_SECRET in .env)') . "\n";
echo "   JWT package installed: " . (class_exists('Tymon\JWTAuth\Facades\JWTAuth') ? '✅ YES' : '❌ NO') . "\n\n";

// Test 5: MFA Configuration
echo "5. Testing MFA Configuration:\n";
$mfaUser = User::where('google2fa_enabled', true)->first();
echo "   Users with 2FA enabled: " . ($mfaUser ? '✅ YES' : '❌ NO') . "\n";
echo "   Google2FA package installed: " . (class_exists('PragmaRX\Google2FA\Google2FA') ? '✅ YES' : '❌ NO') . "\n\n";

// Test 6: RBAC Configuration
echo "6. Testing RBAC Configuration:\n";
$adminUser = User::role('admin')->first();
echo "   Admin role exists: " . ($adminUser ? '✅ YES' : '❌ NO') . "\n";
echo "   Spatie Permission package installed: " . (class_exists('Spatie\Permission\Models\Role') ? '✅ YES' : '❌ NO') . "\n\n";

echo "=== TESTING COMPLETE ===\n";
