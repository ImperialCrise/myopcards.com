<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Services\OAuthService;

class AuthController
{
    public function loginForm(): void
    {
        Auth::requireGuest();
        View::render('pages/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        Auth::requireGuest();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            View::render('pages/login', ['title' => 'Login', 'error' => 'All fields are required.']);
            return;
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            View::render('pages/login', ['title' => 'Login', 'error' => 'Invalid email or password.']);
            return;
        }

        Auth::login($user['id'], $remember);
        
        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    }

    public function registerForm(): void
    {
        Auth::requireGuest();
        View::render('pages/register', ['title' => 'Register']);
    }

    public function register(): void
    {
        Auth::requireGuest();

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = 'All fields are required.';
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, hyphens and underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (User::findByEmail($email)) {
            $errors[] = 'This email is already registered.';
        }

        if (User::findByUsername($username)) {
            $errors[] = 'This username is already taken.';
        }

        if (!empty($errors)) {
            View::render('pages/register', [
                'title' => 'Register',
                'errors' => $errors,
                'old' => ['username' => $username, 'email' => $email],
            ]);
            return;
        }

        $userId = User::create([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
        ]);

        Auth::login($userId);
        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
        exit;
    }

    public function googleRedirect(): void
    {
        $url = OAuthService::getGoogleAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    public function googleCallback(): void
    {
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            header('Location: /login');
            exit;
        }

        $googleUser = OAuthService::getGoogleUser($code);
        if (!$googleUser) {
            header('Location: /login?error=oauth_failed');
            exit;
        }

        $user = User::findByProvider('google', $googleUser['id']);

        if (!$user) {
            $existing = User::findByEmail($googleUser['email']);
            if ($existing) {
                User::update($existing['id'], [
                    'provider' => 'google',
                    'provider_id' => $googleUser['id'],
                    'avatar' => $googleUser['avatar'] ?? $existing['avatar'],
                ]);
                $user = User::findById($existing['id']);
            } else {
                $username = preg_replace('/[^a-zA-Z0-9_-]/', '', explode('@', $googleUser['email'])[0]);
                $base = $username;
                $i = 1;
                while (User::findByUsername($username)) {
                    $username = $base . $i++;
                }

                $userId = User::create([
                    'username' => $username,
                    'email' => $googleUser['email'],
                    'avatar' => $googleUser['avatar'] ?? null,
                    'provider' => 'google',
                    'provider_id' => $googleUser['id'],
                ]);
                $user = User::findById($userId);
            }
        }

        Auth::login($user['id']);
        header('Location: /dashboard');
        exit;
    }

    public function discordRedirect(): void
    {
        $url = OAuthService::getDiscordAuthUrl();
        header('Location: ' . $url);
        exit;
    }

    public function discordCallback(): void
    {
        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            header('Location: /login');
            exit;
        }

        $discordUser = OAuthService::getDiscordUser($code);
        if (!$discordUser) {
            header('Location: /login?error=oauth_failed');
            exit;
        }

        $user = User::findByProvider('discord', $discordUser['id']);

        if (!$user) {
            $existing = User::findByEmail($discordUser['email']);
            if ($existing) {
                User::update($existing['id'], [
                    'provider' => 'discord',
                    'provider_id' => $discordUser['id'],
                    'avatar' => $discordUser['avatar'] ?? $existing['avatar'],
                ]);
                $user = User::findById($existing['id']);
            } else {
                $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $discordUser['username']);
                $base = $username;
                $i = 1;
                while (User::findByUsername($username)) {
                    $username = $base . $i++;
                }

                $userId = User::create([
                    'username' => $username,
                    'email' => $discordUser['email'],
                    'avatar' => $discordUser['avatar'] ?? null,
                    'provider' => 'discord',
                    'provider_id' => $discordUser['id'],
                ]);
                $user = User::findById($userId);
            }
        }

        Auth::login($user['id']);
        header('Location: /dashboard');
        exit;
    }
}
