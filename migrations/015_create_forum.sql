CREATE TABLE IF NOT EXISTS forum_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) DEFAULT '#3B82F6',
    sort_order INT UNSIGNED DEFAULT 0,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_topics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    is_announcement TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED DEFAULT 0,
    reply_count INT UNSIGNED DEFAULT 0,
    last_reply_at TIMESTAMP NULL,
    last_reply_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_reply_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_user (user_id),
    INDEX idx_pinned (is_pinned, created_at),
    INDEX idx_last_reply (last_reply_at),
    FULLTEXT idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    topic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content LONGTEXT NOT NULL,
    is_solution TINYINT(1) NOT NULL DEFAULT 0,
    edited_at TIMESTAMP NULL,
    edited_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_topic (topic_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    FULLTEXT idx_search (content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NULL,
    topic_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post (post_id),
    INDEX idx_topic (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_reactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NULL,
    topic_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NOT NULL,
    reaction ENUM('like', 'helpful', 'thanks') NOT NULL DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES forum_topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_reaction (post_id, user_id),
    UNIQUE KEY unique_topic_reaction (topic_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token (token_hash),
    INDEX idx_expires (expires_at),
    INDEX idx_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO forum_categories (name, slug, description, icon, color, sort_order) VALUES
('Official Announcements', 'announcements', 'Site news and updates', 'megaphone', '#EF4444', 1),
('General Discussion', 'general', 'General discussions about One Piece Card Game', 'chat-bubble-left-right', '#3B82F6', 2),
('Strategies & Decks', 'strategies', 'Share your decks, combos and game strategies', 'puzzle-piece', '#10B981', 3),
('Trading & Marketplace', 'trading', 'Post your trades and search for cards', 'arrows-right-left', '#F59E0B', 4),
('Collections & Showcases', 'collections', 'Show off your best collections and pulls', 'sparkles', '#8B5CF6', 5),
('Help & Support', 'help', 'Questions about the game or the site', 'question-mark-circle', '#6B7280', 6),
('Off-Topic', 'off-topic', 'Non One Piece discussions (follow the rules)', 'fire', '#EC4899', 7);
