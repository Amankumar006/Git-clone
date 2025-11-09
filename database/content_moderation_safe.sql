-- Content Moderation System Tables (Safe Version)

-- Reports table for user-reported content
CREATE TABLE IF NOT EXISTS reports (
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
CREATE TABLE IF NOT EXISTS moderation_actions (
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
CREATE TABLE IF NOT EXISTS user_penalties (
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
CREATE TABLE IF NOT EXISTS content_flags (
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

-- Check and add columns to users table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = DATABASE()
     AND column_name = 'role') > 0,
    'SELECT "Column role already exists"',
    'ALTER TABLE users ADD COLUMN role ENUM(''user'', ''admin'', ''moderator'') DEFAULT ''user'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = DATABASE()
     AND column_name = 'is_suspended') > 0,
    'SELECT "Column is_suspended already exists"',
    'ALTER TABLE users ADD COLUMN is_suspended BOOLEAN DEFAULT FALSE'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' 
     AND table_schema = DATABASE()
     AND column_name = 'suspension_expires_at') > 0,
    'SELECT "Column suspension_expires_at already exists"',
    'ALTER TABLE users ADD COLUMN suspension_expires_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add columns to articles table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'articles' 
     AND table_schema = DATABASE()
     AND column_name = 'moderation_status') > 0,
    'SELECT "Column moderation_status already exists"',
    'ALTER TABLE articles ADD COLUMN moderation_status ENUM(''approved'', ''pending'', ''flagged'', ''removed'') DEFAULT ''approved'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'articles' 
     AND table_schema = DATABASE()
     AND column_name = 'flagged_at') > 0,
    'SELECT "Column flagged_at already exists"',
    'ALTER TABLE articles ADD COLUMN flagged_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'articles' 
     AND table_schema = DATABASE()
     AND column_name = 'moderated_by') > 0,
    'SELECT "Column moderated_by already exists"',
    'ALTER TABLE articles ADD COLUMN moderated_by INT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'articles' 
     AND table_schema = DATABASE()
     AND column_name = 'is_featured') > 0,
    'SELECT "Column is_featured already exists"',
    'ALTER TABLE articles ADD COLUMN is_featured BOOLEAN DEFAULT FALSE'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'articles' 
     AND table_schema = DATABASE()
     AND column_name = 'featured_at') > 0,
    'SELECT "Column featured_at already exists"',
    'ALTER TABLE articles ADD COLUMN featured_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add columns to comments table
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'comments' 
     AND table_schema = DATABASE()
     AND column_name = 'moderation_status') > 0,
    'SELECT "Column moderation_status already exists"',
    'ALTER TABLE comments ADD COLUMN moderation_status ENUM(''approved'', ''pending'', ''flagged'', ''removed'') DEFAULT ''approved'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'comments' 
     AND table_schema = DATABASE()
     AND column_name = 'flagged_at') > 0,
    'SELECT "Column flagged_at already exists"',
    'ALTER TABLE comments ADD COLUMN flagged_at TIMESTAMP NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'comments' 
     AND table_schema = DATABASE()
     AND column_name = 'moderated_by') > 0,
    'SELECT "Column moderated_by already exists"',
    'ALTER TABLE comments ADD COLUMN moderated_by INT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for performance (only if they don't exist)
CREATE INDEX IF NOT EXISTS idx_articles_moderation ON articles(moderation_status, flagged_at);
CREATE INDEX IF NOT EXISTS idx_comments_moderation ON comments(moderation_status, flagged_at);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_suspended ON users(is_suspended, suspension_expires_at);
CREATE INDEX IF NOT EXISTS idx_articles_featured ON articles(is_featured, featured_at);