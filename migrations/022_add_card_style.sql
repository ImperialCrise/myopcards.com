ALTER TABLE users
  ADD COLUMN card_style VARCHAR(30) NULL DEFAULT 'default' AFTER profile_accent_color;
