-- Add new notification types for publication workflow
-- Execute this script in phpMyAdmin to add new notification types

-- Add new notification types to the enum
ALTER TABLE notifications 
MODIFY COLUMN type ENUM(
    'follow', 
    'clap', 
    'comment', 
    'publication_invite',
    'article_submission',
    'article_approved',
    'article_rejected'
) NOT NULL;