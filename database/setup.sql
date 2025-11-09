-- Medium Clone Database Schema
-- Execute this script in phpMyAdmin to create all required tables

-- Create database (uncomment if needed)
-- CREATE DATABASE medium_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE medium_clone;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_image_url VARCHAR(500),
    social_links TEXT,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Publications table (created before articles for foreign key)
CREATE TABLE publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    logo_url VARCHAR(500),
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id)
);

-- Articles table
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,      
    author_id INT NOT NULL,
    publication_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NULL,
    subtitle VARCHAR(500),
    content TEXT NOT NULL,
    featured_image_url VARCHAR(500),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    reading_time INT DEFAULT 0,
    view_count INT DEFAULT 0,
    clap_count INT DEFAULT 0,
    comment_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE SET NULL,
    INDEX idx_author_status (author_id, status),
    INDEX idx_published_at (published_at DESC),
    INDEX idx_status (status),
    INDEX idx_view_count (view_count DESC),
    INDEX idx_clap_count (clap_count DESC),
    INDEX idx_articles_slug (slug),
    FULLTEXT INDEX idx_articles_search (title, subtitle, content)
);

-- Tags table
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tags_name (name),
    INDEX idx_slug (slug)
);

-- Article tags relationship table
CREATE TABLE article_tags (
    article_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (article_id, tag_id),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Comments table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_comments_article_id (article_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_comment (parent_comment_id),
    INDEX idx_created_at (created_at DESC)
);

-- Claps table
CREATE TABLE claps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    article_id INT NOT NULL,
    count INT DEFAULT 1 CHECK (count <= 50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_article (user_id, article_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    INDEX idx_claps_article_user (article_id, user_id),
    INDEX idx_created_at (created_at DESC)
);

-- Bookmarks table
CREATE TABLE bookmarks (
    user_id INT NOT NULL,
    article_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, article_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- Follows table
CREATE TABLE follows (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (follower_id != following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);

-- Publication members table
CREATE TABLE publication_members (
    publication_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'editor', 'writer') DEFAULT 'writer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publication_id, user_id),
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('follow', 'clap', 'comment', 'publication_invite') NOT NULL,
    content TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at DESC)
);

-- Password resets table
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
);

-- Additional performance indexes for complex queries
-- These indexes optimize common query patterns for better performance

-- Composite index for article discovery queries (MySQL 8.0+ supports WHERE clause)
-- For older MySQL versions, this will create a regular composite index
CREATE INDEX idx_articles_status_published ON articles(status, published_at DESC);

-- Index for trending articles (high engagement in recent time)
CREATE INDEX idx_articles_engagement ON articles(published_at DESC, clap_count DESC, comment_count DESC, view_count DESC);

-- Index for user activity queries
CREATE INDEX idx_user_activity ON notifications(user_id, created_at DESC, is_read);

-- Index for publication article queries
CREATE INDEX idx_publication_articles ON articles(publication_id, status, published_at DESC);

-- Index for tag popularity queries
CREATE INDEX idx_tag_usage ON article_tags(tag_id, article_id);

-- Index for user following feed queries
CREATE INDEX idx_following_feed ON articles(author_id, status, published_at DESC);

-- Index for bookmark queries
CREATE INDEX idx_bookmarks_user_date ON bookmarks(user_id, created_at DESC);

-- Index for comment threading
CREATE INDEX idx_comment_thread ON comments(article_id, parent_comment_id, created_at ASC);