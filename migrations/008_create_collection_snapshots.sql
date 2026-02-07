CREATE TABLE IF NOT EXISTS collection_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_value_usd DECIMAL(12,2) NOT NULL,
    total_value_eur DECIMAL(12,2) NULL,
    unique_cards INT UNSIGNED NOT NULL,
    total_cards INT UNSIGNED NOT NULL,
    snapshot_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_date (user_id, snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
