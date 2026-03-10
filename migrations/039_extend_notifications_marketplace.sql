ALTER TABLE notifications MODIFY COLUMN type ENUM(
    'forum_reply','forum_like','forum_mention',
    'friend_request','friend_accepted','private_message',
    'marketplace_bid_received','marketplace_bid_accepted',
    'marketplace_bid_rejected','marketplace_bid_expired',
    'marketplace_item_sold','marketplace_order_shipped',
    'marketplace_order_delivered','marketplace_order_completed',
    'marketplace_dispute_opened','marketplace_dispute_resolved',
    'marketplace_review_received','marketplace_funds_released',
    'marketplace_watchlist_alert'
) NOT NULL;

ALTER TABLE notification_settings
    ADD COLUMN marketplace_bids BOOLEAN DEFAULT TRUE,
    ADD COLUMN marketplace_orders BOOLEAN DEFAULT TRUE,
    ADD COLUMN marketplace_reviews BOOLEAN DEFAULT TRUE,
    ADD COLUMN marketplace_watchlist BOOLEAN DEFAULT TRUE;
