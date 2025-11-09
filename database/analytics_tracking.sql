-- Analytics tracking tables for comprehensive monitoring

-- Analytics events table for tracking all user interactions
CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    label VARCHAR(255),
    value INT,
    session_id VARCHAR(100),
    user_id INT,
    url TEXT,
    referrer TEXT,
    user_agent TEXT,
    custom_parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action (action),
    INDEX idx_category (category),
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action_category (action, category),
    INDEX idx_user_created (user_id, created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Page views table for detailed page analytics
CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(500) NOT NULL,
    title VARCHAR(255),
    session_id VARCHAR(100),
    user_id INT,
    referrer TEXT,
    user_agent TEXT,
    ip_address VARCHAR(45),
    country VARCHAR(2),
    device_type ENUM('desktop', 'mobile', 'tablet'),
    browser VARCHAR(50),
    os VARCHAR(50),
    screen_resolution VARCHAR(20),
    viewport_size VARCHAR(20),
    load_time INT, -- in milliseconds
    time_on_page INT, -- in seconds
    scroll_depth INT, -- percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_url (url),
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_device_type (device_type),
    INDEX idx_country (country),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- User sessions table for session analytics
CREATE TABLE IF NOT EXISTS user_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) UNIQUE NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    country VARCHAR(2),
    device_type ENUM('desktop', 'mobile', 'tablet'),
    browser VARCHAR(50),
    os VARCHAR(50),
    referrer TEXT,
    landing_page TEXT,
    exit_page TEXT,
    page_views_count INT DEFAULT 0,
    session_duration INT DEFAULT 0, -- in seconds
    is_bounce BOOLEAN DEFAULT FALSE,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_started_at (started_at),
    INDEX idx_device_type (device_type),
    INDEX idx_country (country),
    INDEX idx_is_bounce (is_bounce),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Performance metrics table
CREATE TABLE IF NOT EXISTS performance_metrics (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,3) NOT NULL,
    url VARCHAR(500),
    session_id VARCHAR(100),
    user_id INT,
    user_agent TEXT,
    device_type ENUM('desktop', 'mobile', 'tablet'),
    connection_type VARCHAR(50),
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_metric_name (metric_name),
    INDEX idx_url (url),
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_device_type (device_type),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Error tracking table
CREATE TABLE IF NOT EXISTS error_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    error_type VARCHAR(100) NOT NULL,
    error_message TEXT NOT NULL,
    error_stack TEXT,
    url VARCHAR(500),
    user_id INT,
    session_id VARCHAR(100),
    user_agent TEXT,
    browser VARCHAR(50),
    os VARCHAR(50),
    context_data JSON,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    
    INDEX idx_error_type (error_type),
    INDEX idx_severity (severity),
    INDEX idx_is_resolved (is_resolved),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Conversion events table
CREATE TABLE IF NOT EXISTS conversion_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    user_id INT,
    session_id VARCHAR(100),
    value DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'USD',
    funnel_step VARCHAR(100),
    source VARCHAR(100),
    medium VARCHAR(100),
    campaign VARCHAR(100),
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_name (event_name),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at),
    INDEX idx_funnel_step (funnel_step),
    INDEX idx_source (source),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- A/B test tracking table
CREATE TABLE IF NOT EXISTS ab_test_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    test_name VARCHAR(100) NOT NULL,
    variant VARCHAR(50) NOT NULL,
    user_id INT,
    session_id VARCHAR(100),
    event_type ENUM('impression', 'conversion') NOT NULL,
    value DECIMAL(10,2),
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_test_name (test_name),
    INDEX idx_variant (variant),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Real-time analytics summary table (for caching)
CREATE TABLE IF NOT EXISTS realtime_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value BIGINT NOT NULL,
    time_bucket TIMESTAMP NOT NULL,
    additional_data JSON,
    
    UNIQUE KEY unique_metric_time (metric_name, time_bucket),
    INDEX idx_metric_name (metric_name),
    INDEX idx_time_bucket (time_bucket)
);

-- Create partitions for large tables (optional, for high-volume sites)
-- This would be done manually based on data volume requirements

-- Create triggers to update session data
DELIMITER //

CREATE TRIGGER update_session_on_page_view
AFTER INSERT ON page_views
FOR EACH ROW
BEGIN
    INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, landing_page, page_views_count, started_at)
    VALUES (NEW.session_id, NEW.user_id, NEW.ip_address, NEW.user_agent, NEW.url, 1, NEW.created_at)
    ON DUPLICATE KEY UPDATE
        page_views_count = page_views_count + 1,
        exit_page = NEW.url,
        ended_at = NEW.created_at,
        session_duration = TIMESTAMPDIFF(SECOND, started_at, NEW.created_at);
END//

DELIMITER ;

-- Insert some sample data for testing
INSERT INTO analytics_events (action, category, label, session_id, url, created_at) VALUES
('page_view', 'navigation', 'homepage', 'session_123', '/', NOW() - INTERVAL 1 HOUR),
('page_view', 'navigation', 'article', 'session_123', '/article/1', NOW() - INTERVAL 50 MINUTE),
('clap', 'engagement', 'article_1', 'session_123', '/article/1', NOW() - INTERVAL 45 MINUTE),
('comment', 'engagement', 'article_1', 'session_123', '/article/1', NOW() - INTERVAL 40 MINUTE);

-- Create indexes for better performance
CREATE INDEX idx_analytics_events_composite ON analytics_events(category, action, created_at);
CREATE INDEX idx_page_views_composite ON page_views(url, created_at);
CREATE INDEX idx_performance_metrics_composite ON performance_metrics(metric_name, created_at);