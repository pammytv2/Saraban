<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Router อย่างง่าย — รองรับ placeholder แบบ {id}
 * ตัวอย่าง: $router->get('/documents/{id}', 'DocumentController@show');
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, params:string[], handler:string}> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '([^/]+)';
            },
            $path
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    /**
     * หาเส้นทางภายในระบบจาก URL ที่ขอเข้ามา
     * รองรับทั้งแบบมี rewrite (/documents) และแบบ PATH_INFO ของ IIS (/index.php/documents)
     */
    private function resolvePath(string $uri): string
    {
        // IIS/FastCGI ให้ PATH_INFO มาแล้ว ใช้ได้เลย แม่นที่สุด
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if ($pathInfo !== '') {
            return '/' . trim($pathInfo, '/');
        }

        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // ตัดคำนำหน้าออก ลองทั้ง BASE_URL (อาจลงท้ายด้วย index.php) และโฟลเดอร์ที่ index.php อยู่
        foreach ([BASE_URL, ASSET_URL] as $prefix) {
            if ($prefix !== '' && strpos($path, $prefix) === 0) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->resolvePath($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            array_shift($matches);
            $args = [];
            foreach ($route['params'] as $i => $name) {
                $args[$name] = $matches[$i] ?? null;
            }

            [$class, $action] = explode('@', $route['handler']);
            $fqcn = 'App\\Controllers\\' . $class;

            if (!class_exists($fqcn)) {
                throw new \RuntimeException("ไม่พบ controller: {$fqcn}");
            }
            $controller = new $fqcn();
            if (!method_exists($controller, $action)) {
                throw new \RuntimeException("ไม่พบ action: {$fqcn}@{$action}");
            }

            $controller->{$action}(...array_values($args));
            return;
        }

        http_response_code(404);
        (new class extends Controller {
            public function notFound(): void
            {
                $this->view('errors/show', [
                    'status'  => 404,
                    'message' => 'ไม่พบหน้าที่ต้องการ',
                ], '');
            }
        })->notFound();
    }
}
