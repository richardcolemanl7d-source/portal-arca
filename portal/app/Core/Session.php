<?php

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (!self::$started && session_status() === PHP_SESSION_NONE) {
            session_start();
            self::$started = true;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            
            session_destroy();
            self::$started = false;
        }
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        
        if ($value !== null) {
            $_SESSION['__flash'][$key] = $value;
            return null;
        }
        
        $flashValue = $_SESSION['__flash'][$key] ?? null;
        unset($_SESSION['__flash'][$key]);
        
        return $flashValue;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['__flash'][$key]);
    }

    public static function regenerateId(): bool
    {
        self::start();
        return session_regenerate_id(true);
    }

    public static function all(): array
    {
        self::start();
        return $_SESSION ?? [];
    }

    public static function keep(array $keys): void
    {
        self::start();
        foreach ($keys as $key) {
            if (isset($_SESSION['__flash'][$key])) {
                $_SESSION[$key] = $_SESSION['__flash'][$key];
                unset($_SESSION['__flash'][$key]);
            }
        }
    }
}
