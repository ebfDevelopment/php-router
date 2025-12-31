<?php

namespace PHPRouter\Middlewares;

use PHPRouter\Middleware;

/**
 * Middleware para validar referer
 * Garante que requisições venham de origens permitidas
 */
class RefererMiddleware extends Middleware
{
    /**
     * Lista de domínios permitidos
     * Exemplo: ['meusite.com', 'www.meusite.com', 'api.meusite.com']
     */
    protected $allowedDomains = [];

    /**
     * Se true, permite requisições sem referer (útil para acesso direto)
     */
    protected $allowEmpty = false;

    /**
     * Se true, verifica apenas se o domínio está na lista
     * Se false, verifica domínio + protocolo (http/https)
     */
    protected $strictProtocol = false;

    /**
     * Construtor permite passar domínios permitidos
     */
    public function __construct(array $allowedDomains = [])
    {
        if (!empty($allowedDomains)) {
            $this->allowedDomains = $allowedDomains;
        } else {
            // Se não passar nada, usa o domínio atual
            $this->allowedDomains = [$this->getCurrentDomain()];
        }
    }

    public function handle(callable $next)
    {
        if (!$this->isValidReferer()) {
            // Se for requisição AJAX/API, retorna JSON
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Acesso negado. Origem da requisição não permitida.',
                    'error' => 'INVALID_REFERER'
                ], 403);
            }

            // Aborta com erro 403
            $this->abort(403, 'Acesso negado. Origem da requisição não permitida.');
        }

        // Referer válido, continua
        return $next();
    }

    /**
     * Verifica se o referer é válido
     */
    protected function isValidReferer(): bool
    {
        $referer = $this->getReferer();

        // Se não há referer
        if (empty($referer)) {
            return $this->allowEmpty;
        }

        // Extrai o domínio do referer
        $refererDomain = $this->extractDomain($referer);

        // Verifica se o domínio está na lista de permitidos
        foreach ($this->allowedDomains as $allowed) {
            if ($this->strictProtocol) {
                // Verifica protocolo + domínio
                if ($this->matchesWithProtocol($referer, $allowed)) {
                    return true;
                }
            } else {
                // Verifica apenas domínio
                if ($this->matchesDomain($refererDomain, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtém o referer da requisição
     */
    protected function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Extrai o domínio de uma URL
     */
    protected function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Verifica se o domínio corresponde (sem protocolo)
     */
    protected function matchesDomain(string $refererDomain, string $allowedDomain): bool
    {
        // Remove www. para comparação
        $refererDomain = preg_replace('/^www\./', '', $refererDomain);
        $allowedDomain = preg_replace('/^www\./', '', $allowedDomain);

        return $refererDomain === $allowedDomain;
    }

    /**
     * Verifica se corresponde com protocolo
     */
    protected function matchesWithProtocol(string $referer, string $allowed): bool
    {
        // Normaliza as URLs
        $referer = rtrim($referer, '/');
        $allowed = rtrim($allowed, '/');

        return strpos($referer, $allowed) === 0;
    }

    /**
     * Obtém o domínio atual do servidor
     */
    protected function getCurrentDomain(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    /**
     * Verifica se é uma requisição AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Define domínios permitidos
     */
    public function setAllowedDomains(array $domains): self
    {
        $this->allowedDomains = $domains;
        return $this;
    }

    /**
     * Adiciona um domínio permitido
     */
    public function addAllowedDomain(string $domain): self
    {
        $this->allowedDomains[] = $domain;
        return $this;
    }

    /**
     * Define se permite referer vazio
     */
    public function setAllowEmpty(bool $allow): self
    {
        $this->allowEmpty = $allow;
        return $this;
    }

    /**
     * Define se verifica protocolo estritamente
     */
    public function setStrictProtocol(bool $strict): self
    {
        $this->strictProtocol = $strict;
        return $this;
    }
}