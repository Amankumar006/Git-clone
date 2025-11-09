-- Add branding columns to publications table
-- Run this script in phpMyAdmin to add the new branding fields

ALTER TABLE publications 
ADD COLUMN website_url VARCHAR(500) NULL AFTER logo_url,
ADD COLUMN social_links JSON NULL AFTER website_url,
ADD COLUMN theme_color VARCHAR(7) DEFAULT '#3B82F6' AFTER social_links,
ADD COLUMN custom_css TEXT NULL AFTER theme_color;

-- Update existing publications to have default theme color
UPDATE publications SET theme_color = '#3B82F6' WHERE theme_color IS NULL;