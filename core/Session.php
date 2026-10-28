<?php
/**
 * Session Management Class
 *
 * Provides a secure static wrapper around PHP native sessions with hardened
 * cookie parameters (HttpOnly, SameSite=Strict, Strict Mode) and support
 * for one-time flash notification messages across HTTP redirects.
 */
class Session
{
    /**
     * Initializes the session with hardened security settings if not already started.
     * Enforces HttpOnly, SameSite=Strict, and strict session ID handling.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isHttps = (
                (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
                (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
                (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on')
            );

            // If accessing over plain http localhost, do not force secure
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
                if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) === 'off') {
                    $isHttps = false;
                }
            }

            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_path', '/');

            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'lifetime' => 86400 * 30, // 30 days
                    'path' => '/',
                    'secure' => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                ini_set('session.cookie_secure', $isHttps ? '1' : '0');
            }

            session_start();
        }
    }

    /**
     * Stores a key-value pair in the active session.
     *
     * @param string $key Session variable key
     * @param mixed $value Value to store
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a stored session value by key, with an optional fallback.
     *
     * @param string $key Session variable key
     * @param mixed $default Fallback value if key is not found
     * @return mixed The stored value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a specific key exists in the active session.
     *
     * @param string $key Session variable key
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Removes a specific key from the active session.
     *
     * @param string $key Session variable key to unset
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Completely destroys the session and clears all session cookies.
     * Used upon logout or session invalidation.
     *
     * @return void
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Regenerates the session ID to prevent session fixation attacks.
     *
     * @return void
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Sets or retrieves a one-time flash message (e.g. success/error banner).
     * Once retrieved, the flash message is automatically removed from the session.
     *
     * @param string $key Flash message identifier
     * @param mixed|null $value If provided, sets the flash value; otherwise retrieves and clears it
     * @return mixed Stored flash value or null
     */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}
