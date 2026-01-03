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
        $url = $this->addBasePath($url);

        // Usa header diretamente para evitar conflito com funções globais
        \header('Location: ' . $url);
        exit;
    }

    /**
     * Adiciona o base path à URL (para aplicações em subdiretórios)
     */
    protected function addBasePath(string $url): string
    {
        // Se a URL já é absoluta (começa com http:// ou https://), retorna como está
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // Pega o script name e extrai o base path
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = str_replace('\\', '/', dirname($scriptName));

        // Normaliza o base path
        $basePath = rtrim($basePath, '/');

        // Se o basePath for vazio ou apenas "/", não adiciona nada
        if (empty($basePath) || $basePath === '/') {
            return $url;
        }

        // Remove o base path da URL se já existir (evita duplicação)
        if (strpos($url, $basePath) === 0) {
            return $url;
        }

        // Adiciona o base path
        return $basePath . '/' . ltrim($url, '/');
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
}