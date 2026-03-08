ALTER TABLE users
  ADD COLUMN banner_image VARCHAR(255) NULL AFTER custom_avatar,
  ADD COLUMN banner_gradient VARCHAR(100) NULL DEFAULT 'default' AFTER banner_image,
  ADD COLUMN profile_accent_color VARCHAR(7) NULL DEFAULT '#d4a853' AFTER banner_gradient;
