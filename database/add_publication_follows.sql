-- Add publication follows table for publication subscription feature
-- Execute this script in phpMyAdmin to add the publication_follows table

CREATE TABLE publication_follows (
    publication_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (publication_id, user_id),
    FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_publication_follows_user (user_id, created_at DESC),
    INDEX idx_publication_follows_pub (publication_id, created_at DESC)
);