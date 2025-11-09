-- Admin settings and configuration tables

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Medium Clone', 'string', 'Name of the platform'),
('site_description', 'A platform for sharing ideas and stories', 'string', 'Platform description'),
('registration_enabled', 'true', 'boolean', 'Whether new user registration is enabled'),
('content_approval_required', 'false', 'boolean', 'Whether articles require admin approval before publishing'),
('max_articles_per_day', '10', 'number', 'Maximum articles a user can publish per day'),
('max_comments_per_day', '50', 'number', 'Maximum comments a user can post per day'),
('featured_articles_limit', '5', 'number', 'Maximum number of featured articles on homepage'),
('email_notifications_enabled', 'true', 'boolean', 'Whether email notifications are enabled'),
('maintenance_mode', 'false', 'boolean', 'Whether the site is in maintenance mode'),
('max_upload_size', '5242880', 'number', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', '["jpg", "jpeg", "png", "gif"]', 'json', 'Allowed file types for uploads'),
('spam_detection_enabled', 'true', 'boolean', 'Whether automatic spam detection is enabled'),
('comment_moderation_enabled', 'false', 'boolean', 'Whether comments require moderation'),
('user_verification_required', 'false', 'boolean', 'Whether users must verify email before posting'),
('social_login_enabled', 'false', 'boolean', 'Whether social media login is enabled')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Featured content table
CREATE TABLE IF NOT EXISTS featured_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('article', 'user', 'publication') NOT NULL,
    content_id INT NOT NULL,
    position INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    featured_by INT NOT NULL,
    featured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (featured_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_content_type_active (content_type, is_active),
    INDEX idx_position (position)
);

-- Homepage sections configuration
CREATE TABLE IF NOT EXISTS homepage_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    section_type ENUM('featured', 'trending', 'latest', 'recommended', 'custom') NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    position INT DEFAULT 0,
    configuration JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_name (section_name),
    INDEX idx_position_enabled (position, is_enabled)
);

-- Insert default homepage sections
INSERT INTO homepage_sections (section_name, section_type, is_enabled, position, configuration) VALUES
('Featured Articles', 'featured', TRUE, 1, '{"limit": 5, "show_author": true, "show_stats": true}'),
('Trending Now', 'trending', TRUE, 2, '{"limit": 10, "time_period": "7d", "show_engagement": true}'),
('Latest Articles', 'latest', TRUE, 3, '{"limit": 20, "exclude_featured": true}'),
('Recommended for You', 'recommended', TRUE, 4, '{"limit": 15, "personalized": true}')
ON DUPLICATE KEY UPDATE configuration = VALUES(configuration);

-- Platform announcements
CREATE TABLE IF NOT EXISTS platform_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_active BOOLEAN DEFAULT TRUE,
    show_to_roles JSON DEFAULT '["user", "moderator", "admin"]',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active_expires (is_active, expires_at)
);