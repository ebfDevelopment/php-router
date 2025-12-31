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
                $this->json([
                    'success' => false,
                    'message' => 'Não autenticado',
                    'redirect' => $this->redirectTo
                ], 401);
            }

            // Redireciona para a página de login
            $this->redirect($this->redirectTo);
        }

        // Usuário autenticado, continua para o próximo middleware/controller
        return $next();
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