CREATE TABLE page_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    page_type ENUM('profile','collection','shared') NOT NULL,
    viewer_ip VARCHAR(45) NULL,
    viewer_user_id INT UNSIGNED NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_page (user_id, page_type),
    INDEX idx_viewed_at (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users
    ADD COLUMN profile_views INT UNSIGNED NOT NULL DEFAULT 0 AFTER share_token,
    ADD COLUMN collection_views INT UNSIGNED NOT NULL DEFAULT 0 AFTER profile_views;
