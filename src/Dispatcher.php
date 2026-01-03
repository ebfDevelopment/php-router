<?php

namespace PHPRouter;

class Dispatcher
{
    private $router;
    private $notFoundHandler;
    private $errorHandler;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Define um handler customizado para 404
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Define um handler customizado para erros
     */
    public function setErrorHandler(callable $handler): void
    {
        $this->errorHandler = $handler;
    }

    /**
     * Despacha a requisição para o controller apropriado
     */
    public function dispatch(?string $url = null, ?string $method = null): void
    {
        try {
            // Obtém URL e método da requisição atual se não foram passados
            $url = $url ?? $this->getCurrentUrl();
            $method = $method ?? $_SERVER['REQUEST_METHOD'];

            // Limpa a URL
            $url = $this->cleanUrl($url);

            // Busca a rota correspondente
            $route = $this->router->resolve($url, $method);

            if ($route === null) {
                $this->notFound();
                return;
            }

            // Executa os middlewares se houver
            $middlewares = $route['middleware'] ?? [];

            if (!empty($middlewares)) {
                MiddlewareManager::run($middlewares, function() use ($route) {
                    $this->executeRoute($route);
                });
            } else {
                $this->executeRoute($route);
            }

        } catch (\Exception $e) {
            $this->error($e);
        }
    }

    /**
     * Executa a rota (controller/closure)
     */
    private function executeRoute(array $route): void
    {
        $controller = $route['controller'];
        $method = $route['method'];
        $params = $route['params'];

        // Se for uma closure
        if (is_callable($method)) {
            call_user_func_array($method, $params);
            return;
        }

        // Se for um controller
        if (!class_exists($controller)) {
            throw new \Exception("Controller {$controller} não encontrado");
        }

        $instance = new $controller();

        if (!method_exists($instance, $method)) {
            throw new \Exception("Método {$method} não encontrado no controller {$controller}");
        }

        call_user_func_array([$instance, $method], $params);
    }

    /**
     * Obtém a URL atual da requisição
     */
    private function getCurrentUrl(): string
    {
        $url = $_GET['url'] ?? $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }

        return $url;
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

        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
            echo '<p>A página que você procura não existe.</p>';
        }

        exit;
    }

    /**
     * Exibe página de erro 500
     */
    private function error(\Exception $e): void
    {
        http_response_code(500);

        if ($this->errorHandler) {
            call_user_func($this->errorHandler, $e);
        } else {
            echo '<h1>500 - Erro interno</h1>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';

            // Mostra stack trace apenas em desenvolvimento
            if (getenv('APP_ENV') !== 'production') {
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
        }

        exit;
    }
}