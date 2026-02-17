-- Notifications system
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('forum_reply', 'forum_like', 'forum_mention') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_unread (user_id, is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings for notifications
CREATE TABLE IF NOT EXISTS notification_settings (
    user_id INT UNSIGNED PRIMARY KEY,
    forum_replies BOOLEAN DEFAULT TRUE,
    forum_likes BOOLEAN DEFAULT TRUE,
    forum_mentions BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);