<?php
declare(strict_types=1);

$databasePassword = getenv('SHEWA_DB_PASSWORD');

define('SHEWA_DB_HOST', getenv('SHEWA_DB_HOST') ?: '127.0.0.1');
define('SHEWA_DB_NAME', 'SHEWA');
define('SHEWA_DB_USER', getenv('SHEWA_DB_USER') ?: 'root');
define('SHEWA_DB_PASSWORD', $databasePassword === false ? '' : $databasePassword);
