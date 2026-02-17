<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

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

$router->get('/collection', [App\Controllers\CollectionController::class, 'index']);
$router->post('/collection/add', [App\Controllers\CollectionController::class, 'add']);
$router->post('/collection/remove', [App\Controllers\CollectionController::class, 'remove']);
$router->post('/collection/update', [App\Controllers\CollectionController::class, 'update']);
$router->get('/collection/export', [App\Controllers\CollectionController::class, 'export']);
$router->post('/collection/share', [App\Controllers\CollectionController::class, 'generateShare']);
$router->post('/collection/share/revoke', [App\Controllers\CollectionController::class, 'revokeShare']);
$router->get('/s/{token}', [App\Controllers\CollectionController::class, 'sharedView']);
$router->get('/collection/{username}', [App\Controllers\CollectionController::class, 'publicView']);

$router->get('/friends', [App\Controllers\FriendController::class, 'index']);
$router->post('/friends/request', [App\Controllers\FriendController::class, 'sendRequest']);
$router->post('/friends/accept', [App\Controllers\FriendController::class, 'accept']);
$router->post('/friends/decline', [App\Controllers\FriendController::class, 'decline']);
$router->post('/friends/remove', [App\Controllers\FriendController::class, 'remove']);
$router->get('/api/users/search', [App\Controllers\FriendController::class, 'searchUsers']);
$router->get('/api/notifications/pending', [App\Controllers\FriendController::class, 'pendingJson']);

$router->get('/profile', [App\Controllers\UserController::class, 'profile']);
$router->post('/profile/update', [App\Controllers\UserController::class, 'updateProfile']);
$router->get('/user/{username}', [App\Controllers\UserController::class, 'publicProfile']);
$router->get('/settings', [App\Controllers\UserController::class, 'settings']);
$router->post('/settings/password', [App\Controllers\UserController::class, 'changePassword']);
$router->post('/settings/language', [App\Controllers\UserController::class, 'changeLanguage']);
$router->post('/settings/currency', [App\Controllers\UserController::class, 'changeCurrency']);

$router->get('/analytics', [App\Controllers\AnalyticsController::class, 'index']);
$router->get('/api/analytics/value-history', [App\Controllers\AnalyticsController::class, 'valueHistory']);
$router->get('/api/analytics/distribution', [App\Controllers\AnalyticsController::class, 'distribution']);

$router->get('/market', [App\Controllers\MarketController::class, 'index']);
$router->get('/api/market/movers', [App\Controllers\MarketController::class, 'movers']);

$router->get('/api/search', [App\Controllers\SearchController::class, 'search']);
$router->get('/api/cards/price-history/{id}', [App\Controllers\CardController::class, 'priceHistory']);

$router->get('/admin', [App\Controllers\AdminController::class, 'dashboard']);
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

$router->get('/notifications', [App\Controllers\NotificationController::class, 'index']);
$router->post('/notifications/read', [App\Controllers\NotificationController::class, 'markAsRead']);
$router->post('/notifications/read-all', [App\Controllers\NotificationController::class, 'markAllAsRead']);
$router->get('/api/notifications/count', [App\Controllers\NotificationController::class, 'getUnreadCount']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
