CREATE TABLE IF NOT EXISTS sets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    set_id VARCHAR(20) NOT NULL UNIQUE,
    set_name VARCHAR(255) NOT NULL,
    set_type ENUM('booster', 'starter', 'promo') NOT NULL DEFAULT 'booster',
    card_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_synced_at TIMESTAMP NULL,
    INDEX idx_set_id (set_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
