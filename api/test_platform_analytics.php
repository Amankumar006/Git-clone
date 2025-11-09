<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/PlatformAnalytics.php';

echo "Testing Platform Analytics System\n";
echo "================================\n\n";

try {
    $analyticsModel = new PlatformAnalytics();
    
    // Test 1: User Growth Analytics
    echo "Test 1: User Growth Analytics\n";
    $userGrowth = $analyticsModel->getUserGrowthAnalytics(30);
    echo "✓ Retrieved " . count($userGrowth) . " days of user growth data\n";
    
    if (!empty($userGrowth)) {
        $latestDay = end($userGrowth);
        echo "  - Latest day: " . $latestDay['date'] . "\n";
        echo "  - New users: " . $latestDay['new_users'] . "\n";
        echo "  - Cumulative users: " . $latestDay['cumulative_users'] . "\n";
    }
    
    // Test 2: Content Analytics
    echo "\nTest 2: Content Analytics\n";
    $contentAnalytics = $analyticsModel->getContentAnalytics(30);
    echo "✓ Retrieved " . count($contentAnalytics) . " days of content data\n";
    
    if (!empty($contentAnalytics)) {
        $totalArticles = array_sum(array_column($contentAnalytics, 'articles_created'));
        $totalPublished = array_sum(array_column($contentAnalytics, 'articles_published'));
        echo "  - Total articles created: " . $totalArticles . "\n";
        echo "  - Total articles published: " . $totalPublished . "\n";
    }
    
    // Test 3: Engagement Analytics
    echo "\nTest 3: Engagement Analytics\n";
    $engagementAnalytics = $analyticsModel->getEngagementAnalytics(30);
    echo "✓ Retrieved " . count($engagementAnalytics) . " engagement data points\n";
    
    // Group by type
    $engagementByType = [];
    foreach ($engagementAnalytics as $engagement) {
        $type = $engagement['type'];
        if (!isset($engagementByType[$type])) {
            $engagementByType[$type] = 0;
        }
        $engagementByType[$type] += $engagement['count'];
    }
    
    foreach ($engagementByType as $type => $count) {
        echo "  - Total {$type}: {$count}\n";
    }
    
    // Test 4: Top Articles
    echo "\nTest 4: Top Performing Articles\n";
    $topArticles = $analyticsModel->getTopArticles(30, 5);
    echo "✓ Retrieved " . count($topArticles) . " top articles\n";
    
    foreach ($topArticles as $i => $article) {
        echo "  " . ($i + 1) . ". " . substr($article['title'], 0, 50) . "...\n";
        echo "     Score: " . $article['engagement_score'] . " (Views: " . $article['view_count'] . 
             ", Claps: " . $article['clap_count'] . ", Comments: " . $article['comment_count'] . ")\n";
    }
    
    // Test 5: Trending Topics
    echo "\nTest 5: Trending Topics\n";
    $trendingTopics = $analyticsModel->getTrendingTopics(30, 5);
    echo "✓ Retrieved " . count($trendingTopics) . " trending topics\n";
    
    foreach ($trendingTopics as $i => $topic) {
        echo "  " . ($i + 1) . ". #{$topic['name']}\n";
        echo "     Articles: " . $topic['article_count'] . ", Views: " . $topic['total_views'] . 
             ", Claps: " . $topic['total_claps'] . "\n";
    }
    
    // Test 6: Platform Health Metrics
    echo "\nTest 6: Platform Health Metrics\n";
    $healthMetrics = $analyticsModel->getPlatformHealthMetrics();
    echo "✓ Retrieved " . count($healthMetrics) . " health metrics\n";
    
    foreach ($healthMetrics as $metric) {
        echo "  - " . $metric['metric'] . ": " . round($metric['value'], 2) . "\n";
    }
    
    // Test 7: User Engagement Patterns
    echo "\nTest 7: User Engagement Patterns\n";
    $engagementPatterns = $analyticsModel->getUserEngagementPatterns(30);
    echo "✓ Retrieved " . count($engagementPatterns) . " engagement pattern data points\n";
    
    // Find peak activity hours
    $hourlyActivity = [];
    foreach ($engagementPatterns as $pattern) {
        $hour = $pattern['hour_of_day'];
        if (!isset($hourlyActivity[$hour])) {
            $hourlyActivity[$hour] = 0;
        }
        $hourlyActivity[$hour] += $pattern['activity_count'];
    }
    
    if (!empty($hourlyActivity)) {
        arsort($hourlyActivity);
        $peakHour = array_key_first($hourlyActivity);
        echo "  - Peak activity hour: {$peakHour}:00 with " . $hourlyActivity[$peakHour] . " activities\n";
    }
    
    // Test 8: Retention Analytics
    echo "\nTest 8: Retention Analytics\n";
    $retentionAnalytics = $analyticsModel->getRetentionAnalytics(30);
    echo "✓ Retrieved " . count($retentionAnalytics) . " cohort retention data points\n";
    
    if (!empty($retentionAnalytics)) {
        $avgDay1Retention = 0;
        $avgDay7Retention = 0;
        $avgDay30Retention = 0;
        $totalCohorts = 0;
        
        foreach ($retentionAnalytics as $cohort) {
            if ($cohort['cohort_size'] > 0) {
                $avgDay1Retention += ($cohort['day_1_retention'] / $cohort['cohort_size']) * 100;
                $avgDay7Retention += ($cohort['day_7_retention'] / $cohort['cohort_size']) * 100;
                $avgDay30Retention += ($cohort['day_30_retention'] / $cohort['cohort_size']) * 100;
                $totalCohorts++;
            }
        }
        
        if ($totalCohorts > 0) {
            echo "  - Average Day 1 retention: " . round($avgDay1Retention / $totalCohorts, 2) . "%\n";
            echo "  - Average Day 7 retention: " . round($avgDay7Retention / $totalCohorts, 2) . "%\n";
            echo "  - Average Day 30 retention: " . round($avgDay30Retention / $totalCohorts, 2) . "%\n";
        }
    }
    
    // Test 9: Comparative Analytics
    echo "\nTest 9: Comparative Analytics\n";
    $comparativeAnalytics = $analyticsModel->getComparativeAnalytics(30, 30);
    echo "✓ Retrieved " . count($comparativeAnalytics) . " comparative metrics\n";
    
    foreach ($comparativeAnalytics as $metric) {
        $change = $metric['percentage_change'];
        $changeText = $change > 0 ? "+{$change}%" : "{$change}%";
        echo "  - " . ucfirst($metric['metric']) . ": " . $metric['current_period'] . 
             " (vs " . $metric['previous_period'] . ", {$changeText})\n";
    }
    
    // Test 10: Export functionality
    echo "\nTest 10: Export Functionality\n";
    $exportData = $analyticsModel->exportAnalyticsData('user_growth', 7);
    echo "✓ Exported " . count($exportData) . " rows of user growth data\n";
    
    echo "\n✓ All platform analytics tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}