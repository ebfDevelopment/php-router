<?php

namespace PHPRouter;

class Router
{
    private $routes = [];
    private $separator = '::';
    private $groupPrefix = '';
    private $groupMiddleware = [];
    private $lastRoute = null; // Para método encadeado middleware()

    /**
     * Define o separador entre controller e método
     */
    public function setSeparator(string $separator): self
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Obtém o separador atual
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * Agrupa rotas com prefixo e/ou middleware comum
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        // Adiciona o prefixo do grupo
        if (isset($attributes['prefix'])) {
            $this->groupPrefix .= '/' . trim($attributes['prefix'], '/');
        }

        // Adiciona middleware do grupo
        if (isset($attributes['middleware'])) {
            $middleware = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        }

        // Executa o callback com as rotas do grupo
        $callback($this);

        // Restaura os valores anteriores
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    /**
     * Adiciona uma rota GET
     */
    public function get(string $path, $handler, ?string $method = null): self
    {
        return $this->addRoute('GET', $path, $handler, $method);
    }

    /**
     * Adiciona uma rota POST
     */
    public function post(string $path, $handler, ?string $method = null): self
    {
        return $this->addRoute('POST', $path, $handler, $method);
    }

    /**
     * Adiciona uma rota PUT
     */
    public function put(string $path, $handler, ?string $method = null): self
    {
        return $this->addRoute('PUT', $path, $handler, $method);
    }

    /**
     * Adiciona uma rota DELETE
     */
    public function delete(string $path, $handler, ?string $method = null): self
    {
        return $this->addRoute('DELETE', $path, $handler, $method);
    }

    /**
     * Adiciona uma rota PATCH
     */
    public function patch(string $path, $handler, ?string $method = null): self
    {
        return $this->addRoute('PATCH', $path, $handler, $method);
    }

    /**
     * Adiciona múltiplas rotas de uma vez
     */
    public function match(array $methods, string $path, $handler, ?string $method = null): self
    {
        foreach ($methods as $httpMethod) {
            $this->addRoute(strtoupper($httpMethod), $path, $handler, $method);
        }
        return $this;
    }

    /**
     * Adiciona uma rota para todos os métodos HTTP
     */
    public function any(string $path, $handler, ?string $method = null): self
    {
        return $this->match(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler, $method);
    }

    /**
     * Adiciona middleware(s) à última rota registrada
     * Uso: $router->get('/rota', 'Controller::method')->middleware('auth');
     */
    public function middleware($middleware): self
    {
        if ($this->lastRoute === null) {
            throw new \RuntimeException('Nenhuma rota definida para aplicar middleware');
        }

        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        
        // Adiciona os middlewares à última rota
        $this->routes[$this->lastRoute]['middleware'] = array_merge(
            $this->routes[$this->lastRoute]['middleware'],
            $middlewares
        );

        return $this;
    }

    /**
     * Adiciona um nome à última rota registrada
     * Uso: $router->get('/rota', 'Controller::method')->name('rota.nome');
     */
    public function name(string $name): self
    {
        if ($this->lastRoute === null) {
            throw new \RuntimeException('Nenhuma rota definida para nomear');
        }

        $this->routes[$this->lastRoute]['name'] = $name;

        return $this;
    }

    /**
     * Método privado para adicionar rotas
     */
    private function addRoute(string $httpMethod, string $path, $handler, ?string $method = null): self
    {
        // Aplica o prefixo do grupo
        $fullPath = $this->groupPrefix . '/' . trim($path, '/');
        $fullPath = '/' . trim($fullPath, '/');

        // Se o path for vazio ou apenas "/", mantém "/"
        if ($fullPath === '' || $fullPath === '//') {
            $fullPath = '/';
        }

        // Processa o handler
        if (is_string($handler) && strpos($handler, $this->separator) !== false) {
            // Formato: "Controller::method"
            [$controller, $method] = explode($this->separator, $handler, 2);
        } elseif (is_string($handler) && $method !== null) {
            // Formato: Controller::class, 'method'
            $controller = $handler;
        } elseif (is_array($handler) && count($handler) === 2) {
            // Formato: [Controller::class, 'method']
            [$controller, $method] = $handler;
        } elseif (is_callable($handler)) {
            // Closure ou callable
            $controller = null;
            $method = $handler;
        } else {
            throw new \InvalidArgumentException("Formato de handler inválido");
        }

        $this->routes[] = [
            'http_method' => $httpMethod,
            'path' => $fullPath,
            'controller' => $controller,
            'method' => $method,
            'middleware' => $this->groupMiddleware,
            'name' => null
        ];

        // Salva o índice da última rota para métodos encadeados
        $this->lastRoute = count($this->routes) - 1;

        return $this;
    }

    /**
     * Busca uma rota que corresponda ao caminho e método HTTP
     */
    public function resolve(string $url, string $httpMethod): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['http_method'] !== $httpMethod) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);

            if (preg_match($pattern, $url, $matches)) {
                array_shift($matches); // Remove o match completo

                return [
                    'controller' => $route['controller'],
                    'method' => $route['method'],
                    'params' => $matches,
                    'middleware' => $route['middleware'],
                    'name' => $route['name']
                ];
            }
        }

        return null;
    }

    /**
     * Gera URL para uma rota nomeada
     */
    public function route(string $name, array $params = []): ?string
    {
        foreach ($this->routes as $route) {
            if ($route['name'] === $name) {
                $path = $route['path'];
                
                // Substitui parâmetros
                foreach ($params as $key => $value) {
                    $path = preg_replace('/\{' . $key . '\?\}/', $value, $path);
                    $path = preg_replace('/\{' . $key . '\}/', $value, $path);
                }
                
                // Remove parâmetros opcionais não preenchidos
                $path = preg_replace('/\{[a-zA-Z0-9_]+\?\}/', '', $path);
                
                return $path;
            }
        }

        return null;
    }

    /**
     * Converte o padrão de rota para regex
     */
    private function convertToRegex(string $path): string
    {
        // Substitui {parametro} por um grupo de captura regex
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_-]+)', $path);

        // Substitui {parametro?} por um grupo opcional
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?\}/', '([a-zA-Z0-9_-]*)', $pattern);

        $pattern = '#^' . $pattern . '$#';

        return $pattern;
    }

    /**
     * Retorna todas as rotas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Lista todas as rotas de forma formatada (útil para debug)
     */
    public function listRoutes(): array
    {
        $list = [];

        foreach ($this->routes as $route) {
            $handler = $route['controller']
                ? $route['controller'] . $this->separator . (is_string($route['method']) ? $route['method'] : 'Closure')
                : 'Closure';

            $list[] = [
                'method' => $route['http_method'],
                'path' => $route['path'],
                'handler' => $handler,
                'middleware' => $route['middleware'],
                'name' => $route['name']
            ];
        }

        return $list;
    }
}