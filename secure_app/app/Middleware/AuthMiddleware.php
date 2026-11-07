<?php
/**
 * AuthMiddleware - Cek apakah user sudah login
 */

require_once BASE_PATH . '/core/Middleware.php';

class AuthMiddleware extends Middleware
{
    public function handle(): void
    {
        // Cek session timeout
        $lifetime = (int)(env('SESSION_LIFETIME', 30)) * 60; // dalam detik

        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $lifetime) {
                // Session expired
                session_unset();
                session_destroy();
                session_start();
                $_SESSION['flash'] = [
                    'type'    => 'warning',
                    'message' => 'Sesi Anda telah berakhir. Silakan login kembali.'
                ];
                App::redirect('/login');
            }
        }

        // Update last activity
        $_SESSION['last_activity'] = time();

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            $_SESSION['flash'] = [
                'type'    => 'error',
                'message' => 'Silakan login terlebih dahulu.'
            ];
            App::redirect('/login');
        }
    }
}
