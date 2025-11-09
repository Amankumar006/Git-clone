-- Analytics tables for article tracking

-- Table for tracking article views
CREATE TABLE IF NOT EXISTS article_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NULL,
    user_agent TEXT,
    referrer VARCHAR(500),
    ip_address VARCHAR(45),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article_views_article_id (article_id),
    INDEX idx_article_views_user_id (user_id),
    INDEX idx_article_views_viewed_at (viewed_at)
);

-- Table for tracking when articles are actually read (not just viewed)
CREATE TABLE IF NOT EXISTS article_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NULL,
    time_spent INT DEFAULT 0, -- in milliseconds
    scroll_depth DECIMAL(5,2) DEFAULT 0, -- percentage (0-100)
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_article_read (article_id, user_id),
    INDEX idx_article_reads_article_id (article_id),
    INDEX idx_article_reads_user_id (user_id),
    INDEX idx_article_reads_read_at (read_at)
);

-- Table for detailed analytics data
CREATE TABLE IF NOT EXISTS article_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NULL,
    time_spent INT DEFAULT 0, -- in milliseconds
    scroll_depth DECIMAL(5,2) DEFAULT 0, -- percentage (0-100)
    is_read BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article_analytics_article_id (article_id),
    INDEX idx_article_analytics_user_id (user_id),
    INDEX idx_article_analytics_recorded_at (recorded_at)
);

-- Add indexes for better performance on existing tables
ALTER TABLE articles ADD INDEX idx_articles_status_published_at (status, published_at);
ALTER TABLE articles ADD INDEX idx_articles_author_status (author_id, status);
ALTER TABLE articles ADD INDEX idx_articles_view_count (view_count);
ALTER TABLE articles ADD INDEX idx_articles_clap_count (clap_count);

-- Create a view for article performance metrics
CREATE OR REPLACE VIEW article_performance AS
SELECT 
    a.id,
    a.title,
    a.author_id,
    a.status,
    a.published_at,
    a.view_count,
    a.clap_count,
    a.comment_count,
    COUNT(DISTINCT av.id) as detailed_views,
    COUNT(DISTINCT ar.id) as reads,
    AVG(ar.time_spent) as avg_time_spent,
    AVG(ar.scroll_depth) as avg_scroll_depth,
    (a.view_count + a.clap_count * 2 + a.comment_count * 3 + COUNT(DISTINCT ar.id) * 5) as engagement_score
FROM articles a
LEFT JOIN article_views av ON a.id = av.article_id
LEFT JOIN article_reads ar ON a.id = ar.article_id
WHERE a.status = 'published'
GROUP BY a.id;