<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $articleId = 36;
    $stmt = $db->prepare("SELECT id FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $result = $stmt->fetch();
    
    echo "Article ID $articleId exists: " . ($result ? 'YES' : 'NO') . "\n";
    if ($result) {
        echo "Article data: " . json_encode($result) . "\n";
    }
    
    // Also check the status
    $stmt = $db->prepare("SELECT id, status FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "Article status: " . $result['status'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}