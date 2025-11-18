<?php

/**
 * Exemplo de uso do Simple Router
 */

require __DIR__ . '/../vendor/autoload.php';

use SimpleRouter\Router;
use SimpleRouter\Dispatcher;
use SimpleRouter\MiddlewareManager;

// ============================================
// Criar e configurar o router
// ============================================
$router = new Router();

// Opcional: mudar o separador
// $router->setSeparator('@');


// ============================================
// Registrar middlewares (opcional)
// ============================================
// MiddlewareManager::register('auth', AuthMiddleware::class);
// MiddlewareManager::register('admin', AdminMiddleware::class);


// ============================================
// ROTAS SIMPLES
// ============================================

// Rota básica com closure
$router->get('/', function() {
    echo "<h1>Simple Router - Home</h1>";
    echo "<p>Sistema de roteamento funcionando!</p>";
    echo "<ul>";
    echo "<li><a href='/about'>Sobre</a></li>";
    echo "<li><a href='/users/123'>Ver Usuário 123</a></li>";
    echo "<li><a href='/posts/tecnologia/456'>Post de Tecnologia</a></li>";
    echo "</ul>";
});

// Rota simples
$router->get('/about', function() {
    echo "<h1>Sobre</h1>";
    echo "<p>Este é um exemplo do Simple Router</p>";
    echo "<a href='/'>← Voltar</a>";
});

// Rota com parâmetro
$router->get('/users/{id}', function($id) {
    echo "<h1>Usuário #{$id}</h1>";
    echo "<p>Exibindo detalhes do usuário {$id}</p>";
    echo "<a href='/'>← Voltar</a>";
});

// Rota com múltiplos parâmetros
$router->get('/posts/{category}/{id}', function($category, $id) {
    echo "<h1>Post #{$id}</h1>";
    echo "<p>Categoria: {$category}</p>";
    echo "<a href='/'>← Voltar</a>";
});

// Rota com parâmetro opcional
$router->get('/blog/{page?}', function($page = 1) {
    echo "<h1>Blog - Página {$page}</h1>";
    echo "<p>Exibindo página {$page} do blog</p>";
    echo "<a href='/'>← Voltar</a>";
});


// ============================================
// ROTAS COM CONTROLLERS (descomente para usar)
// ============================================
/*
$router->get('/users', 'UserController::index');
$router->get('/users/{id}', 'UserController::show');
$router->post('/users', 'UserController::store');
$router->put('/users/{id}', 'UserController::update');
$router->delete('/users/{id}', 'UserController::destroy');
*/


// ============================================
// GRUPOS DE ROTAS
// ============================================

// Grupo com prefixo
$router->group(['prefix' => 'api'], function($router) {
    
    // GET /api/users
    $router->get('/users', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'João'],
                ['id' => 2, 'name' => 'Maria']
            ]
        ]);
    });
    
    // GET /api/posts
    $router->get('/posts', function() {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                ['id' => 1, 'title' => 'Post 1'],
                ['id' => 2, 'title' => 'Post 2']
            ]
        ]);
    });
});

// Grupos aninhados
$router->group(['prefix' => 'admin'], function($router) {
    
    // GET /admin/dashboard
    $router->get('/dashboard', function() {
        echo "<h1>Admin Dashboard</h1>";
        echo "<p>Painel administrativo</p>";
        echo "<a href='/'>← Voltar</a>";
    });
    
    $router->group(['prefix' => 'users'], function($router) {
        // GET /admin/users
        $router->get('/', function() {
            echo "<h1>Admin - Usuários</h1>";
            echo "<p>Lista de usuários do sistema</p>";
            echo "<a href='/admin/dashboard'>← Dashboard</a>";
        });
        
        // GET /admin/users/123
        $router->get('/{id}', function($id) {
            echo "<h1>Admin - Usuário #{$id}</h1>";
            echo "<p>Editando usuário {$id}</p>";
            echo "<a href='/admin/users'>← Lista de Usuários</a>";
        });
    });
});


// ============================================
// GRUPOS COM MIDDLEWARE (exemplo comentado)
// ============================================
/*
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'admin']], function($router) {
    $router->get('/dashboard', 'AdminController::dashboard');
    $router->get('/users', 'AdminController::users');
});
*/


// ============================================
// MÚLTIPLOS MÉTODOS
// ============================================

// Aceita GET e POST
$router->match(['GET', 'POST'], '/contact', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<h1>Contato Enviado!</h1>";
        echo "<p>Obrigado pela mensagem</p>";
    } else {
        echo "<h1>Formulário de Contato</h1>";
        echo "<form method='POST'>";
        echo "<input type='text' name='name' placeholder='Nome'><br><br>";
        echo "<textarea name='message' placeholder='Mensagem'></textarea><br><br>";
        echo "<button type='submit'>Enviar</button>";
        echo "</form>";
    }
    echo "<br><a href='/'>← Voltar</a>";
});


// ============================================
// ROTAS NOMEADAS
// ============================================

$router->get('/profile/{id}', function($id) {
    echo "<h1>Perfil do Usuário #{$id}</h1>";
})->name('profile.show');

// Para gerar URL:
// $url = $router->route('profile.show', ['id' => 123]);


// ============================================
// DESPACHAR REQUISIÇÃO
// ============================================

$dispatcher = new Dispatcher($router);

// Handlers customizados (opcional)
$dispatcher->setNotFoundHandler(function() {
    echo "<h1>404 - Página não encontrada</h1>";
    echo "<p>A página que você procura não existe.</p>";
    echo "<a href='/'>← Voltar para Home</a>";
});

$dispatcher->setErrorHandler(function($e) {
    echo "<h1>Erro no Servidor</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
});

// Despacha a requisição
$dispatcher->dispatch();