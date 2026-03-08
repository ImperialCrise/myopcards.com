-- Add custom_avatar column for user-uploaded profile photos
-- When set, overrides the OAuth avatar (Discord/Google)
ALTER TABLE users ADD COLUMN custom_avatar VARCHAR(255) NULL AFTER avatar;
