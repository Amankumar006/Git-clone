<?php
/**
 * Sitemap.xml generator
 * This file should be accessible at /sitemap.xml
 */

require_once __DIR__ . '/api/config/database.php';
require_once __DIR__ . '/api/utils/SEOService.php';

try {
    $seoService = new SEOService();
    
    header('Content-Type: application/xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
    
    echo $seoService->generateSitemap();
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "Error generating sitemap: " . $e->getMessage();
}