CREATE TABLE IF NOT EXISTS user_reports (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT UNSIGNED NOT NULL,
    reported_id INT UNSIGNED NOT NULL,
    reason ENUM('spam', 'harassment', 'inappropriate_content', 'cheating', 'other') NOT NULL,
    details TEXT,
    status ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    reviewed_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_reported (reported_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
