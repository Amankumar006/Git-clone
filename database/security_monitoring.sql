-- Security Monitoring Tables

-- Security events log
CREATE TABLE security_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('failed_login', 'successful_login', 'unauthorized_access', 'data_export', 'bulk_download', 'admin_escalation', 'suspicious_activity', 'ip_blocked', 'account_locked', 'password_reset_abuse') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    description TEXT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_security_events_type (event_type),
    INDEX idx_security_events_severity (severity),
    INDEX idx_security_events_user (user_id),
    INDEX idx_security_events_ip (ip_address),
    INDEX idx_security_events_created (created_at DESC)
);

-- IP address blocks
CREATE TABLE ip_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    reason TEXT NOT NULL,
    blocked_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (blocked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_ip (ip_address),
    INDEX idx_ip_blocks_active (is_active, expires_at),
    INDEX idx_ip_blocks_created (created_at DESC)
);

-- System health metrics
CREATE TABLE system_health_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL,
    metric_unit VARCHAR(20) NULL,
    status ENUM('normal', 'warning', 'critical') DEFAULT 'normal',
    metadata JSON NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_health_metrics_name (metric_name),
    INDEX idx_health_metrics_recorded (recorded_at DESC),
    INDEX idx_health_metrics_status (status)
);

-- Audit log for admin actions
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    target_type ENUM('user', 'article', 'comment', 'publication', 'system') NOT NULL,
    target_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audit_log_admin (admin_id),
    INDEX idx_audit_log_action (action_type),
    INDEX idx_audit_log_target (target_type, target_id),
    INDEX idx_audit_log_created (created_at DESC)
);

-- System alerts
CREATE TABLE system_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('security', 'performance', 'error', 'maintenance') NOT NULL,
    severity ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    metadata JSON NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_alerts_type (alert_type),
    INDEX idx_alerts_severity (severity),
    INDEX idx_alerts_resolved (is_resolved),
    INDEX idx_alerts_created (created_at DESC)
);

-- Backup status tracking
CREATE TABLE backup_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('database', 'files', 'full') NOT NULL,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    file_path VARCHAR(500) NULL,
    file_size BIGINT NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_backup_status_type (backup_type),
    INDEX idx_backup_status_status (status),
    INDEX idx_backup_status_started (started_at DESC)
);

-- Add security-related columns to existing tables
ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_failed_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL;

-- Indexes for security monitoring
CREATE INDEX IF NOT EXISTS idx_users_failed_logins ON users(failed_login_attempts, last_failed_login);
CREATE INDEX IF NOT EXISTS idx_users_locked ON users(account_locked_until);
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login_at DESC);