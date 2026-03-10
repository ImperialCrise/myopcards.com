<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

class GifController
{
    private const BASE    = 'https://api.klipy.com/api/v1';
    private const PER_PAGE = 24;

    public function search(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $q    = trim($_GET['q']    ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        if ($q === '') {
            echo json_encode(['data' => ['data' => [], 'has_next' => false]]);
            return;
        }
        echo $this->proxy('gifs/search', [
            'q'        => $q,
            'page'     => $page,
            'per_page' => self::PER_PAGE,
        ]);
    }

    public function trending(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $page = max(1, (int)($_GET['page'] ?? 1));
        echo $this->proxy('gifs/trending', [
            'page'     => $page,
            'per_page' => self::PER_PAGE,
        ]);
    }

    private function proxy(string $path, array $params): string
    {
        $key = $_ENV['KLIPY_API_KEY'] ?? '';
        if (!$key) {
            return json_encode(['data' => ['data' => [], 'has_next' => false], 'error' => 'not configured']);
        }
        $params['customer_id'] = Auth::id();
        $url = self::BASE . '/' . $key . '/' . $path . '?' . http_build_query($params);

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 8,
                'header'        => "Content-Type: application/json\r\nUser-Agent: MyOPCards/1.0\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        $status = $http_response_header[0] ?? '';

        if ($body === false || !str_contains($status, '200')) {
            error_log('[GifController] upstream error: ' . $status . ' url=' . $url);
            return json_encode(['data' => ['data' => [], 'has_next' => false], 'error' => 'upstream error']);
        }
        return $body;
    }
}
