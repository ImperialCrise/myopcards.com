-- Messages v2: rich messages, typing indicators, system user, welcome tracking
-- Note: ALTER TABLE without IF NOT EXISTS; duplicates are caught by the migrate script

ALTER TABLE messages ADD COLUMN type ENUM('text','image','gif') NOT NULL DEFAULT 'text';
ALTER TABLE messages ADD COLUMN media_url VARCHAR(512) NULL;
ALTER TABLE messages ADD COLUMN edited_at TIMESTAMP NULL;
ALTER TABLE messages ADD COLUMN is_deleted BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS message_typing (
    conversation_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN is_system BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE users ADD COLUMN welcome_sent BOOLEAN NOT NULL DEFAULT FALSE;

INSERT IGNORE INTO users (username, email, password_hash, is_system, is_admin, preferred_lang, preferred_currency)
    VALUES ('MyOPCards', 'system@myopcards.com', '', TRUE, FALSE, 'en', 'usd');
