<?php
declare(strict_types=1);

$databasePassword = getenv('SHEWA_DB_PASSWORD');
$databaseHost = getenv('SHEWA_DB_HOST');
$databaseName = getenv('SHEWA_DB_NAME');
$databaseUser = getenv('SHEWA_DB_USER');
$databasePort = getenv('SHEWA_DB_PORT');

if (getenv('VERCEL') === '1') {
    $missingSettings = [];
    foreach ([
        'SHEWA_DB_HOST' => $databaseHost,
        'SHEWA_DB_NAME' => $databaseName,
        'SHEWA_DB_USER' => $databaseUser,
        'SHEWA_DB_PASSWORD' => $databasePassword,
    ] as $setting => $value) {
        if ($value === false || $value === '') {
            $missingSettings[] = $setting;
        }
    }

    if ($missingSettings !== []) {
        throw new RuntimeException(
            'Missing required Vercel environment variables: ' . implode(', ', $missingSettings)
        );
    }
}

define('SHEWA_DB_HOST', $databaseHost !== false && $databaseHost !== '' ? $databaseHost : '127.0.0.1');
define('SHEWA_DB_PORT', $databasePort !== false && $databasePort !== '' ? $databasePort : '3306');
define('SHEWA_DB_NAME', $databaseName !== false && $databaseName !== '' ? $databaseName : 'SHEWA');
define('SHEWA_DB_USER', $databaseUser !== false && $databaseUser !== '' ? $databaseUser : 'root');
define('SHEWA_DB_PASSWORD', $databasePassword === false ? '' : $databasePassword);
define('SHEWA_DB_SSL_CA', getenv('SHEWA_DB_SSL_CA') ?: '');
