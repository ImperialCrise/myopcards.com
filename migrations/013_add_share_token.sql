ALTER TABLE users ADD COLUMN share_token VARCHAR(32) NULL UNIQUE AFTER preferred_currency;
