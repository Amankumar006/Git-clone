<?php

/**
 * SEO Service for generating sitemaps and managing SEO metadata
 */
class SEOService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate XML sitemap for all published articles
     */
    public function generateSitemap() {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Create urlset element
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
        $urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        $xml->appendChild($urlset);
        
        $baseUrl = $this->getBaseUrl();
        
        // Add homepage
        $this->addUrlToSitemap($xml, $urlset, $baseUrl, date('c'), 'daily', '1.0');
        
        // Add static pages
        $staticPages = [
            '/about' => ['weekly', '0.8'],
            '/contact' => ['monthly', '0.6'],
            '/privacy' => ['yearly', '0.3'],
            '/terms' => ['yearly', '0.3']
        ];
        
        foreach ($staticPages as $page => $config) {
            $this->addUrlToSitemap($xml, $urlset, $baseUrl . $page, date('c'), $config[0], $config[1]);
        }
        
        // Add published articles
        $articles = $this->getPublishedArticles();
        foreach ($articles as $article) {
            $articleUrl = $baseUrl . '/article/' . $article['slug'];
            $lastmod = $article['updated_at'];
            
            $url = $xml->createElement('url');
            
            // Basic URL info
            $loc = $xml->createElement('loc', htmlspecialchars($articleUrl));
            $url->appendChild($loc);
            
            $lastmodElement = $xml->createElement('lastmod', date('c', strtotime($lastmod)));
            $url->appendChild($lastmodElement);
            
            $changefreq = $xml->createElement('changefreq', 'weekly');
            $url->appendChild($changefreq);
            
            $priority = $xml->createElement('priority', '0.9');
            $url->appendChild($priority);
            
            // Add image if featured image exists
            if (!empty($article['featured_image_url'])) {
                $image = $xml->createElement('image:image');
                $imageLoc = $xml->createElement('image:loc', htmlspecialchars($article['featured_image_url']));
                $imageTitle = $xml->createElement('image:title', htmlspecialchars($article['title']));
                $imageCaption = $xml->createElement('image:caption', htmlspecialchars($article['subtitle'] ?? ''));
                
                $image->appendChild($imageLoc);
                $image->appendChild($imageTitle);
                if (!empty($article['subtitle'])) {
                    $image->appendChild($imageCaption);
                }
                
                $url->appendChild($image);
            }
            
            // Add news sitemap data for recent articles (last 2 days)
            if (strtotime($article['published_at']) > strtotime('-2 days')) {
                $news = $xml->createElement('news:news');
                
                $publication = $xml->createElement('news:publication');
                $pubName = $xml->createElement('news:name', 'Medium Clone');
                $pubLanguage = $xml->createElement('news:language', 'en');
                $publication->appendChild($pubName);
                $publication->appendChild($pubLanguage);
                
                $newsArticle = $xml->createElement('news:publication_date', date('c', strtotime($article['published_at'])));
                $newsTitle = $xml->createElement('news:title', htmlspecialchars($article['title']));
                
                $news->appendChild($publication);
                $news->appendChild($newsArticle);
                $news->appendChild($newsTitle);
                
                $url->appendChild($news);
            }
            
            $urlset->appendChild($url);
        }
        
        // Add user profiles
        $users = $this->getActiveUsers();
        foreach ($users as $user) {
            $userUrl = $baseUrl . '/user/' . $user['username'];
            $this->addUrlToSitemap($xml, $urlset, $userUrl, $user['updated_at'], 'weekly', '0.7');
        }
        
        // Add tag pages
        $tags = $this->getPopularTags();
        foreach ($tags as $tag) {
            $tagUrl = $baseUrl . '/tag/' . $tag['slug'];
            $this->addUrlToSitemap($xml, $urlset, $tagUrl, date('c'), 'daily', '0.8');
        }
        
        return $xml->saveXML();
    }
    
    /**
     * Generate robots.txt content
     */
    public function generateRobotsTxt() {
        $baseUrl = $this->getBaseUrl();
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /api/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /dashboard/\n";
        $robots .= "Disallow: /editor/\n";
        $robots .= "Disallow: /settings/\n";
        $robots .= "\n";
        $robots .= "Sitemap: {$baseUrl}/sitemap.xml\n";
        $robots .= "\n";
        $robots .= "# Crawl-delay for respectful crawling\n";
        $robots .= "Crawl-delay: 1\n";
        
        return $robots;
    }
    
    /**
     * Generate structured data for article
     */
    public function generateArticleStructuredData($article) {
        $baseUrl = $this->getBaseUrl();
        
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $article['title'],
            "description" => $article['subtitle'] ?? $this->generateMetaDescription($article['content']),
            "author" => [
                "@type" => "Person",
                "name" => $article['username'],
                "url" => $baseUrl . '/user/' . $article['username']
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => "Medium Clone",
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $baseUrl . "/logo.png"
                ]
            ],
            "datePublished" => date('c', strtotime($article['published_at'])),
            "dateModified" => date('c', strtotime($article['updated_at'])),
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $baseUrl . '/article/' . $article['slug']
            ]
        ];
        
        // Add image if exists
        if (!empty($article['featured_image_url'])) {
            $structuredData["image"] = [$article['featured_image_url']];
        }
        
        // Add article body word count and reading time
        $wordCount = str_word_count(strip_tags($article['content']));
        $structuredData["wordCount"] = $wordCount;
        
        if (!empty($article['reading_time'])) {
            $structuredData["timeRequired"] = "PT{$article['reading_time']}M";
        }
        
        // Add tags as keywords
        if (!empty($article['tags'])) {
            $tags = is_string($article['tags']) ? json_decode($article['tags'], true) : $article['tags'];
            if (is_array($tags)) {
                $structuredData["keywords"] = implode(", ", array_column($tags, 'name'));
            }
        }
        
        return $structuredData;
    }
    
    /**
     * Generate meta description from content
     */
    public function generateMetaDescription($content, $maxLength = 160) {
        // Strip HTML tags and get plain text
        $plainText = strip_tags($content);
        $plainText = preg_replace('/\s+/', ' ', trim($plainText));
        
        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }
        
        // Find the last complete sentence within the limit
        $truncated = substr($plainText, 0, $maxLength);
        $lastSentence = strrpos($truncated, '.');
        
        if ($lastSentence > $maxLength * 0.7) {
            return substr($plainText, 0, $lastSentence + 1);
        }
        
        // If no good sentence break, truncate at word boundary
        $lastSpace = strrpos($truncated, ' ');
        return substr($plainText, 0, $lastSpace) . '...';
    }
    
    /**
     * Get canonical URL for article
     */
    public function getCanonicalUrl($article) {
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/article/' . $article['slug'];
    }
    
    /**
     * Generate Open Graph metadata
     */
    public function generateOpenGraphData($article) {
        $baseUrl = $this->getBaseUrl();
        
        return [
            'og:title' => $article['title'],
            'og:description' => $article['subtitle'] ?? $this->generateMetaDescription($article['content']),
            'og:type' => 'article',
            'og:url' => $this->getCanonicalUrl($article),
            'og:image' => $article['featured_image_url'] ?? $baseUrl . '/default-og-image.png',
            'og:site_name' => 'Medium Clone',
            'article:author' => $baseUrl . '/user/' . $article['username'],
            'article:published_time' => date('c', strtotime($article['published_at'])),
            'article:modified_time' => date('c', strtotime($article['updated_at'])),
            'article:section' => 'Blog',
            'article:tag' => $this->getArticleTagNames($article['id'])
        ];
    }
    
    /**
     * Generate Twitter Card metadata
     */
    public function generateTwitterCardData($article) {
        return [
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $article['title'],
            'twitter:description' => $article['subtitle'] ?? $this->generateMetaDescription($article['content']),
            'twitter:image' => $article['featured_image_url'] ?? $this->getBaseUrl() . '/default-twitter-image.png',
            'twitter:creator' => '@' . $article['username'],
            'twitter:site' => '@mediumclone'
        ];
    }
    
    /**
     * Helper methods
     */
    private function addUrlToSitemap($xml, $urlset, $url, $lastmod, $changefreq, $priority) {
        $urlElement = $xml->createElement('url');
        
        $loc = $xml->createElement('loc', htmlspecialchars($url));
        $urlElement->appendChild($loc);
        
        $lastmodElement = $xml->createElement('lastmod', date('c', strtotime($lastmod)));
        $urlElement->appendChild($lastmodElement);
        
        $changefreqElement = $xml->createElement('changefreq', $changefreq);
        $urlElement->appendChild($changefreqElement);
        
        $priorityElement = $xml->createElement('priority', $priority);
        $urlElement->appendChild($priorityElement);
        
        $urlset->appendChild($urlElement);
    }
    
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
    
    private function getPublishedArticles() {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username, 
                   GROUP_CONCAT(t.name) as tag_names
            FROM articles a 
            JOIN users u ON a.author_id = u.id 
            LEFT JOIN article_tags at ON a.id = at.article_id
            LEFT JOIN tags t ON at.tag_id = t.id
            WHERE a.status = 'published' 
            GROUP BY a.id
            ORDER BY a.published_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getActiveUsers() {
        $stmt = $this->db->prepare("
            SELECT username, updated_at 
            FROM users 
            WHERE id IN (
                SELECT DISTINCT author_id 
                FROM articles 
                WHERE status = 'published'
            )
            ORDER BY updated_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPopularTags() {
        $stmt = $this->db->prepare("
            SELECT t.name, t.slug, MAX(a.updated_at) as updated_at
            FROM tags t
            JOIN article_tags at ON t.id = at.tag_id
            JOIN articles a ON at.article_id = a.id
            WHERE a.status = 'published'
            GROUP BY t.id
            ORDER BY COUNT(at.article_id) DESC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getArticleTagNames($articleId) {
        $stmt = $this->db->prepare("
            SELECT t.name 
            FROM tags t
            JOIN article_tags at ON t.id = at.tag_id
            WHERE at.article_id = ?
        ");
        $stmt->execute([$articleId]);
        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $tags);
    }
}