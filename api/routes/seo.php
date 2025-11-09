<?php

require_once __DIR__ . '/../controllers/SEOController.php';

$seoController = new SEOController();

// Handle different SEO endpoints
switch ($endpoint) {
    case 'sitemap':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $seoController->generateSitemap();
        }
        break;
        
    case 'sitemap-index':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $seoController->generateSitemapIndex();
        }
        break;
        
    case 'robots':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $seoController->generateRobotsTxt();
        }
        break;
        
    case 'article-seo':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $seoController->getArticleSEO();
        }
        break;
        
    case 'update-slug':
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $seoController->updateArticleSlug();
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'SEO endpoint not found'
        ]);
        break;
}