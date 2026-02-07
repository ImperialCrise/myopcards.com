CREATE TABLE IF NOT EXISTS user_cards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    `condition` ENUM('NM', 'LP', 'MP', 'HP', 'DMG') NOT NULL DEFAULT 'NM',
    is_wishlist TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_card_condition_wish (user_id, card_id, `condition`, is_wishlist),
    INDEX idx_user_id (user_id),
    INDEX idx_card_id (card_id),
    INDEX idx_wishlist (user_id, is_wishlist)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
