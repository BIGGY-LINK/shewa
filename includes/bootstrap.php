<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db_server(): PDO
{
    return new PDO(
        'mysql:host=' . SHEWA_DB_HOST . ';port=' . SHEWA_DB_PORT . ';charset=utf8mb4',
        SHEWA_DB_USER,
        SHEWA_DB_PASSWORD,
        db_options()
    );
}

function db_options(): array
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (SHEWA_DB_SSL_CA !== '') {
        $certificatePath = SHEWA_DB_SSL_CA;
        if (str_starts_with($certificatePath, '-----BEGIN CERTIFICATE-----')) {
            $certificatePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'shewa-mysql-ca-' . hash('sha256', $certificatePath) . '.pem';
            if (!is_file($certificatePath) && file_put_contents($certificatePath, SHEWA_DB_SSL_CA, LOCK_EX) === false) {
                throw new RuntimeException('Could not write the configured MySQL CA certificate to temporary storage.');
            }
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $certificatePath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    return $options;
}

function db(): PDO
{
    static $connection;

    if (!$connection instanceof PDO) {
        $connection = new PDO(
            'mysql:host=' . SHEWA_DB_HOST . ';port=' . SHEWA_DB_PORT . ';dbname=' . SHEWA_DB_NAME . ';charset=utf8mb4',
            SHEWA_DB_USER,
            SHEWA_DB_PASSWORD,
            db_options()
        );
    }

    return $connection;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ewa_luxe_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (getenv('VERCEL') === '1') {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS app_sessions (
                session_id VARCHAR(128) PRIMARY KEY,
                session_data MEDIUMBLOB NOT NULL,
                expires_at INT UNSIGNED NOT NULL,
                INDEX idx_app_sessions_expires (expires_at)
            ) ENGINE=InnoDB'
        );

        session_set_save_handler(
            static function (string $path, string $name): bool {
                return true;
            },
            static function (): bool {
                return true;
            },
            static function (string $id): string|false {
                $query = db()->prepare('SELECT session_data FROM app_sessions WHERE session_id = ? AND expires_at > ?');
                $query->execute([$id, time()]);
                $data = $query->fetchColumn();

                return $data === false ? '' : (string) $data;
            },
            static function (string $id, string $data): bool {
                $expiresAt = time() + (int) ini_get('session.gc_maxlifetime');
                $query = db()->prepare(
                    'INSERT INTO app_sessions (session_id, session_data, expires_at) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), expires_at = VALUES(expires_at)'
                );

                return $query->execute([$id, $data, $expiresAt]);
            },
            static function (string $id): bool {
                $query = db()->prepare('DELETE FROM app_sessions WHERE session_id = ?');

                return $query->execute([$id]);
            },
            static function (int $maxLifetime): int|false {
                $query = db()->prepare('DELETE FROM app_sessions WHERE expires_at < ?');
                $query->execute([time()]);

                return $query->rowCount();
            }
        );
    }

    session_start();
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
