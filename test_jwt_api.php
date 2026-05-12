<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\JwtAuthController;

// Create a test request
$request = Request::create('/api/login', 'POST', [
    'email' => 'student@example.com',
    'password' => 'password'
], [], [], [
    'HTTP_CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json'
]);

// Test the JWT login
try {
    $controller = new JwtAuthController();
    $response = $controller->login($request);
    
    echo "JWT Login Test Results:\n";
    echo "======================\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
