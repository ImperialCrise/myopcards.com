ALTER TABLE cards
    ADD COLUMN price_en DECIMAL(10,2) NULL AFTER cardmarket_url,
    ADD COLUMN price_fr DECIMAL(10,2) NULL AFTER price_en,
    ADD COLUMN price_jp DECIMAL(10,2) NULL AFTER price_fr;

ALTER TABLE price_history
    ADD COLUMN edition CHAR(2) NOT NULL DEFAULT 'en' AFTER source;

ALTER TABLE price_history DROP FOREIGN KEY price_history_ibfk_1;
DROP INDEX uk_card_source_date ON price_history;
ALTER TABLE price_history ADD UNIQUE KEY uk_card_source_edition_date (card_id, source, edition, recorded_at);
ALTER TABLE price_history ADD CONSTRAINT fk_ph_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE;
