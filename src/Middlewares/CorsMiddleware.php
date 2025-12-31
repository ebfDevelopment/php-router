<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware CORS
 * Configura headers de CORS para APIs
 */
class CorsMiddleware extends Middleware
{
    /**
     * Origens permitidas
     */
    protected $allowedOrigins = ['*'];

    /**
     * Métodos HTTP permitidos
     */
    protected $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];

    /**
     * Headers permitidos
     */
    protected $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Cron-Auth'];

    /**
     * Se true, permite credenciais (cookies, auth headers)
     */
    protected $allowCredentials = true;

    /**
     * Tempo de cache do preflight (em segundos)
     */
    protected $maxAge = 86400; // 24 horas

    public function handle(callable $next)
    {
        // Define os headers CORS
        $this->setCorsHeaders();

        // Se for uma requisição OPTIONS (preflight), responde imediatamente
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Continua para o próximo middleware/controller
        return $next();
    }

    /**
     * Define os headers de CORS
     */
    protected function setCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Access-Control-Allow-Origin
        if ($this->isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (in_array('*', $this->allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
        }

        // Access-Control-Allow-Methods
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));

        // Access-Control-Allow-Headers
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));

        // Access-Control-Allow-Credentials
        if ($this->allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        // Access-Control-Max-Age
        header("Access-Control-Max-Age: {$this->maxAge}");
    }

    /**
     * Verifica se a origem é permitida
     */
    protected function isOriginAllowed(string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * Define origens permitidas
     */
    public function setAllowedOrigins(array $origins): self
    {
        $this->allowedOrigins = $origins;
        return $this;
    }

    /**
     * Define métodos permitidos
     */
    public function setAllowedMethods(array $methods): self
    {
        $this->allowedMethods = $methods;
        return $this;
    }

    /**
     * Define headers permitidos
     */
    public function setAllowedHeaders(array $headers): self
    {
        $this->allowedHeaders = $headers;
        return $this;
    }

    /**
     * Define se permite credenciais
     */
    public function setAllowCredentials(bool $allow): self
    {
        $this->allowCredentials = $allow;
        return $this;
    }
}