ALTER TABLE games
    ADD COLUMN duration_seconds INT UNSIGNED NULL AFTER turn_count,
    ADD COLUMN player1_time_remaining INT UNSIGNED NULL AFTER duration_seconds,
    ADD COLUMN player2_time_remaining INT UNSIGNED NULL AFTER player1_time_remaining;

CREATE INDEX idx_game_moves_game_at ON game_moves(game_id, created_at);
