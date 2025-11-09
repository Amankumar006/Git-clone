<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/PlatformAnalytics.php';

class AnalyticsController extends BaseController {
    private $analyticsModel;

    public function __construct() {
        parent::__construct();
        $this->analyticsModel = new PlatformAnalytics();
    }

    public function getPlatformAnalytics() {
        try {
            $this->requireAdmin();
            
            $days = (int)($_GET['days'] ?? 30);
            $type = $_GET['type'] ?? 'overview';
            
            $analytics = [];
            
            switch ($type) {
                case 'overview':
                    $analytics = [
                        'user_growth' => $this->analyticsModel->getUserGrowthAnalytics($days),
                        'content' => $this->analyticsModel->getContentAnalytics($days),
                        'engagement' => $this->analyticsModel->getEngagementAnalytics($days),
                        'health_metrics' => $this->analyticsModel->getPlatformHealthMetrics(),
                        'comparative' => $this->analyticsModel->getComparativeAnalytics($days, $days)
                    ];
                    break;
                    
                case 'user_growth':
                    $analytics = $this->analyticsModel->getUserGrowthAnalytics($days);
                    break;
                    
                case 'content':
                    $analytics = $this->analyticsModel->getContentAnalytics($days);
                    break;
                    
                case 'engagement':
                    $analytics = $this->analyticsModel->getEngagementAnalytics($days);
                    break;
                    
                case 'top_content':
                    $limit = (int)($_GET['limit'] ?? 10);
                    $analytics = [
                        'top_articles' => $this->analyticsModel->getTopArticles($days, $limit),
                        'trending_topics' => $this->analyticsModel->getTrendingTopics($days, $limit)
                    ];
                    break;
                    
                case 'patterns':
                    $analytics = [
                        'engagement_patterns' => $this->analyticsModel->getUserEngagementPatterns($days),
                        'retention' => $this->analyticsModel->getRetentionAnalytics($days)
                    ];
                    break;
                    
                default:
                    throw new Exception('Invalid analytics type');
            }
            
            $this->sendResponse($analytics);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getComparativeAnalytics() {
        try {
            $this->requireAdmin();
            
            $currentDays = (int)($_GET['current_days'] ?? 30);
            $previousDays = (int)($_GET['previous_days'] ?? 30);
            
            $comparative = $this->analyticsModel->getComparativeAnalytics($currentDays, $previousDays);
            
            $this->sendResponse([
                'comparative_data' => $comparative,
                'current_period' => $currentDays,
                'previous_period' => $previousDays
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function exportAnalytics() {
        try {
            $this->requireAdmin();
            
            $type = $_GET['type'] ?? 'user_growth';
            $days = (int)($_GET['days'] ?? 30);
            $format = $_GET['format'] ?? 'json';
            
            $data = $this->analyticsModel->exportAnalyticsData($type, $days);
            
            if ($format === 'csv') {
                $this->exportAsCSV($data, $type);
            } else {
                $this->sendResponse([
                    'data' => $data,
                    'type' => $type,
                    'period_days' => $days,
                    'exported_at' => date('Y-m-d H:i:s')
                ]);
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getHealthMetrics() {
        try {
            $this->requireAdmin();
            
            $metrics = $this->analyticsModel->getPlatformHealthMetrics();
            
            // Format metrics for easier consumption
            $formattedMetrics = [];
            foreach ($metrics as $metric) {
                $formattedMetrics[$metric['metric']] = $metric['value'];
            }
            
            // Add some calculated metrics
            $formattedMetrics['health_score'] = $this->calculateHealthScore($formattedMetrics);
            
            $this->sendResponse([
                'metrics' => $formattedMetrics,
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => $this->getHealthStatus($formattedMetrics['health_score'])
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getRetentionAnalytics() {
        try {
            $this->requireAdmin();
            
            $cohortDays = (int)($_GET['cohort_days'] ?? 30);
            
            $retention = $this->analyticsModel->getRetentionAnalytics($cohortDays);
            
            // Calculate average retention rates
            $avgRetention = [
                'day_1' => 0,
                'day_7' => 0,
                'day_30' => 0
            ];
            
            $totalCohorts = count($retention);
            if ($totalCohorts > 0) {
                foreach ($retention as $cohort) {
                    if ($cohort['cohort_size'] > 0) {
                        $avgRetention['day_1'] += ($cohort['day_1_retention'] / $cohort['cohort_size']) * 100;
                        $avgRetention['day_7'] += ($cohort['day_7_retention'] / $cohort['cohort_size']) * 100;
                        $avgRetention['day_30'] += ($cohort['day_30_retention'] / $cohort['cohort_size']) * 100;
                    }
                }
                
                $avgRetention['day_1'] = round($avgRetention['day_1'] / $totalCohorts, 2);
                $avgRetention['day_7'] = round($avgRetention['day_7'] / $totalCohorts, 2);
                $avgRetention['day_30'] = round($avgRetention['day_30'] / $totalCohorts, 2);
            }
            
            $this->sendResponse([
                'retention_cohorts' => $retention,
                'average_retention' => $avgRetention,
                'cohort_period_days' => $cohortDays
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getEngagementPatterns() {
        try {
            $this->requireAdmin();
            
            $days = (int)($_GET['days'] ?? 30);
            
            $patterns = $this->analyticsModel->getUserEngagementPatterns($days);
            
            // Organize data by activity type and create heatmap data
            $heatmapData = [
                'articles' => [],
                'comments' => []
            ];
            
            foreach ($patterns as $pattern) {
                $type = $pattern['activity_type'];
                $dayOfWeek = $pattern['day_of_week'];
                $hour = $pattern['hour_of_day'];
                
                if (!isset($heatmapData[$type][$dayOfWeek])) {
                    $heatmapData[$type][$dayOfWeek] = [];
                }
                
                $heatmapData[$type][$dayOfWeek][$hour] = (int)$pattern['activity_count'];
            }
            
            $this->sendResponse([
                'patterns' => $patterns,
                'heatmap_data' => $heatmapData,
                'period_days' => $days
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    private function exportAsCSV($data, $type) {
        if (empty($data)) {
            throw new Exception('No data to export');
        }
        
        $filename = "analytics_{$type}_" . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        
        $output = fopen('php://output', 'w');
        
        // Write CSV headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    private function calculateHealthScore($metrics) {
        $score = 0;
        
        // Active users score (0-30 points)
        $activeUsers = $metrics['active_users_24h'] ?? 0;
        $score += min(30, $activeUsers * 2);
        
        // Articles published score (0-25 points)
        $articlesPublished = $metrics['articles_published_24h'] ?? 0;
        $score += min(25, $articlesPublished * 5);
        
        // Engagement rate score (0-25 points)
        $engagementRate = $metrics['engagement_rate'] ?? 0;
        $score += min(25, $engagementRate * 2.5);
        
        // Reading time score (0-20 points) - optimal around 5-10 minutes
        $avgReadingTime = $metrics['avg_reading_time'] ?? 0;
        if ($avgReadingTime >= 5 && $avgReadingTime <= 10) {
            $score += 20;
        } elseif ($avgReadingTime > 0) {
            $score += max(0, 20 - abs($avgReadingTime - 7.5) * 2);
        }
        
        return min(100, round($score));
    }

    private function getHealthStatus($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        if ($score >= 20) return 'poor';
        return 'critical';
    }

    private function requireAdmin() {
        $this->requireAuth();
        
        if (!in_array($this->currentUser['role'], ['admin', 'moderator'])) {
            throw new Exception('Admin access required');
        }
    }
}