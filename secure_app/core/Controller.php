<?php
/**
 * Controller.php - Base Controller
 * 
 * Semua controller inherit dari class ini.
 * Menyediakan method untuk render view, redirect, dan response JSON.
 */

class Controller
{
    /**
     * Render view dengan layout
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        // Extract data agar bisa digunakan di view
        extract($data);

        // Set content path
        $contentFile = BASE_PATH . '/resources/views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($contentFile)) {
            App::abort(404, "View not found: {$viewPath}");
            return;
        }

        // Start output buffering for content
        ob_start();
        require $contentFile;
        $content = ob_get_clean();

        // Render layout with content
        $layoutFile = BASE_PATH . '/resources/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Render view tanpa layout (partial)
     */
    protected function partial(string $viewPath, array $data = []): void
    {
        extract($data);
        $file = BASE_PATH . '/resources/views/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }

    /**
     * Redirect ke path
     */
    protected function redirect(string $path): void
    {
        App::redirect($path);
    }

    /**
     * Response JSON (untuk AJAX)
     * Clears any buffered output (e.g. PHP notices) before sending clean JSON.
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        // Discard any buffered output (PHP notices/warnings) that would corrupt JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Get session user
     */
    protected function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            App::abort(403, 'Invalid CSRF token');
            return false;
        }
        return true;
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message
        ];
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}
