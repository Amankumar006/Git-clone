-- Database Setup Verification Script
-- Run this script after executing setup.sql to verify the database is properly configured

-- Check if all required tables exist
SELECT 'Checking table existence...' as status;
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'medium_clone'
ORDER BY TABLE_NAME;

-- Verify foreign key constraints
SELECT 'Checking foreign key constraints...' as status;
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'medium_clone' 
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- Check indexes for performance optimization
SELECT 'Checking performance indexes...' as status;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    INDEX_TYPE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'medium_clone'
AND INDEX_NAME != 'PRIMARY'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Verify full-text search indexes
SELECT 'Checking full-text search indexes...' as status;
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    INDEX_TYPE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'medium_clone'
AND INDEX_TYPE = 'FULLTEXT';

-- Check table constraints and data types
SELECT 'Checking table constraints...' as status;
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    EXTRA
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'medium_clone'
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- Test basic table operations (insert/select/delete)
SELECT 'Testing basic operations...' as status;

-- Test users table
INSERT INTO users (username, email, password_hash) 
VALUES ('test_user', 'test@example.com', 'hashed_password_here');

SELECT 'User created successfully' as test_result, id, username, email 
FROM users WHERE username = 'test_user';

-- Test publications table
INSERT INTO publications (name, description, owner_id) 
VALUES ('Test Publication', 'A test publication', LAST_INSERT_ID());

SELECT 'Publication created successfully' as test_result, id, name, owner_id 
FROM publications WHERE name = 'Test Publication';

-- Test articles table
INSERT INTO articles (author_id, title, content, status) 
VALUES (
    (SELECT id FROM users WHERE username = 'test_user'),
    'Test Article',
    '{"content": "This is a test article"}',
    'draft'
);

SELECT 'Article created successfully' as test_result, id, title, author_id, status 
FROM articles WHERE title = 'Test Article';

-- Test tags table
INSERT INTO tags (name, slug) VALUES ('test-tag', 'test-tag');

SELECT 'Tag created successfully' as test_result, id, name, slug 
FROM tags WHERE name = 'test-tag';

-- Test article_tags relationship
INSERT INTO article_tags (article_id, tag_id) 
VALUES (
    (SELECT id FROM articles WHERE title = 'Test Article'),
    (SELECT id FROM tags WHERE name = 'test-tag')
);

SELECT 'Article-tag relationship created successfully' as test_result, 
       a.title, t.name 
FROM articles a 
JOIN article_tags at ON a.id = at.article_id 
JOIN tags t ON at.tag_id = t.id 
WHERE a.title = 'Test Article';

-- Clean up test data
DELETE FROM article_tags WHERE tag_id = (SELECT id FROM tags WHERE name = 'test-tag');
DELETE FROM articles WHERE title = 'Test Article';
DELETE FROM tags WHERE name = 'test-tag';
DELETE FROM publications WHERE name = 'Test Publication';
DELETE FROM users WHERE username = 'test_user';

SELECT 'Test data cleaned up successfully' as cleanup_result;
SELECT 'Database setup verification completed!' as final_status;