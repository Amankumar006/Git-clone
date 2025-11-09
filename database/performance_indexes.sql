-- Performance optimization indexes for Medium Clone
-- Execute these after the main database setup

-- Articles table indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_articles_author_status ON articles(author_id, status);
CREATE INDEX IF NOT EXISTS idx_articles_published_at ON articles(published_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_status_published ON articles(status, published_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_view_count ON articles(view_count DESC);
CREATE INDEX IF NOT EXISTS idx_articles_clap_count ON articles(clap_count DESC);
CREATE INDEX IF NOT EXISTS idx_articles_created_at ON articles(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_updated_at ON articles(updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_slug ON articles(slug);

-- Full-text search index for articles
CREATE FULLTEXT INDEX IF NOT EXISTS idx_articles_search ON articles(title, subtitle, content);

-- Comments table indexes
CREATE INDEX IF NOT EXISTS idx_comments_article_id ON comments(article_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_comments_user_id ON comments(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_comments_parent_id ON comments(parent_comment_id);

-- Claps table indexes
CREATE INDEX IF NOT EXISTS idx_claps_article_user ON claps(article_id, user_id);
CREATE INDEX IF NOT EXISTS idx_claps_article_id ON claps(article_id);
CREATE INDEX IF NOT EXISTS idx_claps_user_id ON claps(user_id);

-- Bookmarks table indexes
CREATE INDEX IF NOT EXISTS idx_bookmarks_user_created ON bookmarks(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_bookmarks_article_id ON bookmarks(article_id);

-- Follows table indexes
CREATE INDEX IF NOT EXISTS idx_follows_follower ON follows(follower_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_follows_following ON follows(following_id, created_at DESC);

-- Tags and article_tags indexes
CREATE INDEX IF NOT EXISTS idx_tags_name ON tags(name);
CREATE INDEX IF NOT EXISTS idx_tags_slug ON tags(slug);
CREATE INDEX IF NOT EXISTS idx_article_tags_article ON article_tags(article_id);
CREATE INDEX IF NOT EXISTS idx_article_tags_tag ON article_tags(tag_id);

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at DESC);

-- Publications table indexes
CREATE INDEX IF NOT EXISTS idx_publications_owner ON publications(owner_id);
CREATE INDEX IF NOT EXISTS idx_publications_created ON publications(created_at DESC);

-- Publication members indexes
CREATE INDEX IF NOT EXISTS idx_pub_members_publication ON publication_members(publication_id, role);
CREATE INDEX IF NOT EXISTS idx_pub_members_user ON publication_members(user_id);

-- Notifications table indexes
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type, created_at DESC);

-- Password resets indexes
CREATE INDEX IF NOT EXISTS idx_password_resets_email ON password_resets(email);
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);
CREATE INDEX IF NOT EXISTS idx_password_resets_expires ON password_resets(expires_at);

-- Composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_articles_author_published ON articles(author_id, status, published_at DESC);
CREATE INDEX IF NOT EXISTS idx_articles_publication_status ON articles(publication_id, status, published_at DESC);

-- Indexes for analytics queries
CREATE INDEX IF NOT EXISTS idx_articles_analytics ON articles(status, published_at, view_count, clap_count);
CREATE INDEX IF NOT EXISTS idx_claps_analytics ON claps(article_id, created_at);
CREATE INDEX IF NOT EXISTS idx_comments_analytics ON comments(article_id, created_at);

-- Optimize table statistics
ANALYZE TABLE articles;
ANALYZE TABLE users;
ANALYZE TABLE comments;
ANALYZE TABLE claps;
ANALYZE TABLE bookmarks;
ANALYZE TABLE follows;
ANALYZE TABLE tags;
ANALYZE TABLE article_tags;
ANALYZE TABLE publications;
ANALYZE TABLE publication_members;
ANALYZE TABLE notifications;