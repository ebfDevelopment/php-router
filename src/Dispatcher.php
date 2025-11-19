<?php

namespace PHPRouter;

use PHPRouter\MiddlewareManager;

class Dispatcher
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Despacha a requisição para o controller apropriado
     */
    public function dispatch(string $url): void
    {
        try {
            // Limpa a URL
            $url = $this->cleanUrl($url);

            // Obtém o método HTTP
            $httpMethod = $_SERVER['REQUEST_METHOD'];

            // Busca a rota correspondente
            $route = $this->router->resolve($url, $httpMethod);

            if ($route === null) {
                $this->notFound();
                return;
            }

            // Instancia o controller
            $controllerClass = $route['controller'];

            if ($controllerClass && !class_exists($controllerClass)) {
                throw new \Exception("Controller {$controllerClass} não encontrado");
            }

            $controller = $controllerClass ? new $controllerClass() : null;
            $method = $route['method'];

            if ($controller && !method_exists($controller, $method) && !is_callable($method)) {
                throw new \Exception("Método {$method} não encontrado no controller {$controllerClass}");
            }

            // Executa os middlewares antes do controller
            $middlewares = $route['middleware'] ?? [];

            MiddlewareManager::run($middlewares, function() use ($controller, $method, $route) {
                // Chama o método do controller com os parâmetros
                if (is_callable($method)) {
                    // Se for uma closure
                    call_user_func_array($method, $route['params']);
                } else {
                    // Se for um método de controller
                    call_user_func_array([$controller, $method], $route['params']);
                }
            });

        } catch (\Exception $e) {
            $this->error($e);
        }
    }

    /**
     * Limpa e normaliza a URL
     */
    private function cleanUrl(string $url): string
    {
        $url = trim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);

        return $url === '' ? '/' : '/' . $url;
    }

    /**
     * Exibe página de erro 404
     */
    private function notFound(): void
    {
        http_response_code(404);

        if (file_exists(__DIR__ . '/../app/Views/errors/404.php')) {
            require_once __DIR__ . '/../app/Views/errors/404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }

        exit;
    }

    /**
     * Exibe página de erro 500
     */
    private function error(\Exception $e): void
    {
        http_response_code(500);

        if (file_exists(__DIR__ . '/../app/Views/errors/500.php')) {
            $error = $e;
            require_once __DIR__ . '/../app/Views/errors/500.php';
        } else {
            echo '<h1>500 - Erro interno</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }

        exit;
    }
}