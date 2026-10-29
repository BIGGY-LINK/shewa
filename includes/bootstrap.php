<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db_server(): PDO
{
    return new PDO(
        'mysql:host=' . SHEWA_DB_HOST . ';charset=utf8mb4',
        SHEWA_DB_USER,
        SHEWA_DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function db(): PDO
{
    static $connection;

    if (!$connection instanceof PDO) {
        $connection = new PDO(
            'mysql:host=' . SHEWA_DB_HOST . ';dbname=' . SHEWA_DB_NAME . ';charset=utf8mb4',
            SHEWA_DB_USER,
            SHEWA_DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    return $connection;
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (!is_string($provided) || !is_string($expected) || $expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('The form expired. Reload the page and try again.');
    }
}

function app_base_url(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $adminPosition = strpos($script, '/admin/');

    if ($adminPosition !== false) {
        return substr($script, 0, $adminPosition);
    }

    return rtrim(str_replace('\\', '/', dirname($script)), '/');
}

function app_url(string $path): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_admin(): void
{
    $user = current_user();

    if ($user === null || ($user['role'] ?? '') !== 'admin') {
        header('Location: ' . app_url('admin/login.php'));
        exit;
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}
