<?php
namespace Tihloh\PhpRouter;

class Router
{
    protected string $basePath;
    protected string $viewPath;

    public string $page;
    public array $params = [];
    public bool $isApi = false;

    public string $method;

    public function __construct(string $basePath = '', string $viewPath = __DIR__ . '/../views')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->method = $_SERVER['REQUEST_METHOD'];

        $this->basePath = rtrim($basePath, '/');
        $this->viewPath = rtrim($viewPath, '/');

        $this->resolve();
    }

    private function resolve(): void
    {
        $route = parse_url(
            $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        );

        // Remove base path if specified
        if (!empty($this->basePath) && str_starts_with($route, $this->basePath)) {
            $route = substr(
                $route,
                strlen($this->basePath)
            );
        }

        $route = trim($route, '/');
        $parts = array_values(array_filter(explode('/', $route)));

        if (isset($parts[0]) && $parts[0] === 'api') {
            $this->isApi = true;
            array_shift($parts);
            $this->viewPath = __DIR__ . '/../api';
        }

        $this->page = $this->viewPath . '/home.php';
        $this->params = [];

        while (!empty($parts)) {
            $candidate = $this->viewPath . '/' . implode('/', $parts) . '.php';

            if (file_exists($candidate)) {
                $this->page = $candidate;
                return;
            }

            $candidateIndex = $this->viewPath . '/' . implode('/', $parts) . '/index.php';

            if (file_exists($candidateIndex)) {
                $this->page = $candidateIndex;
                return;
            }

            array_unshift($this->params, array_pop($parts));
        }

        // Home page exists
        if (file_exists($this->page)) {
            return;
        }

        // Fallback to 404
        $this->page = $this->viewPath . '/404.php';
    }

    public function param(int $index, mixed $default = null): mixed
    {
        return $this->params[$index]
            ?? $default;
    }

    public function url(string $url = ''): string
    {
        return $this->basePath . '/' . ltrim($url, '/');
    }

    public function redirect(string $url = ''): never
    {
        header( 'Location: ' . $this->url($url) );
        exit;
    }

    public function back(): never
    {
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? $this->url()));
        exit;
    }

    public function json(array $data): never
    {

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
}