# Simple Router

Sistema de roteamento PHP simples, poderoso e flexível com suporte a grupos, middlewares e parâmetros dinâmicos.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Características

- Suporte a todos os métodos HTTP (GET, POST, PUT, DELETE, PATCH)
- Parâmetros dinâmicos e opcionais nas rotas
- Grupos de rotas com prefixos
- Sistema completo de middlewares
- Rotas nomeadas
- Suporte a closures
- Separador customizável (::, @, etc)
- Grupos aninhados ilimitados
- Zero dependências

## Instalação

```bash
composer require edifonttes/php-router
```

## Uso Básico

### Configuração Inicial

```php
<?php

require 'vendor/autoload.php';

use PHPRouter\Router;
use PHPRouter\Dispatcher;

$router = new Router();

// Defina suas rotas
$router->get('/', function() {
    echo "Hello World!";
});

$router->get('/users/{id}', function($id) {
    echo "User ID: " . $id;
});

// Despacha a requisição
$dispatcher = new Dispatcher($router);
$dispatcher->dispatch();
```

### .htaccess (Apache)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

## Documentação

### Métodos HTTP

```php
$router->get('/users', 'UserController::index');
$router->post('/users', 'UserController::store');
$router->put('/users/{id}', 'UserController::update');
$router->delete('/users/{id}', 'UserController::destroy');
$router->patch('/users/{id}', 'UserController::patch');

// Múltiplos métodos
$router->match(['GET', 'POST'], '/form', 'FormController::handle');

// Todos os métodos
$router->any('/webhook', 'WebhookController::handle');
```

### Parâmetros de Rota

```php
// Parâmetro obrigatório
$router->get('/users/{id}', function($id) {
    echo "User: " . $id;
});

// Múltiplos parâmetros
$router->get('/posts/{category}/{id}', function($category, $id) {
    echo "Category: {$category}, ID: {$id}";
});

// Parâmetro opcional (adicione ? no final)
$router->get('/posts/{category}/{page?}', function($category, $page = 1) {
    echo "Category: {$category}, Page: {$page}";
});
```

### Formatos de Handler

```php
// Closure
$router->get('/', function() {
    echo "Home";
});

// Controller::class, 'method'
$router->get('/users', UserController::class, 'index');

// Array [Controller::class, 'method']
$router->get('/users', [UserController::class, 'index']);

// String com separador
$router->get('/users', 'UserController::index');

// Separador customizado
$router->setSeparator('@');
$router->get('/users', 'UserController@index');
```

### Grupos de Rotas

```php
// Grupo com prefixo
$router->group(['prefix' => 'admin'], function($router) {
    // GET /admin/dashboard
    $router->get('/dashboard', 'AdminController::dashboard');
    
    // GET /admin/users
    $router->get('/users', 'AdminController::users');
});

// Grupos aninhados
$router->group(['prefix' => 'api'], function($router) {
    $router->group(['prefix' => 'v1'], function($router) {
        // GET /api/v1/users
        $router->get('/users', 'Api\V1\UserController::index');
    });
});
```

### Middlewares

#### Criando um Middleware

```php
<?php

namespace App\Middlewares;

use PHPRouter\Middleware;

class AuthMiddleware extends Middleware
{
    public function handle(callable $next)
    {
        // Código executado ANTES do controller
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        // Continua para o próximo middleware/controller
        return $next();
    }
}
```

#### Registrando Middlewares

```php
use PHPRouter\MiddlewareManager;

// Registrar aliases
MiddlewareManager::register('auth', AuthMiddleware::class);
MiddlewareManager::register('admin', AdminMiddleware::class);

// Ou múltiplos de uma vez
MiddlewareManager::registerMultiple([
    'auth' => AuthMiddleware::class,
    'admin' => AdminMiddleware::class,
    'cors' => CorsMiddleware::class,
]);

// Middleware global (executado em todas as rotas)
MiddlewareManager::addGlobal(LogMiddleware::class);
```

#### Usando Middlewares nas Rotas

```php
// Grupo com middleware
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/dashboard', 'DashboardController::index');
});

// Múltiplos middlewares
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    $router->get('/admin', 'AdminController::index');
});

// Combinando prefixo e middleware
$router->group(['prefix' => 'api', 'middleware' => 'cors'], function($router) {
    $router->get('/data', 'ApiController::getData');
});
```

### Rotas Nomeadas

```php
// Definir rota nomeada
$router->get('/users/{id}', 'UserController::show')->name('users.show');

// Gerar URL a partir do nome
$url = $router->route('users.show', ['id' => 123]);
// Output: /users/123
```

### Handlers Customizados

```php
$dispatcher = new Dispatcher($router);

// Handler 404 customizado
$dispatcher->setNotFoundHandler(function() {
    echo "<h1>Página não encontrada</h1>";
});

// Handler de erro customizado
$dispatcher->setErrorHandler(function($exception) {
    echo "<h1>Erro: " . $exception->getMessage() . "</h1>";
});

$dispatcher->dispatch();
```

## Exemplos Completos

### API RESTful

```php
$router->group(['prefix' => 'api/v1', 'middleware' => ['cors', 'json']], function($router) {
    
    // Rotas públicas
    $router->post('/login', 'AuthController::login');
    $router->post('/register', 'AuthController::register');
    
    // Rotas autenticadas
    $router->group(['middleware' => 'auth'], function($router) {
        // CRUD de usuários
        $router->get('/users', 'UserController::index');
        $router->get('/users/{id}', 'UserController::show');
        $router->post('/users', 'UserController::store');
        $router->put('/users/{id}', 'UserController::update');
        $router->delete('/users/{id}', 'UserController::destroy');
        
        // Apenas admin
        $router->group(['middleware' => 'admin'], function($router) {
            $router->get('/admin/stats', 'AdminController::stats');
        });
    });
});
```

### Blog com Admin

```php
// Área pública
$router->get('/', 'BlogController::index');
$router->get('/post/{slug}', 'BlogController::show');
$router->get('/category/{name}', 'BlogController::category');

// Área administrativa
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/dashboard', 'Admin\DashboardController::index');
    
    $router->group(['prefix' => 'posts'], function($router) {
        $router->get('/', 'Admin\PostController::index');
        $router->get('/create', 'Admin\PostController::create');
        $router->post('/', 'Admin\PostController::store');
        $router->get('/{id}/edit', 'Admin\PostController::edit');
        $router->put('/{id}', 'Admin\PostController::update');
        $router->delete('/{id}', 'Admin\PostController::destroy');
    });
});
```

## Métodos Úteis

```php
// Listar todas as rotas (debug)
$routes = $router->listRoutes();
print_r($routes);

// Obter todas as rotas
$allRoutes = $router->getRoutes();

// Limpar todas as rotas
$router->clear();

// Limpar middlewares
MiddlewareManager::clear();
```

## Estrutura Recomendada

```
seu-projeto/
├── public/
│   ├── index.php
│   └── .htaccess
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── UserController.php
│   └── Middlewares/
│       ├── AuthMiddleware.php
│       └── CorsMiddleware.php
├── routes/
│   ├── web.php
│   └── api.php
└── composer.json
```

### public/index.php

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use PHPRouter\Router;
use PHPRouter\Dispatcher;
use PHPRouter\MiddlewareManager;

// Registrar middlewares
MiddlewareManager::registerMultiple([
    'auth' => App\Middlewares\AuthMiddleware::class,
    'cors' => App\Middlewares\CorsMiddleware::class,
]);

// Criar router
$router = new Router();

// Carregar rotas
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Despachar
$dispatcher = new Dispatcher($router);
$dispatcher->dispatch();
```

## Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## Créditos

Desenvolvido por [Edivan B. Fontes]([https://github.com/seu-usuario](https://github.com/ebfDevelopment))

## Suporte

- **Issues**: [https://github.com/ebfDevelopment/php-router/issues)
- **Email**: ebezerradevelopment@gmail.com

---

Se este projeto te ajudou, considere dar uma estrela no GitHub!
