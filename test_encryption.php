<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE ENCRYPTION TESTING ===\n\n";

// Create test user with sensitive data
echo "1. Creating test user with sensitive data...\n";
$testUser = User::create([
    'name' => 'Test Student',
    'first_name' => 'John',
    'middle_name' => 'Robert',
    'last_name' => 'Doe',
    'student_number' => 'STU12345',
    'email' => 'test.encryption.' . time() . '@example.com',
    'password' => 'password123',
]);

echo "   ✅ Test user created: " . $testUser->email . "\n\n";

// Test 2: Check raw database values (should be encrypted)
echo "2. Checking raw database values (should be encrypted)...\n";
$rawUser = DB::table('users')->where('id', $testUser->id)->first();

echo "   Raw first_name: " . substr($rawUser->first_name, 0, 30) . "...\n";
echo "   First name encrypted: " . (str_contains($rawUser->first_name, 'eyJ') ? '✅ YES' : '❌ NO') . "\n";

echo "   Raw middle_name: " . substr($rawUser->middle_name, 0, 30) . "...\n";
echo "   Middle name encrypted: " . (str_contains($rawUser->middle_name, 'eyJ') ? '✅ YES' : '❌ NO') . "\n";

echo "   Raw last_name: " . substr($rawUser->last_name, 0, 30) . "...\n";
echo "   Last name encrypted: " . (str_contains($rawUser->last_name, 'eyJ') ? '✅ YES' : '❌ NO') . "\n";

echo "   Raw student_number: " . substr($rawUser->student_number, 0, 30) . "...\n";
echo "   Student number encrypted: " . (str_contains($rawUser->student_number, 'eyJ') ? '✅ YES' : '❌ NO') . "\n\n";

// Test 3: Check decrypted values through model
echo "3. Checking decrypted values through model...\n";
$user = User::find($testUser->id);

echo "   Decrypted first_name: " . $user->first_name . "\n";
echo "   Decrypted middle_name: " . $user->middle_name . "\n";
echo "   Decrypted last_name: " . $user->last_name . "\n";
echo "   Decrypted student_number: " . $user->student_number . "\n\n";

// Test 4: Update encrypted field
echo "4. Testing field updates...\n";
$user->update(['first_name' => 'Jane']);
echo "   ✅ Updated first_name to: " . $user->first_name . "\n";

// Check if still encrypted in database
$updatedRaw = DB::table('users')->where('id', $testUser->id)->first();
echo "   Still encrypted after update: " . (str_contains($updatedRaw->first_name, 'eyJ') ? '✅ YES' : '❌ NO') . "\n\n";

// Test 5: Test decryption failure handling
echo "5. Testing decryption failure handling...\n";
// Insert fake encrypted data
DB::table('users')->where('id', $testUser->id)->update(['middle_name' => 'fake_encrypted_data']);

$user = User::find($testUser->id);
echo "   Handled decryption failure: " . ($user->middle_name === 'fake_encrypted_data' ? '✅ YES' : '❌ NO') . "\n\n";

// Cleanup
echo "6. Cleaning up test data...\n";
User::where('id', $testUser->id)->delete();
echo "   ✅ Test user deleted\n\n";

echo "=== ENCRYPTION TESTING COMPLETE ===\n";
