-- Search functionality tables for Medium Clone

-- Table for logging search queries (for analytics)
CREATE TABLE IF NOT EXISTS search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(255) NOT NULL,
    user_id INT NULL,
    results_count INT DEFAULT 0,
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_query (query),
    INDEX idx_search_user (user_id),
    INDEX idx_search_date (searched_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table for saved searches by users
CREATE TABLE IF NOT EXISTS saved_searches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    query VARCHAR(255) NOT NULL,
    filters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_search (user_id, name),
    INDEX idx_saved_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add full-text search indexes for better search performance
-- Note: These may already exist, so we use IF NOT EXISTS equivalent

-- Full-text index on articles for better search
ALTER TABLE articles ADD FULLTEXT(title, subtitle);

-- Index for better tag search performance
CREATE INDEX IF NOT EXISTS idx_tags_name_fulltext ON tags(name);

-- Indexes for better search performance on article_tags
CREATE INDEX IF NOT EXISTS idx_article_tags_article ON article_tags(article_id);
CREATE INDEX IF NOT EXISTS idx_article_tags_tag ON article_tags(tag_id);

-- Index for user search
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_bio ON users(bio(100));

-- Composite indexes for common search queries
CREATE INDEX IF NOT EXISTS idx_articles_status_published ON articles(status, published_at);
CREATE INDEX IF NOT EXISTS idx_articles_author_status ON articles(author_id, status);

-- Index for trending and popular content queries
CREATE INDEX IF NOT EXISTS idx_articles_engagement ON articles(view_count, clap_count, comment_count);
CREATE INDEX IF NOT EXISTS idx_articles_published_engagement ON articles(published_at, view_count, clap_count);

-- Table for tag following functionality
CREATE TABLE IF NOT EXISTS tag_follows (
    user_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, tag_id),
    INDEX idx_tag_follows_user (user_id),
    INDEX idx_tag_follows_tag (tag_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);