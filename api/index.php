<?php
declare(strict_types=1);

$requestPath = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
if ($requestPath !== '' && $requestPath[0] !== '/') {
    $requestPath = '/' . $requestPath;
}

$pages = [
    '/' => '/index.php',
    '/index.php' => '/index.php',
    '/setup.php' => '/setup.php',
    '/account.php' => '/account.php',
    '/checkout.php' => '/checkout.php',
    '/logout.php' => '/logout.php',
    '/admin/login.php' => '/admin/login.php',
    '/admin/index.php' => '/admin/index.php',
];

if (!isset($pages[$requestPath])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Not found.');
}

$script = $pages[$requestPath];
$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['PHP_SELF'] = $script;

try {
    require dirname(__DIR__) . $script;
} catch (PDOException | RuntimeException $exception) {
    error_log('SHEWA application bootstrap error: ' . $exception->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Store temporarily unavailable — Ewa_luxe Closet</title>'
        . '<style>body{margin:0;padding:10vh 8vw;background:#f5f0e8;color:#191714;font:16px/1.7 Arial,sans-serif}'
        . 'main{max-width:680px;margin:auto;padding:36px;background:#fbf9f5;border-top:3px solid #aa8959}'
        . 'h1{font:400 38px Georgia,serif}code{overflow-wrap:anywhere}</style><main>'
        . '<p>AWAITS A MOMENT · EWA_LUXE CLOSET</p><h1>The store is temporarily unavailable.</h1>'
        . '<p>The site could not connect to its hosted MySQL database. In Vercel, open '
        . '<strong>Project Settings → Environment Variables</strong> and check '
        . '<code>SHEWA_DB_HOST</code>, <code>SHEWA_DB_PORT</code>, <code>SHEWA_DB_NAME</code>, '
        . '<code>SHEWA_DB_USER</code>, and <code>SHEWA_DB_PASSWORD</code>.'
        . ' Use your hosted database connection details, not <code>127.0.0.1</code> or the local XAMPP <code>root</code> account.'
        . ' Then redeploy the project.</p></main></html>';
}
