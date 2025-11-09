<?php
/**
 * Test API Endpoints
 * Simple script to test if API endpoints are working
 */

require_once __DIR__ . '/config/config.php';

echo "🔧 Testing API Endpoints\n";
echo "========================\n\n";

// Test database connection first
try {
    require_once __DIR__ . '/models/Article.php';
    $article = new Article();
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test trending articles
echo "1. Testing trending articles...\n";
try {
    $articles = $article->getTrendingArticles(5);
    echo "✓ Found " . count($articles) . " trending articles\n";
    if (!empty($articles)) {
        echo "  Sample: " . $articles[0]['title'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test more from author
echo "2. Testing more from author...\n";
try {
    // Get first article to test with
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, author_id FROM articles WHERE status = 'published' LIMIT 1");
    $stmt->execute();
    $testArticle = $stmt->fetch();
    
    if ($testArticle) {
        $moreArticles = $article->getMoreFromAuthor($testArticle['author_id'], $testArticle['id'], 3);
        echo "✓ Found " . count($moreArticles) . " more articles from author\n";
    } else {
        echo "⚠ No published articles found for testing\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test related articles
echo "3. Testing related articles...\n";
try {
    if (isset($testArticle)) {
        $relatedArticles = $article->getRelatedArticles($testArticle['id'], 3);
        echo "✓ Found " . count($relatedArticles) . " related articles\n";
    } else {
        echo "⚠ No test article available\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test claps
echo "4. Testing claps functionality...\n";
try {
    require_once __DIR__ . '/models/Clap.php';
    $clap = new Clap();
    
    if (isset($testArticle)) {
        $totalClaps = $clap->getArticleTotalClaps($testArticle['id']);
        echo "✓ Article has $totalClaps claps\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test bookmarks
echo "5. Testing bookmarks functionality...\n";
try {
    require_once __DIR__ . '/models/Bookmark.php';
    $bookmark = new Bookmark();
    echo "✓ Bookmark model loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test comments
echo "6. Testing comments functionality...\n";
try {
    require_once __DIR__ . '/models/Comment.php';
    $comment = new Comment();
    
    if (isset($testArticle)) {
        $comments = $comment->getArticleComments($testArticle['id']);
        echo "✓ Found " . count($comments) . " comments for article\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test notifications
echo "7. Testing notifications functionality...\n";
try {
    require_once __DIR__ . '/models/Notification.php';
    $notification = new Notification();
    
    // Get first user for testing
    $stmt = $db->prepare("SELECT id FROM users LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        $unreadCount = $notification->getUnreadCount($testUser['id']);
        echo "✓ User has $unreadCount unread notifications\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Endpoint testing completed!\n";
echo "\nTo test the actual HTTP endpoints, make sure your server is running and try:\n";
echo "curl -X GET 'http://localhost:8000/api/articles/trending'\n";
echo "curl -X GET 'http://localhost:8000/api/notifications/unread-count' -H 'Authorization: Bearer YOUR_TOKEN'\n";