<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                Logger::security("Login attempt on inactive account", ['email' => $email]);
                return false;
            }

            self::login($user, $remember);
            User::updateLastLogin($user['id']);
            Logger::info("User logged in", ['user_id' => $user['id']]);
            return true;
        }

        Logger::security("Failed login attempt", ['email' => $email]);
        return false;
    }

    public static function login(array $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            User::setRememberToken($user['id'], $token);

            $expiry = time() + (86400 * 30);
            setcookie('remember_token', $user['id'] . ':' . $token, $expiry, "/", "", false, true);
        }
    }

    public static function logout(): void
    {
        $userId = Session::get('user_id');
        if ($userId) {
            User::clearRememberToken($userId);
        }

        Session::destroy();

        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, "/", "", false, true);
        }

        Logger::info("User logged out", ['user_id' => $userId]);
    }

    public static function check(): bool
    {
        if (Session::get('user_id')) {
            return true;
        }

        if (isset($_COOKIE['remember_token'])) {
            $parts = explode(':', $_COOKIE['remember_token']);
            if (count($parts) === 2) {
                [$userId, $token] = $parts;
                $user = User::findById((int) $userId);
                if ($user && hash_equals($user['remember_token'] ?? '', $token)) {
                    self::login($user, false);
                    return true;
                }
            }
        }

        return false;
    }

    public static function user(): ?array
    {
        $id = Session::get('user_id');
        return $id ? User::findById($id) : null;
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }
}