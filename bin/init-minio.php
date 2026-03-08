<?php

declare(strict_types=1);

/**
 * Initialize MinIO bucket for forum uploads.
 * Run: php bin/init-minio.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$endpoint = $_ENV['MINIO_ENDPOINT'] ?? getenv('MINIO_ENDPOINT');
if (empty($endpoint)) {
    echo "MINIO_ENDPOINT not set. Skipping MinIO init.\n";
    exit(0);
}

$key = $_ENV['MINIO_ACCESS_KEY'] ?? getenv('MINIO_ACCESS_KEY') ?: 'minioadmin';
$secret = $_ENV['MINIO_SECRET_KEY'] ?? getenv('MINIO_SECRET_KEY') ?: 'minioadmin';
$bucket = $_ENV['MINIO_BUCKET'] ?? getenv('MINIO_BUCKET') ?: 'forum-uploads';

try {
    $client = new Aws\S3\S3Client([
        'version' => 'latest',
        'region' => 'us-east-1',
        'endpoint' => $endpoint,
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => $key,
            'secret' => $secret,
        ],
    ]);

    if ($client->doesBucketExist($bucket)) {
        echo "Bucket '{$bucket}' already exists.\n";
        exit(0);
    }

    $client->createBucket(['Bucket' => $bucket]);
    echo "Bucket '{$bucket}' created successfully.\n";
} catch (Aws\Exception\AwsException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
