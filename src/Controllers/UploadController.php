<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StorageService;

class UploadController
{
    private const UPLOAD_DIR = BASE_PATH . '/public/uploads/forum/';
    private const AVATAR_DIR = BASE_PATH . '/public/uploads/avatars/';
    private const BANNER_DIR = BASE_PATH . '/public/uploads/banners/';
    private const MARKETPLACE_DIR = BASE_PATH . '/public/uploads/marketplace/';

    public function serveForum(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }

        if (StorageService::isConfigured()) {
            $content = StorageService::get($path);
            if ($content !== null) {
                $contentType = StorageService::getContentType($path) ?? 'application/octet-stream';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: public, max-age=86400');
                echo $content;
                return;
            }
        }

        $baseDir = realpath(self::UPLOAD_DIR) ?: self::UPLOAD_DIR;
        $localPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $realPath = realpath($localPath);
        if ($realPath === false || !str_starts_with($realPath, $baseDir . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }
        if (is_file($realPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $realPath) ?: 'application/octet-stream';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($realPath);
            return;
        }

        http_response_code(404);
    }

    public function serveAvatars(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }
        $key = 'avatars/' . $path;

        if (StorageService::isConfigured()) {
            $content = StorageService::get($key);
            if ($content !== null) {
                $contentType = StorageService::getContentType($key) ?? 'application/octet-stream';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: public, max-age=86400');
                echo $content;
                return;
            }
        }

        $baseDir = realpath(self::AVATAR_DIR) ?: self::AVATAR_DIR;
        $localPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $realPath = realpath($localPath);
        if ($realPath === false || !str_starts_with($realPath, $baseDir . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }
        if (is_file($realPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $realPath) ?: 'application/octet-stream';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($realPath);
            return;
        }

        http_response_code(404);
    }

    public function serveCards(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }
        $key = 'cards/' . $path;

        if (StorageService::isConfigured()) {
            $content = StorageService::get($key);
            if ($content !== null) {
                $contentType = StorageService::getContentType($key) ?? 'image/jpeg';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: public, max-age=31536000');
                echo $content;
                return;
            }
        }

        http_response_code(404);
    }

    public function serveMessages(string $path): void
    {
        // Must be authenticated to view message images
        if (!\App\Core\Auth::check()) {
            http_response_code(403);
            return;
        }
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }
        $key = 'messages/' . $path;

        if (StorageService::isConfigured()) {
            $content = StorageService::get($key);
            if ($content !== null) {
                $contentType = StorageService::getContentType($key) ?? 'application/octet-stream';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: private, max-age=86400');
                echo $content;
                return;
            }
        }

        http_response_code(404);
    }

    public function serveMarketplace(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }
        $key = 'marketplace/' . $path;

        if (StorageService::isConfigured()) {
            $content = StorageService::get($key);
            if ($content !== null) {
                $contentType = StorageService::getContentType($key) ?? 'image/jpeg';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: public, max-age=86400');
                echo $content;
                return;
            }
        }

        $baseDir = realpath(self::MARKETPLACE_DIR) ?: self::MARKETPLACE_DIR;
        $localPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $realPath = realpath($localPath);
        if ($realPath === false || !str_starts_with($realPath, $baseDir . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }
        if (is_file($realPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $realPath) ?: 'image/jpeg';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($realPath);
            return;
        }

        http_response_code(404);
    }

    public function serveBanners(string $path): void
    {
        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            http_response_code(400);
            return;
        }
        $key = 'banners/' . $path;

        if (StorageService::isConfigured()) {
            $content = StorageService::get($key);
            if ($content !== null) {
                $contentType = StorageService::getContentType($key) ?? 'image/jpeg';
                header('Content-Type: ' . $contentType);
                header('Cache-Control: public, max-age=86400');
                echo $content;
                return;
            }
        }

        $baseDir = realpath(self::BANNER_DIR) ?: self::BANNER_DIR;
        $localPath = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $realPath = realpath($localPath);
        if ($realPath === false || !str_starts_with($realPath, $baseDir . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            return;
        }
        if (is_file($realPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $realPath) ?: 'image/jpeg';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($realPath);
            return;
        }

        http_response_code(404);
    }
}
