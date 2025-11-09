# Database Setup Instructions

## Setting up the Medium Clone Database in phpMyAdmin

### Prerequisites
- XAMPP, WAMP, or similar local development environment with MySQL and phpMyAdmin
- Access to phpMyAdmin (usually at http://localhost/phpmyadmin)

### Setup Steps

1. **Access phpMyAdmin**
   - Open your web browser and navigate to http://localhost/phpmyadmin
   - Login with your MySQL credentials (default: username 'root', no password for local development)

2. **Create Database**
   - Click on "Databases" tab
   - Enter database name: `medium_clone`
   - Select collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Execute SQL Script**
   - Select the `medium_clone` database from the left sidebar
   - Click on the "SQL" tab
   - Copy the entire contents of `setup.sql` file
   - Paste it into the SQL query box
   - Click "Go" to execute the script

4. **Verify Tables**
   After successful execution, you should see the following tables created:
   - users
   - publications
   - articles
   - tags
   - article_tags
   - comments
   - claps
   - bookmarks
   - follows
   - publication_members
   - notifications
   - password_resets

### Database Schema Overview

The database includes:
- **User management**: users, follows, password_resets
- **Content**: articles, tags, article_tags, publications
- **Engagement**: comments, claps, bookmarks
- **Organization**: publications, publication_members
- **System**: notifications

### Performance Optimizations

The schema includes comprehensive indexes for optimal performance:

**Primary Indexes:**
- Primary keys on all tables for unique identification
- Foreign key indexes for efficient relationship queries
- Unique constraints on usernames, emails, and other unique fields

**Composite Indexes:**
- `idx_author_status` on articles for author's content filtering
- `idx_articles_status_published` for published article queries
- `idx_articles_engagement` for trending content discovery
- `idx_user_activity` for notification management
- `idx_publication_articles` for publication content queries

**Search Optimization:**
- Full-text search index on articles (title, subtitle, content)
- Tag name indexing for fast tag-based searches
- User and email indexing for authentication queries

**Performance Monitoring:**
- View count and engagement metric indexes
- Time-based indexes for chronological queries
- Comment threading indexes for nested discussions

### Security Features

**Data Integrity:**
- Foreign key constraints maintain referential integrity
- Check constraints prevent invalid data (e.g., clap count limits)
- Proper data types and field lengths prevent overflow attacks
- Cascade delete rules maintain consistency

**Access Control:**
- Prepared statement support through PDO configuration
- UTF8MB4 character set for proper Unicode handling
- Timestamp tracking for audit trails
- Email verification system support

### Verification Steps

After running the setup script, verify the installation:

1. **Check Table Creation:**
   ```sql
   SHOW TABLES;
   ```
   Should return 12 tables: users, publications, articles, tags, article_tags, comments, claps, bookmarks, follows, publication_members, notifications, password_resets

2. **Verify Indexes:**
   ```sql
   SHOW INDEX FROM articles;
   SHOW INDEX FROM users;
   SHOW INDEX FROM comments;
   ```

3. **Test Constraints:**
   ```sql
   DESCRIBE articles;
   SHOW CREATE TABLE claps;
   ```

4. **Check Full-text Search:**
   ```sql
   SHOW INDEX FROM articles WHERE Key_name = 'idx_articles_search';
   ```

5. **Run Verification Script:**
   - In phpMyAdmin, select the `medium_clone` database
   - Click on the "SQL" tab
   - Copy and paste the contents of `verify_setup.sql`
   - Click "Go" to execute the verification tests
   - Review the output to ensure all tables, indexes, and constraints are properly configured

### Troubleshooting

**Common Issues:**

1. **MySQL Version Compatibility:**
   - Ensure MySQL 5.7+ or 8.0+ for JSON data type support
   - Check constraints require MySQL 8.0.16+ (will be ignored in older versions)

2. **Character Set Issues:**
   - Ensure database uses `utf8mb4_unicode_ci` collation
   - This supports full Unicode including emojis and special characters

3. **Index Creation Errors:**
   - Some conditional indexes may not be supported in older MySQL versions
   - Full-text indexes on JSON columns require MySQL 5.7.8+

4. **Foreign Key Constraint Errors:**
   - Ensure tables are created in the correct order (users before articles, etc.)
   - Check that referenced columns have the same data type and constraints