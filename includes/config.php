<?php
declare(strict_types=1);

$missingSettings = [];
foreach (['SHEWA_DB_HOST', 'SHEWA_DB_NAME', 'SHEWA_DB_USER', 'SHEWA_DB_PASSWORD'] as $setting) {
    $value = getenv($setting);
    if ($value === false || $value === '') {
        $missingSettings[] = $setting;
    }
}

if ($missingSettings !== []) {
    throw new RuntimeException(
        'Missing required Vercel environment variables: ' . implode(', ', $missingSettings)
    );
}

define('SHEWA_DB_HOST', (string) getenv('SHEWA_DB_HOST'));
define('SHEWA_DB_PORT', getenv('SHEWA_DB_PORT') ?: '3306');
define('SHEWA_DB_NAME', (string) getenv('SHEWA_DB_NAME'));
define('SHEWA_DB_USER', (string) getenv('SHEWA_DB_USER'));
define('SHEWA_DB_PASSWORD', (string) getenv('SHEWA_DB_PASSWORD'));
define('SHEWA_DB_SSL_CA', getenv('SHEWA_DB_SSL_CA') ?: '');
