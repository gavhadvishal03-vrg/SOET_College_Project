<?php
/**
 * Security Utilities - CSRF, XSS, Input Validation
 */
class Security
{
    public static function generateCSRFToken(): string
    {
        Session::start();
        if (!Session::has(CSRF_TOKEN_NAME)) {
            Session::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_NAME);
    }

    public static function validateCSRF(?string $token): bool
    {
        Session::start();
        $stored = Session::get(CSRF_TOKEN_NAME);
        if (!$stored || !$token) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . self::escape($token) . '">';
    }

    public static function escape(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function sanitize(string $input): string
    {
        return trim(strip_tags($input));
    }

    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

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

    public static function getClientIP(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return trim($ip);
    }

    public static function applySecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

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
