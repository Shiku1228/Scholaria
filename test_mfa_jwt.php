<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MFA & JWT TESTING ===\n\n";

// Test 1: JWT Token Generation
echo "1. Testing JWT Token Generation...\n";
$adminUser = User::role('admin')->first();
if ($adminUser) {
    try {
        $token = JWTAuth::fromUser($adminUser);
        echo "   ✅ JWT Token generated: " . substr($token, 0, 20) . "...\n";
        
        // Test token validation
        $user = JWTAuth::setToken($token)->authenticate();
        echo "   ✅ Token validation: " . ($user->id === $adminUser->id ? 'PASS' : 'FAIL') . "\n";
    } catch (\Exception $e) {
        echo "   ❌ JWT Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ No admin user found\n";
}
echo "\n";

// Test 2: MFA Setup
echo "2. Testing MFA Setup...\n";
$testUser = User::where('email', 'like', '%admin%')->first();
if ($testUser) {
    try {
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        
        if (!$testUser->google2fa_secret) {
            $secret = $google2fa->generateSecretKey();
            $testUser->google2fa_secret = encrypt($secret);
            $testUser->save();
            echo "   ✅ 2FA Secret generated and saved\n";
        }
        
        $secret = decrypt($testUser->google2fa_secret);
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'Scholaria',
            $testUser->email,
            $secret
        );
        echo "   ✅ QR Code URL generated: " . substr($qrCodeUrl, 0, 50) . "...\n";
        
        // Test OTP verification
        $otp = $google2fa->getCurrentOtp($secret);
        echo "   ✅ Current OTP: " . $otp . "\n";
        echo "   ✅ OTP Verification: " . ($google2fa->verifyKey($secret, $otp) ? 'PASS' : 'FAIL') . "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ MFA Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ No test user found\n";
}
echo "\n";

// Test 3: Role-based Permissions
echo "3. Testing Role-based Permissions...\n";
$admin = User::role('admin')->first();
$student = User::role('student')->first();

if ($admin && $student) {
    echo "   Admin permissions:\n";
    echo "   - Can manage users: " . ($admin->can('manage users') ? '✅ YES' : '❌ NO') . "\n";
    echo "   - Can manage courses: " . ($admin->can('manage courses') ? '✅ YES' : '❌ NO') . "\n";
    
    echo "   Student permissions:\n";
    echo "   - Can view courses: " . ($student->can('view courses') ? '✅ YES' : '❌ NO') . "\n";
    echo "   - Can manage users: " . ($student->can('manage users') ? '❌ NO (unexpected)' : '✅ NO (expected)') . "\n";
} else {
    echo "   ❌ Missing admin or student users\n";
}
echo "\n";

// Test 4: API Authentication
echo "4. Testing API Authentication...\n";
if ($adminUser) {
    // Simulate API login
    $credentials = ['email' => $adminUser->email, 'password' => 'password'];
    
    if (Auth::attempt($credentials)) {
        $token = JWTAuth::fromUser(Auth::user());
        echo "   ✅ API Login successful\n";
        echo "   ✅ Token received: " . substr($token, 0, 20) . "...\n";
        
        // Test protected route simulation
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            echo "   ✅ Token payload valid (expires: " . $payload->get('exp') . ")\n";
        } catch (\Exception $e) {
            echo "   ❌ Token payload error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ API Login failed\n";
    }
}
echo "\n";

echo "=== MFA & JWT TESTING COMPLETE ===\n";
