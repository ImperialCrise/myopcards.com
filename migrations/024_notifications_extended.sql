-- Extend notifications for friend requests, friend accepted, private messages
ALTER TABLE notifications MODIFY COLUMN type ENUM(
    'forum_reply',
    'forum_like',
    'forum_mention',
    'friend_request',
    'friend_accepted',
    'private_message'
) NOT NULL;

-- Add notification settings for new types
ALTER TABLE notification_settings ADD COLUMN friend_requests BOOLEAN DEFAULT TRUE;
ALTER TABLE notification_settings ADD COLUMN friend_accepted BOOLEAN DEFAULT TRUE;
ALTER TABLE notification_settings ADD COLUMN private_messages BOOLEAN DEFAULT TRUE;
