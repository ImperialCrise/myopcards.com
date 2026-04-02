ALTER TABLE sets
  MODIFY COLUMN set_type ENUM('booster', 'starter', 'promo', 'don') NOT NULL DEFAULT 'booster';
