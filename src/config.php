<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(~0);

setlocale(LC_ALL, 'en_US.UTF-8');

if (!defined('IP')) define('IP', ip());

if (!defined('STATIC_FILES')) define('STATIC_FILES', [
    'js'    => 'application/javascript',
    'pdf'   => 'application/pdf',
    'json'  => 'application/json',
    'zip'   => 'application/zip',
    'ttf'   => 'application/octet-stream',
    'woff'  => 'application/font-woff',
    'css'   => 'text/css',
    'scss'   => 'text/css',
    'xml'   => 'text/xml',
    'txt'   => 'text/plain',
    'ico'   => 'image/x-icon',
    'png'   => 'image/png',
    'jpeg'  => 'image/jpeg',
    'jpg'   => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    // eot, woff2, mp4
]);

if (!defined('MINIMIZE_FILES')) define('MINIMIZE_FILES', [
    'css', 'js'
]);

if (!defined('PRODUCTION')) define('PRODUCTION', !is_readable(SERVER . '/.dev'));
// define('ONEFILE', '1');

if (PRODUCTION) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

$env = SERVER . (PRODUCTION ? '/.env' : '/.dev');
if (!is_readable($env)) die("Can't open .env");
$lines = file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    list($name, $value) = explode('=', $line, 2);
    $name = trim($name);
    $value = trim($value);
    if (!defined($name)) define($name, $value);
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', COOKIE_LIFETIME);
    ini_set('session.name', COOKIE_NAME); #can't be same as domain
    ini_set('session.sid_length', COOKIE_LENGTH);
    session_start();
}
