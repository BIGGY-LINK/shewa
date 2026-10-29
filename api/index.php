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

require dirname(__DIR__) . $script;
