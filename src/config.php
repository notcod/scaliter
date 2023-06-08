<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(~0);

setlocale(LC_ALL, 'en_US.UTF-8');

if (!defined('IP')) define('IP'," ip()");

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

if (!defined('PRODUCTION')) define('PRODUCTION', !is_readable(SERVER.'/.dev'));

if (PRODUCTION) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
