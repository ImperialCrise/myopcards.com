<?php

declare(strict_types=1);

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class StorageService
{
    private static ?S3Client $client = null;
    private static ?string $bucket = null;

    public static function isConfigured(): bool
    {
        return !empty($_ENV['MINIO_ENDPOINT'] ?? getenv('MINIO_ENDPOINT'));
    }

    private static function getClient(): S3Client
    {
        if (self::$client === null) {
            $endpoint = $_ENV['MINIO_ENDPOINT'] ?? getenv('MINIO_ENDPOINT') ?: 'http://127.0.0.1:9000';
            $key = $_ENV['MINIO_ACCESS_KEY'] ?? getenv('MINIO_ACCESS_KEY') ?: 'minioadmin';
            $secret = $_ENV['MINIO_SECRET_KEY'] ?? getenv('MINIO_SECRET_KEY') ?: 'minioadmin';

            self::$client = new S3Client([
                'version' => 'latest',
                'region' => 'us-east-1',
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
            self::$bucket = $_ENV['MINIO_BUCKET'] ?? getenv('MINIO_BUCKET') ?: 'forum-uploads';
        }
        return self::$client;
    }

    private static function getBucket(): string
    {
        if (self::$bucket === null) {
            self::getClient();
        }
        return self::$bucket ?? 'forum-uploads';
    }

    public static function put(string $key, string $content, string $contentType = 'application/octet-stream'): bool
    {
        if (!self::isConfigured()) {
            return false;
        }
        try {
            self::getClient()->putObject([
                'Bucket' => self::getBucket(),
                'Key' => $key,
                'Body' => $content,
                'ContentType' => $contentType,
            ]);
            return true;
        } catch (AwsException $e) {
            error_log('StorageService put error: ' . $e->getMessage());
            return false;
        }
    }

    public static function get(string $key): ?string
    {
        if (!self::isConfigured()) {
            return null;
        }
        try {
            $result = self::getClient()->getObject([
                'Bucket' => self::getBucket(),
                'Key' => $key,
            ]);
            return (string) $result['Body'];
        } catch (AwsException $e) {
            return null;
        }
    }

    public static function getContentType(string $key): ?string
    {
        if (!self::isConfigured()) {
            return null;
        }
        try {
            $result = self::getClient()->headObject([
                'Bucket' => self::getBucket(),
                'Key' => $key,
            ]);
            return $result['ContentType'] ?? null;
        } catch (AwsException $e) {
            return null;
        }
    }
}
