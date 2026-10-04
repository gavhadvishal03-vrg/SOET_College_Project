<?php
/**
 * Security Utilities - CSRF, XSS, Input Validation, and Rate Limiting
 *
 * Provides central cryptographic token generation, HTML escaping,
 * password hashing via Bcrypt, MIME-type file verification, client IP detection,
 * HTTP security response headers, and session-based request rate limiting.
 */
class Security
{
    /**
     * Generates a cryptographically secure CSRF token and stores it in the session.
     *
     * @return string 64-character hex CSRF token
     */
    public static function generateCSRFToken(): string
    {
        Session::start();
        if (!Session::has(CSRF_TOKEN_NAME)) {
            Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_NAME);
    }

    /**
     * Validates a submitted CSRF token using constant-time string comparison.
     *
     * @param string|null $token The CSRF token received from form submission or request headers
     * @return bool True if the token matches the session token, false otherwise
     */
    public static function validateCSRF(?string $token): bool
    {
        Session::start();
        $stored = Session::get(CSRF_TOKEN_NAME);
        if (!$stored || !$token) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Generates a hidden HTML input field containing the current CSRF token.
     *
     * @return string HTML hidden input element
     */
    public static function csrfField(): string
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::escape($token) . '">';
    }

    /**
     * Escapes HTML characters in a string to prevent Cross-Site Scripting (XSS).
     *
     * @param string|null $string Unescaped string
     * @return string Safe HTML-escaped string
     */
    public static function escape(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitizes plain text input by trimming whitespace and stripping all HTML tags.
     *
     * @param string $input Raw text input
     * @return string Stripped clean text
     */
    public static function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Sanitizes an email address string by removing illegal characters.
     *
     * @param string $email Raw email string
     * @return string Sanitized email string
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validates an email address against standard RFC 822 format.
     *
     * @param string $email Email address to validate
     * @return bool True if valid email format, false otherwise
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Hashes a plaintext password using Bcrypt with a high cost factor (12).
     *
     * @param string $password Plaintext password
     * @return string Bcrypt password hash
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verifies a plaintext password against a stored Bcrypt hash.
     *
     * @param string $password Plaintext password candidate
     * @param string $hash Stored Bcrypt hash
     * @return bool True if passwords match, false otherwise
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Validates an uploaded file's error status, file size, and actual MIME type.
     * Uses PHP fileinfo extension to inspect real magic bytes rather than trusting file extension.
     *
     * @param array $file $_FILES array element for the uploaded file
     * @param array $allowedTypes Array of permitted MIME types (e.g., ['image/jpeg', 'image/png'])
     * @param int $maxSize Maximum allowed file size in bytes (defaults to MAX_UPLOAD_SIZE)
     * @return array List of validation error strings, empty if file is valid
     */
    public static function validateUpload(array $file, array $allowedTypes, int $maxSize = MAX_UPLOAD_SIZE): array
    {
        $errors = [];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed.';
            return $errors;
        }
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed (' . ($maxSize / 1024 / 1024) . 'MB).';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedTypes)) {
            $errors[] = 'Invalid file type.';
        }
        return $errors;
    }

    /**
     * Moves an uploaded temporary file to a target destination directory with a unique timestamped name.
     *
     * @param array $file $_FILES array element
     * @param string $directory Target subfolder inside uploads/
     * @param string $prefix Optional filename prefix
     * @return string|null Generated filename on success, null on failure
     */
    public static function uploadFile(array $file, string $directory, string $prefix = ''): ?string
    {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . uniqid() . '_' . time() . '.' . strtolower($ext);
        $dest = UPLOAD_PATH . $directory . '/' . $filename;
        if (!is_dir(UPLOAD_PATH . $directory)) {
            mkdir(UPLOAD_PATH . $directory, 0755, true);
        }
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return $filename;
        }
        return null;
    }

    /**
     * Accurately determines the client IP address, checking proxy forwarding headers if present.
     *
     * @return string Client IP address
     */
    public static function getClientIP(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return trim($ip);
    }

    /**
     * Emits essential HTTP security headers to protect against clickjacking, MIME sniffing,
     * and malicious script injections.
     *
     * @return void
     */
    public static function applySecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Enforces rate limiting per IP/action key over a sliding decay window.
     *
     * @param string $key Unique identifier for the rate-limited action (e.g. 'login_attempt')
     * @param int $maxRequests Maximum permitted requests within the decay window
     * @param int $decaySeconds Window duration in seconds (default: 60)
     * @return bool True if request is within limit, false if rate limit exceeded
     */
    public static function checkRateLimit(string $key, int $maxRequests = 60, int $decaySeconds = 60): bool
    {
        Session::start();
        $currentTime = time();
        $rateKey = 'rate_limit_' . md5($key . self::getClientIP());
        
        $data = Session::get($rateKey, ['count' => 0, 'first_request' => $currentTime]);
        
        if ($currentTime - $data['first_request'] > $decaySeconds) {
            $data = ['count' => 1, 'first_request' => $currentTime];
            Session::set($rateKey, $data);
            return true;
        }
        
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        $data['count']++;
        Session::set($rateKey, $data);
        return true;
    }
}
