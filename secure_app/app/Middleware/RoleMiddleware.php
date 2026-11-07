<?php
/**
 * RoleMiddleware - Cek apakah user memiliki role admin
 */

require_once BASE_PATH . '/core/Middleware.php';

class RoleMiddleware extends Middleware
{
    public function handle(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['flash'] = [
                'type'    => 'error',
                'message' => 'Anda tidak memiliki akses ke halaman ini.'
            ];
            App::redirect('/dashboard');
        }
    }
}
