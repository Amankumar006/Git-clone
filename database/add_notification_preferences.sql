-- Add notification preferences column to users table
ALTER TABLE users ADD COLUMN notification_preferences JSON DEFAULT NULL;

-- Update existing users with default notification preferences
UPDATE users 
SET notification_preferences = JSON_OBJECT(
    'email_notifications', JSON_OBJECT(
        'follows', true,
        'claps', true,
        'comments', true,
        'publication_invites', true,
        'weekly_digest', true
    ),
    'push_notifications', JSON_OBJECT(
        'follows', true,
        'claps', true,
        'comments', true,
        'publication_invites', true
    )
)
WHERE notification_preferences IS NULL;