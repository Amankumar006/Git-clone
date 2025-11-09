-- Add slug column to articles table
ALTER TABLE articles ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title;

-- Create index for better performance
CREATE INDEX idx_articles_slug ON articles(slug);

-- Update existing articles with slugs (if any exist)
-- This would need to be run after the column is added
UPDATE articles 
SET slug = CONCAT(
    LOWER(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(title, ' ', '-'),
                    '?', ''
                ),
                '!', ''
            ),
            '.', ''
        )
    ),
    '-',
    id
) 
WHERE slug IS NULL;