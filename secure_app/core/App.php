<?php
/**
 * App.php - Router Engine / Front Controller
 * Professional Production Version
 */

class App
{
    /**
     * Registered routes
     */
    private static array $routes = [];

    /**
     * Base path for production hosting (auto-detected if empty)
     */
    private static string $basePath = '';

    /**
     * Auto-detect base path dynamically (supports VirtualHost root & localhost subdirectory)
     */
    private static function getBasePath(): string
    {
        if (self::$basePath !== '') {
            return self::$basePath;
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim($scriptDir, '/');

        if ($scriptDir === '' || $scriptDir === '.') {
            return '';
        }

        return $scriptDir;
    }

    /**
     * Register GET route
     */
    public static function get(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('GET', $uri, $action, $middleware);
    }

    public static function post(string $uri, string $action, array $middleware = []): void
    {
        self::addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Register route for multiple methods
     */
    public static function match(array $methods, string $uri, string $action, array $middleware = []): void
    {
        foreach ($methods as $method) {
            self::addRoute(strtoupper($method), $uri, $action, $middleware);
        }
    }

    /**
     * Add route to registry
     */
    private static function addRoute(
        string $method,
        string $uri,
        string $action,
        array $middleware
    ): void {

        // Convert parameter route
        // Example: /paket/{id}
        // Result : /paket/123

        $pattern = preg_replace(
            '/\{([a-zA-Z_]+)\}/',
            '(?P<$1>[^/]+)',
            $uri
        );

        $pattern = '#^' . $pattern . '$#';

        self::$routes[] = [
            'method'      => $method,
            'uri'         => $uri,
            'pattern'     => $pattern,
            'action'      => $action,
            'middleware'  => $middleware
        ];
    }

    /**
     * Dispatch request
     */
    public static function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri    = self::getRequestUri();

        foreach (self::$routes as $route) {

            // Skip if method not match
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Match URI
            if (preg_match($route['pattern'], $requestUri, $matches)) {

                /**
                 * Run Middleware
                 */
                foreach ($route['middleware'] as $middlewareClass) {

                    $middlewareFile = BASE_PATH .
                        '/app/Middleware/' .
                        $middlewareClass .
                        '.php';

                    if (file_exists($middlewareFile)) {

                        require_once $middlewareFile;

                        $middleware = new $middlewareClass();

                        if (method_exists($middleware, 'handle')) {
                            $middleware->handle();
                        }
                    }
                }

                /**
                 * Parse Controller@Method
                 */
                [$controllerName, $methodName] = explode('@', $route['action']);

                $controllerFile = BASE_PATH .
                    '/app/Controllers/' .
                    $controllerName .
                    '.php';

                /**
                 * Check controller exists
                 */
                if (!file_exists($controllerFile)) {

                    self::abort(
                        404,
                        "Controller not found: {$controllerName}"
                    );

                    return;
                }

                require_once $controllerFile;

                /**
                 * Check class exists
                 */
                if (!class_exists($controllerName)) {

                    self::abort(
                        500,
                        "Controller class not found: {$controllerName}"
                    );

                    return;
                }

                $controller = new $controllerName();

                /**
                 * Check method exists
                 */
                if (!method_exists($controller, $methodName)) {

                    self::abort(
                        404,
                        "Method not found: {$methodName}"
                    );

                    return;
                }

                /**
                 * Get URL params
                 */
                $params = array_filter(
                    $matches,
                    'is_string',
                    ARRAY_FILTER_USE_KEY
                );

                /**
                 * Call controller method
                 */
                call_user_func_array(
                    [$controller, $methodName],
                    $params
                );

                return;
            }
        }

        /**
         * No route matched
         */
        self::abort(404);
    }

    /**
     * Get request URI
     * Hosting & local subdirectory compatible version
     */
    private static function getRequestUri(): string
    {
        // Example:
        // https://domain.com/login
        // Result:
        // /login

        $uri = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        ) ?: '/';

        $basePath = self::getBasePath();
        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Remove trailing slash
        if ($uri !== '/' && $uri !== '') {
            $uri = rtrim($uri, '/');
        }

        // Default root
        if (empty($uri)) {
            $uri = '/';
        }

        return $uri;
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $path): void
    {
        $path = '/' . ltrim($path, '/');
        $basePath = self::getBasePath();

        if ($basePath !== '' && ($path === $basePath || strpos($path, $basePath . '/') === 0)) {
            header('Location: ' . $path);
        } else {
            header('Location: ' . $basePath . $path);
        }

        exit;
    }

    /**
     * Generate URL
     */
    public static function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        $basePath = self::getBasePath();

        if ($basePath !== '' && ($path === $basePath || strpos($path, $basePath . '/') === 0)) {
            return $path;
        }

        return $basePath . $path;
    }

    /**
     * Abort / Error handler
     */
    public static function abort(
        int $code,
        string $message = ''
    ): void {

        http_response_code($code);

        /**
         * 404 Error
         */
        if ($code === 404) {

            $errorFile = BASE_PATH .
                '/resources/views/errors/404.php';

            if (file_exists($errorFile)) {

                require $errorFile;

            } else {

                echo '<h1>404 - Page Not Found</h1>';
            }

        } else {

            /**
             * Other errors
             */
            echo "<h1>Error {$code}</h1>";

            if (env('APP_DEBUG', false)) {

                echo "<p>{$message}</p>";
            }
        }

        exit;
    }
}
