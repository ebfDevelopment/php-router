<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware Guest
 * Permite acesso apenas para usuários NÃO autenticados
 * Útil para páginas de login, registro, etc.
 */
class GuestMiddleware extends Middleware
{
    /**
     * Caminho de redirecionamento quando já autenticado
     */
    protected $redirectTo = '/dashboard';

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

        // Se o usuário JÁ está autenticado, redireciona
        if ($this->isAuthenticated()) {
            $redirectUrl = $this->addBasePath($this->redirectTo);
            \header('Location: ' . $redirectUrl);
            exit;
        }

        // Usuário não está autenticado, continua (permite acesso)
        return $next();
    }

    /**
     * Verifica se o usuário está autenticado
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION[$this->sessionKey]) && $_SESSION[$this->sessionKey] === true;
    }
}