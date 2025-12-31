<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware para proteger rotas de CRON Jobs
 * Valida token de autenticação enviado via header
 */
class CronMiddleware extends Middleware
{
    /**
     * Nome do header que contém o token
     */
    protected $headerName = 'X-Cron-Auth';

    /**
     * Token esperado (configure via variável de ambiente)
     */
    protected $expectedToken = null;

    /**
     * Se true, permite execução via CLI (command line)
     */
    protected $allowCli = true;

    public function __construct()
    {
        // Tenta carregar o token de uma variável de ambiente
        $this->expectedToken = getenv('CRON_TOKEN') ?: $this->getDefaultToken();
    }

    public function handle(callable $next)
    {
        // Se está rodando via CLI (linha de comando), permite
        if ($this->allowCli && $this->isCli()) {
            return $next();
        }

        // Verifica o token no header
        if (!$this->isValidToken()) {
            $this->json([
                'success' => false,
                'message' => 'Token de autenticação inválido ou ausente',
                'error' => 'INVALID_CRON_TOKEN'
            ], 403);
        }

        // Token válido, continua
        return $next();
    }

    /**
     * Verifica se o token é válido
     */
    protected function isValidToken(): bool
    {
        $token = $this->getTokenFromRequest();

        if (empty($token)) {
            return false;
        }

        // Comparação segura contra timing attacks
        return hash_equals($this->expectedToken, $token);
    }

    /**
     * Obtém o token da requisição
     */
    protected function getTokenFromRequest(): ?string
    {
        // Tenta pegar do header customizado
        $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper($this->headerName));
        
        if (isset($_SERVER[$headerKey])) {
            return $_SERVER[$headerKey];
        }

        // Tenta pegar do header Authorization Bearer
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
                return $matches[1];
            }
        }

        // Tenta pegar de parâmetro GET (menos seguro, mas funciona com CURL simples)
        if (isset($_GET['cron_token'])) {
            return $_GET['cron_token'];
        }

        return null;
    }

    /**
     * Verifica se está rodando via CLI
     */
    protected function isCli(): bool
    {
        return php_sapi_name() === 'cli' || defined('STDIN');
    }

    /**
     * Gera um token padrão (deve ser sobrescrito em produção)
     */
    protected function getDefaultToken(): string
    {
        // Em produção, SEMPRE use uma variável de ambiente
        // Este é apenas um fallback para desenvolvimento
        return '6b6f52d0d8faaba8a1e6dcd0109d877e';
    }

    /**
     * Define um token customizado
     */
    public function setToken(string $token): self
    {
        $this->expectedToken = $token;
        return $this;
    }

    /**
     * Define o nome do header
     */
    public function setHeaderName(string $name): self
    {
        $this->headerName = $name;
        return $this;
    }

    /**
     * Define se permite execução via CLI
     */
    public function setAllowCli(bool $allow): self
    {
        $this->allowCli = $allow;
        return $this;
    }
}