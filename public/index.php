<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

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

$router->get('/sitemap.xml', [App\Controllers\SeoController::class, 'sitemap']);
$router->get('/robots.txt', [App\Controllers\SeoController::class, 'robots']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
