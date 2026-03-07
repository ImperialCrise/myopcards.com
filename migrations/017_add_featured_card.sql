-- Add featured card functionality to users
ALTER TABLE users ADD COLUMN featured_card_id INT UNSIGNED NULL AFTER bio;

-- Add foreign key constraint to cards table
ALTER TABLE users ADD CONSTRAINT fk_users_featured_card 
    FOREIGN KEY (featured_card_id) REFERENCES cards(id) ON DELETE SET NULL;

-- Add index for better performance
ALTER TABLE users ADD INDEX idx_featured_card_id (featured_card_id);