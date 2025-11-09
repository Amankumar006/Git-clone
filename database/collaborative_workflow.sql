-- Collaborative Writing Workflow Database Schema
-- Execute this script in phpMyAdmin to add collaborative workflow tables

-- Article submissions table for tracking submission workflow
CREATE TABLE article_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    publication_id INT NOT NULL,
    submitted_by INT NOT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'revision_requested') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    revision_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_publication_status (publication_id, status),
    INDEX idx_submitted_by (submitted_by),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at DESC)
);

-- Article revisions table for tracking changes and collaborative editing
CREATE TABLE article_revisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    revision_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(500),
    content TEXT NOT NULL,
    featured_image_url VARCHAR(500),
    tags TEXT, -- JSON array of tags
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_summary TEXT,
    is_major_revision BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_article_revision (article_id, revision_number),
    INDEX idx_article_revision (article_id, revision_number DESC),
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at DESC)
);

-- Publication templates for standardized article formats
CREATE TABLE publication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    template_content TEXT NOT NULL, -- JSON structure for template
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_publication_active (publication_id, is_active),
    INDEX idx_default_template (publication_id, is_default)
);

-- Publication guidelines for writers
CREATE TABLE publication_guidelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('writing_style', 'content_policy', 'submission_process', 'formatting', 'general') DEFAULT 'general',
    is_required BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_publication_category (publication_id, category),
    INDEX idx_display_order (publication_id, display_order)
);

-- Article comments for collaborative review (extends existing comments for internal use)
CREATE TABLE article_review_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    submission_id INT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    comment_type ENUM('general', 'suggestion', 'required_change', 'approval', 'rejection') DEFAULT 'general',
    line_number INT NULL, -- For line-specific comments
    selection_start INT NULL, -- For text selection comments
    selection_end INT NULL,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_id) REFERENCES article_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_article_type (article_id, comment_type),
    INDEX idx_submission_resolved (submission_id, is_resolved),
    INDEX idx_created_at (created_at DESC)
);

-- Collaborative editing sessions for real-time editing
CREATE TABLE collaborative_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    created_by INT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    max_participants INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_article_active (article_id, is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_session_token (session_token)
);

-- Participants in collaborative editing sessions
CREATE TABLE collaborative_participants (
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (session_id, user_id),
    FOREIGN KEY (session_id) REFERENCES collaborative_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_last_activity (last_activity DESC)
);

-- Workflow notifications for submission process
CREATE TABLE workflow_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('submission_received', 'review_assigned', 'revision_requested', 'approved', 'rejected', 'published') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES article_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_submission_type (submission_id, notification_type),
    INDEX idx_created_at (created_at DESC)
);

-- Add new columns to existing publications table for workflow settings
ALTER TABLE publications 
ADD COLUMN workflow_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN auto_approval BOOLEAN DEFAULT FALSE,
ADD COLUMN require_review BOOLEAN DEFAULT TRUE,
ADD COLUMN submission_guidelines TEXT,
ADD COLUMN review_deadline_days INT DEFAULT 7;

-- Add new columns to existing articles table for workflow tracking
ALTER TABLE articles 
ADD COLUMN submission_id INT NULL,
ADD COLUMN original_author_id INT NULL,
ADD COLUMN last_edited_by INT NULL,
ADD COLUMN last_edited_at TIMESTAMP NULL,
ADD COLUMN revision_count INT DEFAULT 0,
ADD COLUMN is_collaborative BOOLEAN DEFAULT FALSE;

-- Add foreign keys for new article columns
ALTER TABLE articles 
ADD FOREIGN KEY (submission_id) REFERENCES article_submissions(id) ON DELETE SET NULL,
ADD FOREIGN KEY (original_author_id) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (last_edited_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add indexes for new article columns
ALTER TABLE articles 
ADD INDEX idx_submission_id (submission_id),
ADD INDEX idx_original_author (original_author_id),
ADD INDEX idx_last_edited (last_edited_by, last_edited_at),
ADD INDEX idx_collaborative (is_collaborative);

-- Update notification types to include workflow notifications
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('follow', 'clap', 'comment', 'publication_invite', 'submission_received', 'review_assigned', 'revision_requested', 'approved', 'rejected', 'published') NOT NULL;