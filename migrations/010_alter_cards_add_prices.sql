ALTER TABLE cards
    ADD COLUMN cardmarket_price DECIMAL(10,2) NULL AFTER inventory_price,
    ADD COLUMN cardmarket_url VARCHAR(500) NULL AFTER cardmarket_price,
    ADD COLUMN price_updated_at TIMESTAMP NULL AFTER cardmarket_url;
