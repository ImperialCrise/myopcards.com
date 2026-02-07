ALTER TABLE cards ADD FULLTEXT INDEX ft_card_name (card_name);
ALTER TABLE card_translations ADD FULLTEXT INDEX ft_trans_card_name (card_name);
