<?php

namespace PHPRouter;

class MiddlewareManager
{
    /**
     * Alias de middlewares registrados
     */
    private static $aliases = [];

    /**
     * Middlewares globais (executados em todas as rotas)
     */
    private static $globalMiddlewares = [];

    /**
     * Registra um alias de middleware
     */
    public static function register(string $alias, string $class): void
    {
        self::$aliases[$alias] = $class;
    }

    /**
     * Registra múltiplos aliases de uma vez
     */
    public static function registerMultiple(array $middlewares): void
    {
        foreach ($middlewares as $alias => $class) {
            self::register($alias, $class);
        }
    }

    /**
     * Adiciona um middleware global
     */
    public static function addGlobal(string $class): void
    {
        self::$globalMiddlewares[] = $class;
    }

    /**
     * Resolve o nome do middleware para a classe
     */
    public static function resolve(string $alias): ?string
    {
        return self::$aliases[$alias] ?? null;
    }

    /**
     * Executa uma pilha de middlewares
     *
     * @param array $middlewares Lista de middlewares (aliases ou classes)
     * @param callable $finalCallback Callback final (controller)
     * @return mixed
     */
    public static function run(array $middlewares, callable $finalCallback)
    {
        // Adiciona middlewares globais no início
        $middlewares = array_merge(self::$globalMiddlewares, $middlewares);

        // Se não houver middlewares, executa direto o callback
        if (empty($middlewares)) {
            return $finalCallback();
        }

        // Cria a pilha de execução
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function () use ($next, $middleware) {
                    // Resolve o alias para a classe
                    $class = self::resolve($middleware) ?? $middleware;

                    if (!class_exists($class)) {
                        throw new \Exception("Middleware {$class} não encontrado");
                    }

                    $instance = new $class();

                    if (!method_exists($instance, 'handle')) {
                        throw new \Exception("Middleware {$class} deve ter um método handle()");
                    }

                    return $instance->handle($next);
                };
            },
            $finalCallback
        );

        return $pipeline();
    }

    /**
     * Retorna todos os aliases registrados
     */
    public static function getAliases(): array
    {
        return self::$aliases;
    }

    /**
     * Retorna os middlewares globais
     */
    public static function getGlobals(): array
    {
        return self::$globalMiddlewares;
    }
}