<?php

/**
 * Setup Analytics Tables
 * Creates the missing analytics tables needed for dashboard functionality
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Setting up analytics tables...\n\n";

    // Create article_views table
    $sql = "CREATE TABLE IF NOT EXISTS article_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        user_id INT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_article_id (article_id),
        INDEX idx_user_id (user_id),
        INDEX idx_viewed_at (viewed_at),
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $db->exec($sql);
    echo "✅ Created article_views table\n";

    // Create article_reads table
    $sql = "CREATE TABLE IF NOT EXISTS article_reads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        user_id INT NULL,
        time_spent INT DEFAULT 0,
        scroll_depth INT DEFAULT 0,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_read (article_id, user_id, DATE(read_at)),
        INDEX idx_article_id (article_id),
        INDEX idx_user_id (user_id),
        INDEX idx_read_at (read_at),
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $db->exec($sql);
    echo "✅ Created article_reads table\n";

    // Create article_tags table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS article_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        tag_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_article_tag (article_id, tag_id),
        INDEX idx_article_id (article_id),
        INDEX idx_tag_id (tag_id),
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "✅ Created article_tags table\n";

    // Create tags table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_slug (slug)
    )";
    
    $db->exec($sql);
    echo "✅ Created tags table\n";

    // Insert some sample data for testing
    echo "\nInserting sample data...\n";

    // Insert sample tags
    $sampleTags = [
        ['Technology', 'technology'],
        ['Programming', 'programming'],
        ['Design', 'design'],
        ['Startup', 'startup'],
        ['AI', 'ai'],
        ['Web Development', 'web-development'],
        ['JavaScript', 'javascript'],
        ['React', 'react'],
        ['PHP', 'php'],
        ['Database', 'database']
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
    foreach ($sampleTags as $tag) {
        $stmt->execute($tag);
    }
    echo "✅ Inserted sample tags\n";

    // Get some articles and add sample views/reads
    $stmt = $db->prepare("SELECT id FROM articles WHERE status = 'published' LIMIT 5");
    $stmt->execute();
    $articles = $stmt->fetchAll();

    if (!empty($articles)) {
        // Add sample article views
        $stmt = $db->prepare("INSERT IGNORE INTO article_views (article_id, user_id, ip_address, viewed_at) VALUES (?, ?, ?, ?)");
        foreach ($articles as $article) {
            // Add multiple views for each article
            for ($i = 0; $i < rand(10, 50); $i++) {
                $userId = rand(1, 10) <= 7 ? rand(1, 5) : null; // 70% chance of logged-in user
                $ip = '192.168.1.' . rand(1, 255);
                $viewedAt = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
                $stmt->execute([$article['id'], $userId, $ip, $viewedAt]);
            }
        }
        echo "✅ Inserted sample article views\n";

        // Add sample article reads
        $stmt = $db->prepare("INSERT IGNORE INTO article_reads (article_id, user_id, time_spent, scroll_depth, read_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($articles as $article) {
            // Add reads for each article
            for ($i = 0; $i < rand(5, 20); $i++) {
                $userId = rand(1, 10) <= 8 ? rand(1, 5) : null; // 80% chance of logged-in user
                $timeSpent = rand(30, 600); // 30 seconds to 10 minutes
                $scrollDepth = rand(25, 100); // 25% to 100% scroll
                $readAt = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
                $stmt->execute([$article['id'], $userId, $timeSpent, $scrollDepth, $readAt]);
            }
        }
        echo "✅ Inserted sample article reads\n";

        // Add sample article tags
        $stmt = $db->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)");
        foreach ($articles as $article) {
            // Add 2-4 random tags to each article
            $numTags = rand(2, 4);
            $usedTags = [];
            for ($i = 0; $i < $numTags; $i++) {
                $tagId = rand(1, 10);
                if (!in_array($tagId, $usedTags)) {
                    $stmt->execute([$article['id'], $tagId]);
                    $usedTags[] = $tagId;
                }
            }
        }
        echo "✅ Inserted sample article tags\n";
    }

    echo "\n✅ Analytics tables setup completed successfully!\n";
    echo "Dashboard should now show data.\n";

} catch (Exception $e) {
    echo "❌ Error setting up analytics tables: " . $e->getMessage() . "\n";
}