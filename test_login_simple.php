<?php

// Simple test to check role assignments without Laravel framework
$host = 'localhost';
$dbname = 'scholaria';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n\n";
    
    // Check roles table
    $stmt = $pdo->query("SELECT * FROM roles");
    echo "ROLES TABLE:\n";
    echo "ID\tName\t\tGuard\n";
    echo "--------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['id']}\t{$row['name']}\t\t{$row['guard_name']}\n";
    }
    
    echo "\n";
    
    // Check model_has_roles table
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, r.name as role_name 
        FROM users u 
        LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id 
        LEFT JOIN roles r ON mhr.role_id = r.id 
        WHERE u.id IN (1, 2, 3, 4, 5)
        ORDER BY u.id
    ");
    
    echo "USER ROLE ASSIGNMENTS:\n";
    echo "ID\tName\t\tEmail\t\tRole\n";
    echo "------------------------------------------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['id']}\t{$row['name']}\t\t{$row['email']}\t{$row['role_name']}\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
