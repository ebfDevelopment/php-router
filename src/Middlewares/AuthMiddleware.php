<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware de autenticação
 * Protege rotas que requerem usuário logado
 */
class AuthMiddleware extends Middleware
{
    /**
     * Caminho de redirecionamento quando não autenticado
     */
    protected $redirectTo = '/login';

    /**
     * Nome da sessão que indica autenticação
     */
    protected $sessionKey = 'user_authenticated';

    public function handle(callable $next)
    {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está autenticado
        if (!$this->isAuthenticated()) {
            // Se for requisição AJAX/API, retorna JSON
            if ($this->isAjaxRequest()) {
                $redirectUrl = $this->addBasePath($this->redirectTo);
                $this->json([
                    'success' => false,
                    'message' => 'Não autenticado',
                    'redirect' => $redirectUrl
                ], 401);
            }

            // Redireciona para a página de login (usando header diretamente)
            $redirectUrl = $this->addBasePath($this->redirectTo);
            \header('Location: ' . $redirectUrl);
            exit;
        }

        // Usuário autenticado, continua para o próximo middleware/controller
        return $next();
    }

    /**
     * Redireciona para uma URL (considerando base path)
     */
    protected function redirect(string $url): void
    {
        if ($this->useBasePath) {
            $url = $this->addBasePath($url);
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Adiciona o base path à URL (para aplicações em subdiretórios)
     */
    protected function addBasePath(string $url): string
    {
        // Se a URL já começa com http:// ou https://, retorna como está
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));

        // Se o basePath não for raiz e a URL não começar com o basePath
        if ($basePath !== '/' && strpos($url, $basePath) !== 0) {
            $url = rtrim($basePath, '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Verifica se o usuário está autenticado
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION[$this->sessionKey]) && $_SESSION[$this->sessionKey] === true;
    }

    /**
     * Verifica se é uma requisição AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}