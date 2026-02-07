<?php

declare(strict_types=1);

namespace App\Services;

class OAuthService
{
    public static function getGoogleAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public static function getGoogleUser(string $code): ?array
    {
        $tokenData = self::httpPost('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenData['access_token'])) {
            return null;
        }

        $userInfo = self::httpGet(
            'https://www.googleapis.com/oauth2/v2/userinfo',
            $tokenData['access_token']
        );

        if (!isset($userInfo['id'])) {
            return null;
        }

        return [
            'id' => $userInfo['id'],
            'email' => $userInfo['email'] ?? '',
            'name' => $userInfo['name'] ?? '',
            'avatar' => $userInfo['picture'] ?? null,
        ];
    }

    public static function getDiscordAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => $_ENV['DISCORD_CLIENT_ID'],
            'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'],
            'response_type' => 'code',
            'scope' => 'identify email',
        ]);

        return 'https://discord.com/api/oauth2/authorize?' . $params;
    }

    public static function getDiscordUser(string $code): ?array
    {
        $tokenData = self::httpPost('https://discord.com/api/oauth2/token', [
            'code' => $code,
            'client_id' => $_ENV['DISCORD_CLIENT_ID'],
            'client_secret' => $_ENV['DISCORD_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['DISCORD_REDIRECT_URI'],
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenData['access_token'])) {
            return null;
        }

        $userInfo = self::httpGet(
            'https://discord.com/api/v10/users/@me',
            $tokenData['access_token']
        );

        if (!isset($userInfo['id'])) {
            return null;
        }

        $avatar = null;
        if (!empty($userInfo['avatar'])) {
            $avatar = sprintf(
                'https://cdn.discordapp.com/avatars/%s/%s.png',
                $userInfo['id'],
                $userInfo['avatar']
            );
        }

        return [
            'id' => $userInfo['id'],
            'email' => $userInfo['email'] ?? '',
            'username' => $userInfo['username'] ?? '',
            'avatar' => $avatar,
        ];
    }

    private static function httpPost(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    private static function httpGet(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }
}
