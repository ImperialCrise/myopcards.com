-- Update user_sessions table to support both remember tokens and regular sessions
ALTER TABLE user_sessions ADD COLUMN session_id VARCHAR(128) NULL AFTER user_id;

-- Make token_hash nullable since regular sessions won't have tokens
ALTER TABLE user_sessions MODIFY token_hash VARCHAR(64) NULL;

-- Remove unique constraint on token_hash since it can be NULL
ALTER TABLE user_sessions DROP INDEX token_hash;

-- Add unique index on session_id (for regular sessions)
ALTER TABLE user_sessions ADD UNIQUE INDEX idx_session_id (session_id);

-- Add composite index for efficient lookups
ALTER TABLE user_sessions ADD INDEX idx_user_activity (user_id, last_activity);