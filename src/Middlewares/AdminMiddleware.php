<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware de administrador
 * Garante que apenas usuários com permissões de admin acessem a rota
 */
class AdminMiddleware extends Middleware
{
    /**
     * Caminho de redirecionamento quando não autorizado
     */
    protected $redirectTo = '/';

    /**
     * Nome da sessão que indica nível de permissão
     */
    protected $roleKey = 'user_role';

    /**
     * Role necessário para acessar
     */
    protected $requiredRole = 'admin';

    public function handle(callable $next)
    {
        // Inicia a sessão se ainda não foi iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário tem permissões de admin
        if (!$this->isAdmin()) {
            // Se for requisição AJAX/API, retorna JSON
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Acesso negado. Permissões de administrador necessárias.'
                ], 403);
            }

            // Aborta com erro 403
            $this->abort(403, 'Acesso negado. Você não tem permissão para acessar esta página.');
        }

        // Usuário é admin, continua
        return $next();
    }

    /**
     * Verifica se o usuário é administrador
     */
    protected function isAdmin(): bool
    {
        if (!isset($_SESSION[$this->roleKey])) {
            return false;
        }

        // Pode ser uma string ou array de roles
        $userRole = $_SESSION[$this->roleKey];

        if (is_array($userRole)) {
            return in_array($this->requiredRole, $userRole);
        }

        return $userRole === $this->requiredRole;
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