-- Content Moderation System Tables

-- Reports table for user-reported content
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_content_type ENUM('article', 'comment', 'user') NOT NULL,
    reported_content_id INT NOT NULL,
    reason ENUM('spam', 'harassment', 'inappropriate', 'copyright', 'misinformation', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewing', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_id INT NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reports_status (status),
    INDEX idx_reports_content (reported_content_type, reported_content_id),
    INDEX idx_reports_created (created_at DESC)
);

-- Moderation actions log
CREATE TABLE moderation_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type ENUM('approve', 'remove', 'edit', 'warn', 'suspend', 'ban') NOT NULL,
    target_type ENUM('article', 'comment', 'user') NOT NULL,
    target_id INT NOT NULL,
    reason TEXT,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_moderation_admin (admin_id),
    INDEX idx_moderation_target (target_type, target_id),
    INDEX idx_moderation_created (created_at DESC)
);

-- User warnings and penalties
CREATE TABLE user_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    penalty_type ENUM('warning', 'temporary_suspension', 'permanent_ban') NOT NULL,
    reason TEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_penalties_user (user_id),
    INDEX idx_penalties_active (is_active, expires_at)
);

-- Content flags for automated filtering
CREATE TABLE content_flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('article', 'comment') NOT NULL,
    content_id INT NOT NULL,
    flag_type ENUM('spam_detected', 'profanity_detected', 'suspicious_links', 'duplicate_content') NOT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    auto_action ENUM('none', 'flag_for_review', 'auto_remove') DEFAULT 'none',
    reviewed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_flags_content (content_type, content_id),
    INDEX idx_flags_reviewed (reviewed, created_at),
    UNIQUE KEY unique_content_flag (content_type, content_id, flag_type)
);

-- Add admin role to users table if not exists
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'moderator') DEFAULT 'user';
ALTER TABLE users ADD COLUMN is_suspended BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN suspension_expires_at TIMESTAMP NULL;

-- Add moderation status to articles
ALTER TABLE articles ADD COLUMN moderation_status ENUM('approved', 'pending', 'flagged', 'removed') DEFAULT 'approved';
ALTER TABLE articles ADD COLUMN flagged_at TIMESTAMP NULL;
ALTER TABLE articles ADD COLUMN moderated_by INT NULL;
ALTER TABLE articles ADD FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add moderation status to comments
ALTER TABLE comments ADD COLUMN moderation_status ENUM('approved', 'pending', 'flagged', 'removed') DEFAULT 'approved';
ALTER TABLE comments ADD COLUMN flagged_at TIMESTAMP NULL;
ALTER TABLE comments ADD COLUMN moderated_by INT NULL;
ALTER TABLE comments ADD FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;

-- Indexes for performance
CREATE INDEX idx_articles_moderation ON articles(moderation_status, flagged_at);
CREATE INDEX idx_comments_moderation ON comments(moderation_status, flagged_at);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_suspended ON users(is_suspended, suspension_expires_at);
-- Add featured article columns
ALTER TABLE articles ADD COLUMN is_featured BOOLEAN DEFAULT FALSE;
ALTER TABLE articles ADD COLUMN featured_at TIMESTAMP NULL;

-- Add indexes for featured articles
CREATE INDEX idx_articles_featured ON articles(is_featured, featured_at);