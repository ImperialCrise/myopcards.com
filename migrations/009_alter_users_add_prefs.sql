ALTER TABLE users
    ADD COLUMN preferred_lang CHAR(2) NOT NULL DEFAULT 'en' AFTER is_public,
    ADD COLUMN preferred_currency ENUM('usd','eur') NOT NULL DEFAULT 'usd' AFTER preferred_lang;
