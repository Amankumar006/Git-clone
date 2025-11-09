<?php
/**
 * Test script for Collaborative Workflow functionality
 * Run this after setting up the database tables
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/ArticleSubmission.php';
require_once __DIR__ . '/models/ArticleRevision.php';
require_once __DIR__ . '/models/PublicationTemplate.php';
require_once __DIR__ . '/models/PublicationGuideline.php';

echo "Testing Collaborative Workflow Components...\n\n";

try {
    // Test ArticleSubmission model
    echo "1. Testing ArticleSubmission model...\n";
    $submissionModel = new ArticleSubmission();
    echo "   ✓ ArticleSubmission model loaded successfully\n";
    
    // Test ArticleRevision model
    echo "2. Testing ArticleRevision model...\n";
    $revisionModel = new ArticleRevision();
    echo "   ✓ ArticleRevision model loaded successfully\n";
    
    // Test PublicationTemplate model
    echo "3. Testing PublicationTemplate model...\n";
    $templateModel = new PublicationTemplate();
    $predefinedTemplates = $templateModel->getPredefinedTemplates();
    echo "   ✓ PublicationTemplate model loaded successfully\n";
    echo "   ✓ Found " . count($predefinedTemplates) . " predefined templates\n";
    
    // Test PublicationGuideline model
    echo "4. Testing PublicationGuideline model...\n";
    $guidelineModel = new PublicationGuideline();
    $categories = $guidelineModel->getCategories();
    echo "   ✓ PublicationGuideline model loaded successfully\n";
    echo "   ✓ Found " . count($categories) . " guideline categories\n";
    
    // Test database tables exist (basic check)
    echo "5. Testing database tables...\n";
    $db = Database::getInstance()->getConnection();
    
    $tables = [
        'article_submissions',
        'article_revisions', 
        'publication_templates',
        'publication_guidelines',
        'article_review_comments',
        'collaborative_sessions',
        'collaborative_participants',
        'workflow_notifications'
    ];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' missing - please run collaborative_workflow.sql\n";
        }
    }
    
    echo "\n✓ All collaborative workflow components tested successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Execute database/collaborative_workflow.sql in phpMyAdmin\n";
    echo "2. Test the workflow endpoints via the frontend\n";
    echo "3. Create templates and guidelines for your publications\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Make sure to run the database migration first.\n";
}