<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require BASE_PATH . '/src/helpers.php';

ini_set('session.gc_maxlifetime', (string)(86400 * 7));
session_set_cookie_params([
    'lifetime' => 86400 * 7,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$router = new App\Core\Router();

$router->get('/', [App\Controllers\HomeController::class, 'index']);

$router->get('/login', [App\Controllers\AuthController::class, 'loginForm']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->get('/register', [App\Controllers\AuthController::class, 'registerForm']);
$router->post('/register', [App\Controllers\AuthController::class, 'register']);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

$router->get('/auth/google', [App\Controllers\AuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [App\Controllers\AuthController::class, 'googleCallback']);
$router->get('/auth/discord', [App\Controllers\AuthController::class, 'discordRedirect']);
$router->get('/auth/discord/callback', [App\Controllers\AuthController::class, 'discordCallback']);

$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);

$router->get('/cards', [App\Controllers\CardController::class, 'index']);
$router->get('/cards/{id}', [App\Controllers\CardController::class, 'show']);
$router->get('/api/cards/search', [App\Controllers\CardController::class, 'search']);
$router->get('/api/cards/recommended', [App\Controllers\CardController::class, 'recommended']);
$router->get('/api/card-image', [App\Controllers\CardController::class, 'proxyImage']);

$router->get('/collection', [App\Controllers\CollectionController::class, 'index']);
$router->post('/collection/add', [App\Controllers\CollectionController::class, 'add']);
$router->post('/collection/remove', [App\Controllers\CollectionController::class, 'remove']);
$router->post('/collection/update', [App\Controllers\CollectionController::class, 'update']);
$router->get('/collection/export', [App\Controllers\CollectionController::class, 'export']);
$router->post('/collection/share', [App\Controllers\CollectionController::class, 'generateShare']);
$router->post('/collection/share/revoke', [App\Controllers\CollectionController::class, 'revokeShare']);
$router->get('/s/{token}', [App\Controllers\CollectionController::class, 'sharedView']);
$router->get('/collection/{username}', [App\Controllers\CollectionController::class, 'publicView']);

$router->get('/messages', [App\Controllers\MessageController::class, 'inbox']);
$router->get('/messages/new/{username}', [App\Controllers\MessageController::class, 'newConversation']);
$router->get('/messages/{id}', [App\Controllers\MessageController::class, 'conversation']);
$router->post('/api/messages/send', [App\Controllers\MessageController::class, 'sendMessage']);
$router->post('/api/messages/start', [App\Controllers\MessageController::class, 'startConversation']);
$router->get('/api/messages/{id}/poll', [App\Controllers\MessageController::class, 'poll']);
$router->post('/api/messages/{id}/typing', [App\Controllers\MessageController::class, 'setTyping']);
$router->post('/api/messages/edit', [App\Controllers\MessageController::class, 'editMessage']);
$router->post('/api/messages/delete', [App\Controllers\MessageController::class, 'deleteMessage']);
$router->post('/api/messages/upload', [App\Controllers\MessageController::class, 'uploadMedia']);

$router->get('/friends', [App\Controllers\FriendController::class, 'index']);
$router->post('/friends/request', [App\Controllers\FriendController::class, 'sendRequest']);
$router->post('/friends/accept', [App\Controllers\FriendController::class, 'accept']);
$router->post('/friends/decline', [App\Controllers\FriendController::class, 'decline']);
$router->post('/friends/remove', [App\Controllers\FriendController::class, 'remove']);
$router->post('/friends/block', [App\Controllers\FriendController::class, 'block']);
$router->post('/friends/unblock', [App\Controllers\FriendController::class, 'unblock']);
$router->post('/report/user', [App\Controllers\ReportController::class, 'submitUserReport']);
$router->get('/api/users/search', [App\Controllers\FriendController::class, 'searchUsers']);
$router->get('/api/notifications/pending', [App\Controllers\FriendController::class, 'pendingJson']);
$router->get('/api/notifications/recent', [App\Controllers\NotificationController::class, 'getRecent']);

$router->get('/profile', [App\Controllers\UserController::class, 'profile']);
$router->post('/profile/update', [App\Controllers\UserController::class, 'updateProfile']);
$router->get('/user/{username}', [App\Controllers\UserController::class, 'publicProfile']);
$router->get('/settings', [App\Controllers\UserController::class, 'settings']);
$router->post('/settings/password', [App\Controllers\UserController::class, 'changePassword']);
$router->post('/settings/avatar', [App\Controllers\UserController::class, 'updateAvatar']);
$router->post('/settings/avatar/remove', [App\Controllers\UserController::class, 'removeAvatar']);
$router->post('/profile/banner', [App\Controllers\UserController::class, 'updateBanner']);
$router->post('/profile/banner/remove', [App\Controllers\UserController::class, 'removeBanner']);
$router->post('/settings/language', [App\Controllers\UserController::class, 'changeLanguage']);
$router->post('/settings/currency', [App\Controllers\UserController::class, 'changeCurrency']);
$router->post('/settings/messages-privacy', [App\Controllers\UserController::class, 'changeMessagesPrivacy']);

$router->get('/analytics', [App\Controllers\AnalyticsController::class, 'index']);
$router->get('/api/analytics/value-history', [App\Controllers\AnalyticsController::class, 'valueHistory']);
$router->get('/api/analytics/distribution', [App\Controllers\AnalyticsController::class, 'distribution']);

$router->get('/market', [App\Controllers\MarketController::class, 'index']);
$router->get('/api/market/movers', [App\Controllers\MarketController::class, 'movers']);

$router->get('/decks', [App\Controllers\DeckController::class, 'index']);
$router->get('/decks/create', [App\Controllers\DeckController::class, 'create']);
$router->get('/decks/{id}/edit', [App\Controllers\DeckController::class, 'edit']);
$router->post('/api/decks/save', [App\Controllers\DeckController::class, 'save']);
$router->post('/api/decks/delete', [App\Controllers\DeckController::class, 'delete']);
$router->get('/api/decks/{id}', [App\Controllers\DeckController::class, 'get']);
$router->get('/api/decks/user/list', [App\Controllers\DeckController::class, 'userDecks']);

$router->get('/play', [App\Controllers\GameController::class, 'lobby']);
$router->get('/play/game/{id}', [App\Controllers\GameController::class, 'board']);
$router->get('/leaderboard', [App\Controllers\GameController::class, 'leaderboard']);
$router->get('/api/leaderboard', [App\Controllers\GameController::class, 'leaderboardApi']);
$router->get('/api/game/history/global', [App\Controllers\GameController::class, 'globalHistory']);
$router->get('/api/game/history', [App\Controllers\GameController::class, 'history']);
$router->get('/api/game/{id}/moves', [App\Controllers\GameController::class, 'gameMoves']);
$router->get('/history', [App\Controllers\GameController::class, 'historyPage']);
$router->get('/history/{id}', [App\Controllers\GameController::class, 'replayPage']);

$router->get('/api/gif/search',   [App\Controllers\GifController::class, 'search']);
$router->get('/api/gif/trending', [App\Controllers\GifController::class, 'trending']);

$router->get('/api/search', [App\Controllers\SearchController::class, 'search']);
$router->get('/api/cards/price-history/{id}', [App\Controllers\CardController::class, 'priceHistory']);
$router->post('/api/cards/{id}/set-featured', [App\Controllers\CardController::class, 'setFeatured']);

$router->get('/admin', [App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/reports', [App\Controllers\AdminController::class, 'reports']);
$router->post('/admin/reports/review', [App\Controllers\AdminController::class, 'reviewReport']);
$router->get('/admin/users', [App\Controllers\AdminController::class, 'users']);
$router->post('/admin/users/toggle-admin', [App\Controllers\AdminController::class, 'toggleAdmin']);
$router->post('/admin/users/delete', [App\Controllers\AdminController::class, 'deleteUser']);
$router->get('/admin/cards', [App\Controllers\AdminController::class, 'cards']);
$router->get('/admin/prices', [App\Controllers\AdminController::class, 'prices']);
$router->post('/admin/sync/cards', [App\Controllers\AdminController::class, 'syncCards']);
$router->post('/admin/sync/prices-tcg', [App\Controllers\AdminController::class, 'syncPricesTcg']);
$router->post('/admin/sync/prices-cardmarket', [App\Controllers\AdminController::class, 'syncPricesCardmarket']);
$router->post('/admin/sync/snapshot', [App\Controllers\AdminController::class, 'syncSnapshot']);
$router->get('/admin/logs', [App\Controllers\AdminController::class, 'logs']);
$router->get('/admin/card-edit', [App\Controllers\AdminController::class, 'editCard']);
$router->post('/admin/card-update', [App\Controllers\AdminController::class, 'updateCard']);
$router->post('/admin/prices/import', [App\Controllers\AdminController::class, 'importPrices']);
$router->get('/admin/forum-categories', [App\Controllers\AdminController::class, 'forumCategories']);
$router->get('/admin/forum-categories/create', [App\Controllers\AdminController::class, 'createForumCategory']);
$router->post('/admin/forum-categories/create', [App\Controllers\AdminController::class, 'createForumCategory']);
$router->get('/admin/forum-categories/{id}/edit', [App\Controllers\AdminController::class, 'editForumCategory']);
$router->post('/admin/forum-categories/{id}/edit', [App\Controllers\AdminController::class, 'editForumCategory']);
$router->post('/admin/forum-categories/{id}/delete', [App\Controllers\AdminController::class, 'deleteForumCategory']);

$router->get('/sitemap.xml', [App\Controllers\SeoController::class, 'sitemapIndex']);
$router->get('/sitemap-static.xml', [App\Controllers\SeoController::class, 'sitemapStatic']);
$router->get('/sitemap-cards-premium.xml', [App\Controllers\SeoController::class, 'sitemapCardsPremium']);
$router->get('/sitemap-cards.xml', [App\Controllers\SeoController::class, 'sitemapCards']);
$router->get('/sitemap-users.xml', [App\Controllers\SeoController::class, 'sitemapUsers']);
$router->get('/sitemap-forum.xml', [App\Controllers\SeoController::class, 'sitemapForum']);
$router->get('/robots.txt', [App\Controllers\SeoController::class, 'robots']);

$router->get('/forum', [App\Controllers\ForumController::class, 'index']);
$router->get('/forum/rules', [App\Controllers\ForumController::class, 'rules']);
$router->get('/forum/search', [App\Controllers\ForumController::class, 'search']);
$router->post('/forum/upload-image', [App\Controllers\ForumController::class, 'uploadImage']);
$router->post('/forum/react', [App\Controllers\ForumController::class, 'react']);
$router->get('/forum/{slug}', [App\Controllers\ForumController::class, 'category']);
$router->get('/forum/{slug}/new', [App\Controllers\ForumController::class, 'newTopicForm']);
$router->post('/forum/{slug}/create', [App\Controllers\ForumController::class, 'createTopic']);
$router->get('/forum/{slug}/{id}-{topicSlug}', [App\Controllers\ForumController::class, 'topic']);
$router->post('/forum/{slug}/{id}/reply', [App\Controllers\ForumController::class, 'reply']);
$router->get('/forum/post/{id}/edit', [App\Controllers\ForumController::class, 'editPost']);
$router->post('/forum/post/{id}/edit', [App\Controllers\ForumController::class, 'editPost']);
$router->post('/forum/post/{id}/delete', [App\Controllers\ForumController::class, 'deletePost']);
$router->get('/forum/topic/{id}/edit', [App\Controllers\ForumController::class, 'editTopic']);
$router->post('/forum/topic/{id}/edit', [App\Controllers\ForumController::class, 'editTopic']);
$router->post('/forum/topic/{id}/delete', [App\Controllers\ForumController::class, 'deleteTopic']);

$router->get('/notifications', [App\Controllers\NotificationController::class, 'index']);
$router->post('/notifications/read', [App\Controllers\NotificationController::class, 'markAsRead']);
$router->post('/notifications/read-all', [App\Controllers\NotificationController::class, 'markAllAsRead']);
$router->get('/api/notifications/count', [App\Controllers\NotificationController::class, 'getUnreadCount']);

// Marketplace
$router->get('/marketplace', [App\Controllers\MarketplaceController::class, 'index']);
$router->get('/marketplace/card/{id}', [App\Controllers\MarketplaceController::class, 'cardListings']);
$router->get('/marketplace/listing/{id}', [App\Controllers\MarketplaceController::class, 'showListing']);
$router->get('/marketplace/sell', [App\Controllers\MarketplaceController::class, 'createListingForm']);
$router->get('/marketplace/my-listings', [App\Controllers\MarketplaceController::class, 'myListings']);
$router->get('/marketplace/my-bids', [App\Controllers\MarketplaceController::class, 'myBids']);
$router->get('/api/marketplace/search', [App\Controllers\MarketplaceController::class, 'search']);
$router->get('/api/marketplace/floor-prices', [App\Controllers\MarketplaceController::class, 'floorPrices']);
$router->get('/api/marketplace/recent-sales', [App\Controllers\MarketplaceController::class, 'recentSales']);
$router->get('/api/marketplace/popular', [App\Controllers\MarketplaceController::class, 'popularCards']);
$router->post('/api/marketplace/listings', [App\Controllers\MarketplaceController::class, 'createListing']);
$router->post('/api/marketplace/listings/{id}/cancel', [App\Controllers\MarketplaceController::class, 'cancelListing']);
$router->post('/api/marketplace/listings/{id}/edit', [App\Controllers\MarketplaceController::class, 'editListing']);
$router->post('/api/marketplace/bids', [App\Controllers\MarketplaceController::class, 'placeBid']);
$router->post('/api/marketplace/bids/{id}/accept', [App\Controllers\MarketplaceController::class, 'acceptBid']);
$router->post('/api/marketplace/bids/{id}/reject', [App\Controllers\MarketplaceController::class, 'rejectBid']);
$router->post('/api/marketplace/bids/{id}/cancel', [App\Controllers\MarketplaceController::class, 'cancelBid']);
$router->post('/api/marketplace/buy', [App\Controllers\MarketplaceController::class, 'buyNow']);
$router->post('/api/marketplace/upload', [App\Controllers\MarketplaceController::class, 'uploadImage']);

// Wallet
$router->get('/wallet', [App\Controllers\WalletController::class, 'index']);
$router->get('/api/wallet/balance', [App\Controllers\WalletController::class, 'balance']);
$router->get('/api/wallet/transactions', [App\Controllers\WalletController::class, 'transactions']);
$router->post('/api/wallet/deposit', [App\Controllers\WalletController::class, 'createDeposit']);
$router->post('/api/wallet/deposit/confirm', [App\Controllers\WalletController::class, 'confirmDeposit']);
$router->post('/api/wallet/withdraw', [App\Controllers\WalletController::class, 'requestWithdrawal']);

// Orders
$router->get('/orders', [App\Controllers\OrderController::class, 'index']);
$router->get('/orders/{id}', [App\Controllers\OrderController::class, 'show']);
$router->post('/api/orders/{id}/ship', [App\Controllers\OrderController::class, 'markShipped']);
$router->post('/api/orders/{id}/confirm-delivery', [App\Controllers\OrderController::class, 'confirmDelivery']);
$router->post('/api/orders/{id}/dispute', [App\Controllers\OrderController::class, 'openDispute']);
$router->post('/api/orders/{id}/review', [App\Controllers\OrderController::class, 'submitReview']);

// Stripe webhook
$router->post('/webhook/stripe', [App\Controllers\WalletController::class, 'stripeWebhook']);

// Admin marketplace
$router->get('/admin/marketplace', [App\Controllers\AdminController::class, 'marketplaceOverview']);
$router->get('/admin/marketplace/disputes', [App\Controllers\AdminController::class, 'marketplaceDisputes']);
$router->post('/admin/marketplace/disputes/{id}/resolve', [App\Controllers\AdminController::class, 'resolveDispute']);

// Uploads proxy (MinIO or local) - must match before dispatch for path with slashes
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $uriPath !== null && $uriPath !== false) {
    if (str_starts_with($uriPath, '/uploads/messages/')) {
        $path = substr($uriPath, strlen('/uploads/messages/'));
        (new App\Controllers\UploadController())->serveMessages($path);
        exit;
    }
    if (str_starts_with($uriPath, '/uploads/forum/')) {
        $path = substr($uriPath, strlen('/uploads/forum/'));
        (new App\Controllers\UploadController())->serveForum($path);
        exit;
    }
    if (str_starts_with($uriPath, '/uploads/avatars/')) {
        $path = substr($uriPath, strlen('/uploads/avatars/'));
        (new App\Controllers\UploadController())->serveAvatars($path);
        exit;
    }
    if (str_starts_with($uriPath, '/uploads/cards/')) {
        $path = substr($uriPath, strlen('/uploads/cards/'));
        (new App\Controllers\UploadController())->serveCards($path);
        exit;
    }
    if (str_starts_with($uriPath, '/uploads/marketplace/')) {
        $path = substr($uriPath, strlen('/uploads/marketplace/'));
        (new App\Controllers\UploadController())->serveMarketplace($path);
        exit;
    }
    if (str_starts_with($uriPath, '/uploads/banners/')) {
        $path = substr($uriPath, strlen('/uploads/banners/'));
        (new App\Controllers\UploadController())->serveBanners($path);
        exit;
    }
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
