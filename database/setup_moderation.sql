-- Content Moderation System Tables

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
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
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
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
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
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
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
    UNIQUE KEY unique_content_flag (content_type, content_id, flag_type)
);