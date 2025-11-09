<?php
/**
 * Robots.txt generator
 * This file should be accessible at /robots.txt
 */

require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/utils/SEOService.php';

try {
    $seoService = new SEOService();
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
    
    echo $seoService->generateRobotsTxt();
    
} catch (Exception $e) {
    http_response_code(500);
    echo "# Error generating robots.txt";
}