<?php

namespace PHPRouter;

abstract class Middleware
{
    /**
     * Processa o middleware
     * 
     * @param callable $next Próximo middleware ou controller
     * @return mixed
     */
    abstract public function handle(callable $next);

    /**
     * Redireciona para uma URL
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Retorna resposta JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Aborta com erro HTTP
     */
    protected function abort(int $code = 403, string $message = ''): void
    {
        http_response_code($code);
        
        if (empty($message)) {
            $messages = [
                403 => 'Acesso negado',
                401 => 'Não autorizado',
                404 => 'Não encontrado',
                500 => 'Erro interno'
            ];
            $message = $messages[$code] ?? 'Erro';
        }

        echo "<h1>{$code} - {$message}</h1>";
        exit;
    }

    /**
     * Define um header HTTP
     */
    protected function setHeader(string $name, string $value): void
    {
        header("{$name}: {$value}");
    }
}