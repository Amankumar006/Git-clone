<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/SEOService.php';

class SEOController {
    private $seoService;
    
    public function __construct() {
        $this->seoService = new SEOService();
    }
    
    /**
     * Generate and serve XML sitemap
     */
    public function generateSitemap() {
        try {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
            
            $sitemap = $this->seoService->generateSitemap();
            echo $sitemap;
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate sitemap: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate and serve robots.txt
     */
    public function generateRobotsTxt() {
        try {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
            
            $robots = $this->seoService->generateRobotsTxt();
            echo $robots;
            
        } catch (Exception $e) {
            http_response_code(500);
            echo "# Error generating robots.txt";
        }
    }
    
    /**
     * Get SEO metadata for a specific article
     */
    public function getArticleSEO() {
        try {
            $articleId = $_GET['id'] ?? null;
            $slug = $_GET['slug'] ?? null;
            
            if (!$articleId && !$slug) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Article ID or slug is required'
                ]);
                return;
            }
            
            $article = $this->getArticleData($articleId, $slug);
            
            if (!$article) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Article not found'
                ]);
                return;
            }
            
            $seoData = [
                'structured_data' => $this->seoService->generateArticleStructuredData($article),
                'open_graph' => $this->seoService->generateOpenGraphData($article),
                'twitter_card' => $this->seoService->generateTwitterCardData($article),
                'canonical_url' => $this->seoService->getCanonicalUrl($article),
                'meta_description' => $this->seoService->generateMetaDescription($article['content'])
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $seoData
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate SEO data: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update article slug
     */
    public function updateArticleSlug() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $articleId = $input['article_id'] ?? null;
            $newSlug = $input['slug'] ?? null;
            
            if (!$articleId || !$newSlug) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Article ID and slug are required'
                ]);
                return;
            }
            
            // Validate slug format
            if (!preg_match('/^[a-z0-9-]+$/', $newSlug)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid slug format. Use only lowercase letters, numbers, and hyphens.'
                ]);
                return;
            }
            
            // Check if slug is unique
            if ($this->isSlugTaken($newSlug, $articleId)) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'error' => 'Slug is already taken'
                ]);
                return;
            }
            
            // Update the slug
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE articles SET slug = ? WHERE id = ?");
            $stmt->execute([$newSlug, $articleId]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Slug updated successfully',
                'data' => ['slug' => $newSlug]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update slug: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate sitemap index for large sites
     */
    public function generateSitemapIndex() {
        try {
            header('Content-Type: application/xml; charset=utf-8');
            
            $xml = new DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            
            $sitemapindex = $xml->createElement('sitemapindex');
            $sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $xml->appendChild($sitemapindex);
            
            $baseUrl = $this->getBaseUrl();
            
            // Main sitemap
            $sitemap = $xml->createElement('sitemap');
            $loc = $xml->createElement('loc', $baseUrl . '/sitemap.xml');
            $lastmod = $xml->createElement('lastmod', date('c'));
            $sitemap->appendChild($loc);
            $sitemap->appendChild($lastmod);
            $sitemapindex->appendChild($sitemap);
            
            // Articles sitemap (if we have many articles, we could split by date)
            $articlesSitemap = $xml->createElement('sitemap');
            $articlesLoc = $xml->createElement('loc', $baseUrl . '/sitemap-articles.xml');
            $articlesLastmod = $xml->createElement('lastmod', date('c'));
            $articlesSitemap->appendChild($articlesLoc);
            $articlesSitemap->appendChild($articlesLastmod);
            $sitemapindex->appendChild($articlesSitemap);
            
            echo $xml->saveXML();
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate sitemap index: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get article data by ID or slug
     */
    private function getArticleData($articleId = null, $slug = null) {
        $db = Database::getInstance()->getConnection();
        
        if ($articleId) {
            $stmt = $db->prepare("
                SELECT a.*, u.username 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.id = ? AND a.status = 'published'
            ");
            $stmt->execute([$articleId]);
        } else {
            $stmt = $db->prepare("
                SELECT a.*, u.username 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.slug = ? AND a.status = 'published'
            ");
            $stmt->execute([$slug]);
        }
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if slug is already taken
     */
    private function isSlugTaken($slug, $excludeArticleId = null) {
        $db = Database::getInstance()->getConnection();
        
        if ($excludeArticleId) {
            $stmt = $db->prepare("SELECT id FROM articles WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $excludeArticleId]);
        } else {
            $stmt = $db->prepare("SELECT id FROM articles WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        
        return $stmt->fetch() !== false;
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}