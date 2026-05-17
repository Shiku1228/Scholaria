<?php

echo "Testing admin users...\n";

// Simple database connection test
try {
    $pdo = new PDO('mysql:host=localhost;dbname=scholaria', 'root', '');
    echo "✓ Database connected\n";
    
    // Check for admin users
    $stmt = $pdo->prepare("SELECT u.email, r.name as role_name 
                            FROM users u 
                            JOIN model_has_roles mr ON u.id = mr.model_id 
                            JOIN roles r ON mr.role_id = r.id 
                            WHERE r.name IN ('Super Admin', 'Content Admin', 'User Admin', 'Report Admin', 'Settings Admin')");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($adminUsers) > 0) {
        echo "✓ Found " . count($adminUsers) . " admin users:\n";
        foreach ($adminUsers as $user) {
            echo "  - " . $user['email'] . " (" . $user['role_name'] . ")\n";
        }
        
        // Show specific super admin details
        $superAdmin = array_filter($adminUsers, function($user) {
            return $user['role_name'] === 'Super Admin';
        });
        
        if (!empty($superAdmin)) {
            $superAdmin = reset($superAdmin);
            echo "\n✓ Super Admin login details:\n";
            echo "  Email: " . $superAdmin['email'] . "\n";
            echo "  Password: password123\n";
            echo "  Admin URL: http://127.0.0.1:8000/admin/dashboard\n";
        }
    } else {
        echo "✗ No admin users found!\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
