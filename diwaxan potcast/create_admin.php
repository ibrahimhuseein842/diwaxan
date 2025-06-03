<?php

require_once 'config.php';

$username = 'admin';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // پاککردنەوەی ئەدمینە کۆنەکان
    $pdo->exec("DELETE FROM admins WHERE username = 'admin'");
    
    // زیادکردنی ئەدمینی نوێ
    $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, 'super_admin')");
    $stmt->execute([$username, $hashed_password]);
    
    echo "✅ ئەدمین بە سەرکەوتوویی دروست کرا\n";
    echo "Username: " . $username . "\n";
    echo "Password: " . $password . "\n";
    echo "Hashed Password: " . $hashed_password . "\n";
    
} catch(PDOException $e) {
    echo "❌ هەڵە ڕوویدا: " . $e->getMessage();
}
?>