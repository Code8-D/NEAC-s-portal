<?php

require_once __DIR__ . '/Database.php';

class Security
{
    public const CSRF_FIELD = '_csrf_token';

    public static function sessionStart(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
                || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
            session_name('NEACSESSID');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.gc_maxlifetime', '3600');
            ini_set('session.cookie_httponly', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function csrfToken(): string
    {
        self::sessionStart();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="' . self::CSRF_FIELD . '" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::sessionStart();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return is_string($token)
            && $sessionToken !== ''
            && hash_equals($sessionToken, $token);
    }

    public static function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $token = $_POST[self::CSRF_FIELD] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!self::verifyCsrf($token)) {
            http_response_code(419);
            exit('Security token mismatch. Please go back and try again.');
        }
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; "
            . "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
            . "img-src 'self' data:; "
            . "connect-src 'self'; "
            . "object-src 'none'; base-uri 'self'; frame-ancestors 'self'"
        );
    }

    public static function recordLoginAttempt(string $identifier, bool $success): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (identifier, ip_address, success) VALUES (:id, :ip, :ok)'
        );
        $stmt->execute([
            'id' => substr($identifier, 0, 200),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ok' => $success ? 1 : 0,
        ]);
    }

    public static function loginThrottle(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = :id AND success = 0 AND created_at >= datetime("now", :window)'
        );
        $stmt->execute([
            'id' => substr($identifier, 0, 200),
            'window' => '-' . $windowSeconds . ' seconds',
        ]);
        return ((int) $stmt->fetchColumn()) >= $maxAttempts;
    }

    public static function isStrongPassword(string $password): bool
    {
        if (strlen($password) < 8) {
            return false;
        }
        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasDigit = preg_match('/\d/', $password) === 1;
        return $hasLower && $hasUpper && $hasDigit;
    }

    public static function detectMime(string $path): string
    {
        if (!is_file($path)) {
            return 'application/octet-stream';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($path);
    }

    public static function uploadIsAllowed(array $file, array $allowedExtensions, array $allowedMimePrefixes, int $maxSize): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return [false, 'Upload failed. Please try again.'];
        }
        if ($file['size'] <= 0 || $file['size'] > $maxSize) {
            return [false, 'The file is empty or exceeds the size limit.'];
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return [false, 'This file type is not allowed.'];
        }
        $tmp = $file['tmp_name'] ?? '';
        $mime = self::detectMime($tmp);
        $ok = false;
        foreach ($allowedMimePrefixes as $prefix) {
            if (str_starts_with($mime, $prefix)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            return [false, 'The file content does not match its extension.'];
        }
        return [true, ''];
    }

    public static function safeStorageName(string $originalName, string $extension): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
        $base = trim($base ?? '', '-') ?: 'upload';
        $base = substr($base, 0, 60);
        return $base . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    }

    public static function randomCode(int $length = 7): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }
}
