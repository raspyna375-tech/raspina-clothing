<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_DIR', __DIR__);

foreach (array(APP_ROOT . '/storage/logs', APP_ROOT . '/storage/rate-limit') as $runtimeDirectory) {
    if (!is_dir($runtimeDirectory)) {
        @mkdir($runtimeDirectory, 0750, true);
    }
}

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');
date_default_timezone_set('Asia/Tehran');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('raspina_session');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

require APP_DIR . '/helpers.php';
require APP_DIR . '/security.php';
require APP_DIR . '/CatalogRepository.php';

set_exception_handler(function (Throwable $exception): void {
    error_log('Unhandled application error: ' . get_class($exception) . ' | message=' . safe_error_summary($exception) . ' | file=' . $exception->getFile() . ' | line=' . $exception->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Raspina Clothing</title><style>body{margin:0;background:#fffaf0;color:#15120d;font:18px/1.6 Georgia,serif;display:grid;min-height:100vh;place-items:center}.box{max-width:620px;padding:3rem;border-top:1px solid #d8b65a;border-bottom:1px solid #d8b65a}a{color:inherit}</style><div class="box"><p>Raspina Clothing</p><h1>We could not complete that request.</h1><p>Please return to the <a href="/">homepage</a> or contact us directly.</p></div></html>';
});

if (!headers_sent() && PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

$defaultConfig = require APP_DIR . '/config.php';
$config = is_array($defaultConfig) ? $defaultConfig : array();
if (is_file(APP_DIR . '/config.local.php')) {
    $localConfig = require APP_DIR . '/config.local.php';
    if (is_array($localConfig)) {
        $config = array_replace_recursive($config, $localConfig);
    } else {
        error_log('Private config.local.php must return a PHP array; safe defaults selected.');
    }
}
$site_settings = load_site_settings($config);
$catalog = new CatalogRepository($config, APP_ROOT . '/storage/catalog.json');
