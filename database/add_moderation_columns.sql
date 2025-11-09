-- Add moderation columns to existing tables

-- Add role column to users table
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'moderator') DEFAULT 'user';
ALTER TABLE users ADD COLUMN is_suspended BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN suspension_expires_at TIMESTAMP NULL;

-- Add moderation columns to articles table
ALTER TABLE articles ADD COLUMN moderation_status ENUM('approved', 'pending', 'flagged', 'removed') DEFAULT 'approved';
ALTER TABLE articles ADD COLUMN flagged_at TIMESTAMP NULL;
ALTER TABLE articles ADD COLUMN moderated_by INT NULL;
ALTER TABLE articles ADD COLUMN is_featured BOOLEAN DEFAULT FALSE;
ALTER TABLE articles ADD COLUMN featured_at TIMESTAMP NULL;

-- Add moderation columns to comments table
ALTER TABLE comments ADD COLUMN moderation_status ENUM('approved', 'pending', 'flagged', 'removed') DEFAULT 'approved';
ALTER TABLE comments ADD COLUMN flagged_at TIMESTAMP NULL;
ALTER TABLE comments ADD COLUMN moderated_by INT NULL;