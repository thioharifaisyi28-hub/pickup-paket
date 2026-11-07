<?php

/**
 * public/index.php - Front Controller
 */

// ========================================
// DEBUG MODE (sementara untuk cek error)
// ========================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ========================================
// OUTPUT BUFFER
// ========================================

ob_start();

// ========================================
// DEFINE BASE PATH
// ========================================
// Karena source code ada di /secure_app
// dan index.php ada di /public_html
// ========================================

define('BASE_PATH', __DIR__ . '/../secure_app');

// ========================================
// CHECK ENV FILE
// ========================================

if (!file_exists(BASE_PATH . '/.env')) {
    die('ERROR: .env file not found');
}

// ========================================
// LOAD HELPERS & ENVIRONMENT
// ========================================

require_once BASE_PATH . '/app/Helpers/helpers.php';

loadEnv(BASE_PATH . '/.env');

define('APP_DEBUG', (bool)env('APP_DEBUG', false));

// ========================================
// TIMEZONE
// ========================================

date_default_timezone_set((string)env('APP_TIMEZONE', 'Asia/Jakarta'));

// ========================================
// SECURE & ISOLATED SESSION
// ========================================

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443);

session_name('PICKUP_SKRIPSI_SESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => (bool)env('SESSION_SECURE_COOKIE', false) && $isHttps,
    'httponly' => (bool)env('SESSION_HTTP_ONLY', true),
    'samesite' => 'Lax'
]);

session_start();

// ========================================
// GLOBAL ERROR HANDLER
// ========================================

set_exception_handler(function ($e) {

    error_log($e);

    if (APP_DEBUG) {

        echo '<pre>';
        print_r($e);
        echo '</pre>';

    } else {

        http_response_code(500);
        echo 'Internal Server Error';
    }
});

// ========================================
// SIMPLE AUTOLOADER
// ========================================

spl_autoload_register(function ($class) {

    $paths = [

        '/core/',
        '/app/Controllers/',
        '/app/Models/',
        '/app/Services/',
        '/app/Middleware/'

    ];

    foreach ($paths as $path) {

        $file = BASE_PATH . $path . $class . '.php';

        if (file_exists($file)) {

            require_once $file;
            return;
        }
    }
});

// ========================================
// CHECK ROUTES FILE
// ========================================

if (!file_exists(BASE_PATH . '/routes/web.php')) {
    die('ERROR: routes/web.php not found');
}

// ========================================
// LOAD ROUTES
// ========================================

require_once BASE_PATH . '/routes/web.php';

// ========================================
// CHECK APP CLASS
// ========================================

if (!class_exists('App')) {
    die('ERROR: App class not found');
}

// ========================================
// RUN APPLICATION
// ========================================

App::dispatch();

// ========================================
// END OUTPUT BUFFER
// ========================================

ob_end_flush();
