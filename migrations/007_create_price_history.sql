CREATE TABLE IF NOT EXISTS price_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    source ENUM('tcgplayer', 'cardmarket') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    recorded_at DATE NOT NULL,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE KEY uk_card_source_date (card_id, source, recorded_at),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
