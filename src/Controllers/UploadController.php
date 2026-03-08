<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StorageService;

class UploadController
{
    private const UPLOAD_DIR = BASE_PATH . '/public/uploads/forum/';
    private const AVATAR_DIR = BASE_PATH . '/public/uploads/avatars/';

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

        $localPath = self::UPLOAD_DIR . $path;
        if (is_file($localPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $localPath) ?: 'application/octet-stream';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($localPath);
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

        $localPath = self::AVATAR_DIR . $path;
        if (is_file($localPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $localPath) ?: 'application/octet-stream';
            finfo_close($finfo);
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($localPath);
            return;
        }

        http_response_code(404);
    }
}
