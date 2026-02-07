CREATE TABLE IF NOT EXISTS card_translations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    lang CHAR(2) NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    card_text TEXT NULL,
    sub_types VARCHAR(255) NULL,
    set_name VARCHAR(255) NULL,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE KEY uk_card_lang (card_id, lang),
    INDEX idx_lang (lang),
    INDEX idx_card_name (card_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
