# PHP Router - Sistema de Roteamento Completo

Sistema de roteamento PHP moderno, poderoso e flexível com suporte a middlewares, namespace dinâmico, proteção de rotas e muito mais.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## 🚀 Características

- ✅ Suporte a todos os métodos HTTP (GET, POST, PUT, DELETE, PATCH)
- ✅ Parâmetros dinâmicos e opcionais nas rotas
- ✅ Grupos de rotas com prefixos e namespaces
- ✅ **Sistema completo de middlewares** (Auth, Admin, CRON, CORS, etc)
- ✅ **Namespace dinâmico para controllers**
- ✅ Rotas nomeadas
- ✅ Suporte a closures e controllers
- ✅ Separador customizável (::, @, :, etc)
- ✅ Grupos aninhados ilimitados
- ✅ **Suporte a subdiretórios** (aplicações em /subpasta)
- ✅ **Proteção de rotas CRON** com tokens configuráveis
- ✅ Zero dependências externas

## 📦 Instalação
```bash
composer require edifonttes/php-router
```

## 🎯 Início Rápido

### Configuração Básica
```php
<?php

require 'vendor/autoload.php';

use PHPRouter\Router;
use PHPRouter\Dispatcher;

$router = new Router();

// Define namespace padrão
$router->namespace('App\Controllers');

// Rota simples
$router->get('/', 'HomeController::index');

// Rota com parâmetro
$router->get('/users/{id}', 'UserController::show');

// Despacha a requisição
$dispatcher = new Dispatcher($router);
$dispatcher->dispatch();
```

### .htaccess (Apache)
```apache
RewriteEngine On
RewriteBase /

# Redireciona tudo para index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

Para aplicações em subdiretórios (ex: `/meuapp`):
```apache
RewriteEngine On
RewriteBase /meuapp/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

## 📚 Documentação Completa

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

// Parâmetro opcional
$router->get('/blog/{page?}', function($page = 1) {
    echo "Page: {$page}";
});
```

### 🎨 Namespace para Controllers

**Problema resolvido:** Não precisa mais escrever o namespace completo em cada rota!
```php
// Define namespace base
$router->namespace('App\Controllers');

// Agora basta usar o nome da classe
$router->get('/', 'HomeController::index');
// Busca: App\Controllers\HomeController

$router->get('/about', 'HomeController::about');
// Busca: App\Controllers\HomeController
```

**Mudando namespace ao longo do arquivo:**
```php
// Rotas públicas
$router->namespace('App\Controllers\Public');
$router->get('/blog', 'BlogController::index');
// Busca: App\Controllers\Public\BlogController

// Rotas admin
$router->namespace('App\Controllers\Admin');
$router->get('/admin', 'DashboardController::index');
// Busca: App\Controllers\Admin\DashboardController
```

**Namespace em grupos:**
```php
$router->namespace('App\Controllers');

$router->group(['prefix' => 'admin', 'namespace' => 'Admin'], function($router) {
    $router->get('/', 'DashboardController::index');
    // Busca: App\Controllers\Admin\DashboardController
    
    $router->get('/users', 'UsersController::index');
    // Busca: App\Controllers\Admin\UsersController
});
```

**Grupos aninhados:**
```php
$router->namespace('App\Controllers');

$router->group(['prefix' => 'api', 'namespace' => 'Api'], function($router) {
    
    $router->group(['prefix' => 'v1', 'namespace' => 'V1'], function($router) {
        $router->get('/users', 'UserController::index');
        // Busca: App\Controllers\Api\V1\UserController
    });
    
    $router->group(['prefix' => 'v2', 'namespace' => 'V2'], function($router) {
        $router->get('/users', 'UserController::index');
        // Busca: App\Controllers\Api\V2\UserController
    });
});
```

**Ignorar namespace (usar FQCN):**
```php
$router->namespace('App\Controllers');

// Use \ no início para namespace absoluto
$router->get('/special', '\My\Custom\Controller::index');
// Busca EXATAMENTE: My\Custom\Controller

// Ou use namespace completo
$router->get('/other', 'Other\Package\Controller::index');
// Busca: Other\Package\Controller (detecta \ e ignora namespace definido)
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

// String com separador padrão (::)
$router->get('/users', 'UserController::index');

// Separador customizado
$router->setSeparator('@');
$router->get('/users', 'UserController@index');

$router->setSeparator(':');
$router->get('/users', 'UserController:index');
```

### Grupos de Rotas
```php
// Grupo com prefixo
$router->group(['prefix' => 'admin'], function($router) {
    // GET /admin/dashboard
    $router->get('/dashboard', 'AdminController::dashboard');
});

// Grupo com middleware
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/dashboard', 'DashboardController::index');
});

// Grupo com prefixo, namespace e middleware
$router->group([
    'prefix' => 'admin',
    'namespace' => 'Admin',
    'middleware' => ['auth', 'admin']
], function($router) {
    $router->get('/', 'DashboardController::index');
    // Rota: /admin
    // Controller: App\Controllers\Admin\DashboardController
    // Middlewares: auth, admin
});
```

## 🛡️ Sistema de Middlewares

### Middlewares Incluídos

O router já vem com middlewares prontos para uso:

1. **AuthMiddleware** - Protege rotas que requerem autenticação
2. **AdminMiddleware** - Garante que apenas admins acessem
3. **GuestMiddleware** - Permite acesso apenas para NÃO autenticados
4. **CronMiddleware** - Protege rotas de CRON Jobs com tokens
5. **RefererMiddleware** - Valida origem da requisição
6. **CorsMiddleware** - Configura CORS para APIs

### Registrando Middlewares
```php
use PHPRouter\MiddlewareManager;
use PHPRouter\Middlewares\AuthMiddleware;
use PHPRouter\Middlewares\AdminMiddleware;
use PHPRouter\Middlewares\CronMiddleware;

// Registrar individualmente
MiddlewareManager::register('auth', AuthMiddleware::class);
MiddlewareManager::register('admin', AdminMiddleware::class);

// Ou múltiplos de uma vez
MiddlewareManager::registerMultiple([
    'auth' => AuthMiddleware::class,
    'admin' => AdminMiddleware::class,
    'cron' => CronMiddleware::class,
]);
```

### Usando Middlewares

**Em rotas individuais:**
```php
$router->get('/dashboard', 'DashboardController::index')->middleware('auth');

$router->get('/admin', 'AdminController::index')->middleware(['auth', 'admin']);
```

**Em grupos:**
```php
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/dashboard', 'DashboardController::index');
    $router->get('/profile', 'ProfileController::show');
});

// Múltiplos middlewares
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    $router->get('/admin', 'AdminController::index');
});
```

### AuthMiddleware - Autenticação

Protege rotas que requerem usuário logado.
```php
MiddlewareManager::register('auth', AuthMiddleware::class);

$router->get('/dashboard', 'DashboardController::index')->middleware('auth');
```

**Funcionamento:**
- Verifica se `$_SESSION['user_authenticated']` é `true`
- Redireciona para `/login` se não autenticado
- Retorna JSON para requisições AJAX

**Para fazer login:**
```php
session_start();
$_SESSION['user_authenticated'] = true;
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $userName;
$_SESSION['user_role'] = 'user'; // ou 'admin'
```

### AdminMiddleware - Apenas Administradores

Garante que apenas usuários com role de admin acessem.
```php
$router->group(['middleware' => ['auth', 'admin']], function($router) {
    $router->get('/admin', 'AdminController::index');
});
```

**Funcionamento:**
- Verifica se `$_SESSION['user_role']` é `'admin'`
- Aborta com erro 403 se não for admin

### GuestMiddleware - Apenas Não Autenticados

Útil para páginas de login/registro (usuários já logados são redirecionados).
```php
$router->group(['middleware' => 'guest'], function($router) {
    $router->get('/login', 'AuthController::loginForm');
    $router->get('/register', 'AuthController::registerForm');
});
```

**Funcionamento:**
- Redireciona para `/dashboard` se já autenticado
- Permite acesso se NÃO autenticado

### 🔐 CronMiddleware - Proteção de Tarefas Agendadas

Protege rotas de CRON Jobs com autenticação por token.

#### Configuração Básica

**1. Configure o token no `.env`:**
```bash
CRON_TOKEN=b3a5514697975e4daa19757391df83ce //Atenção//, não use este token, ele está aqui apenas como exemplo. Gere o seu token digitando, php -r "echo bin2hex(random_bytes(16));" no CMD ou no Power Shell.
```

**2. Registre e use:**
```php
MiddlewareManager::register('cron', CronMiddleware::class);

$router->group(['prefix' => 'cron', 'middleware' => 'cron'], function($router) {
    $router->get('/daily-cleanup', 'CronController::dailyCleanup');
    $router->get('/send-emails', 'CronController::sendEmails');
});
```

**3. Teste:**
```bash
# Via Header
curl -H "X-Cron-Auth: b3a5514697975e4daa19757391df83ce" http://localhost/cron/daily-cleanup

# Via Bearer Token
curl -H "Authorization: Bearer b3a5514697975e4daa19757391df83ce" http://localhost/cron/daily-cleanup

# Via Query String
curl "http://localhost/cron/daily-cleanup?cron_token=b3a5514697975e4daa19757391df83ce"
```

#### Configurações Avançadas

**Middleware que aceita APENAS header:**
```php
$cronHeader = (new CronMiddleware())
    ->setEnvVar('CRON_TOKEN')
    ->setHeaderName('X-Cron-Auth')
    ->setAuthType('header');

MiddlewareManager::register('cron-header', get_class($cronHeader));

$router->get('/cron/backup', 'CronController::backup')->middleware('cron-header');
```

**Middleware que aceita APENAS Bearer:**
```php
$cronBearer = (new CronMiddleware())
    ->setEnvVar('BACKUP_TOKEN')
    ->setAuthType('bearer');

$router->get('/backup/full', 'BackupController::full')->middleware([$cronBearer]);
```

**Middleware que aceita APENAS Query String:**
```php
$cronQuery = (new CronMiddleware())
    ->setEnvVar('WEBHOOK_TOKEN')
    ->setAuthType('query')
    ->setQueryParam('token');

$router->post('/webhook/payment', 'WebhookController::handle')->middleware([$cronQuery]);
```

**Tokens diferentes para tarefas diferentes:**
```bash
# .env
CRON_TOKEN=b3a5514697975e4daa19757391df83ce
BACKUP_TOKEN=abc123def456
WEBHOOK_TOKEN=xyz789uvw456
```
```php
// Tarefa diária
$cronDaily = (new CronMiddleware())->setEnvVar('CRON_TOKEN');
$router->get('/cron/daily', 'CronController::daily')->middleware([$cronDaily]);

// Backup
$cronBackup = (new CronMiddleware())->setEnvVar('BACKUP_TOKEN');
$router->get('/cron/backup', 'CronController::backup')->middleware([$cronBackup]);

// Webhook
$cronWebhook = (new CronMiddleware())->setEnvVar('WEBHOOK_TOKEN');
$router->post('/webhook/payment', 'WebhookController::payment')->middleware([$cronWebhook]);
```

#### Tipos de Autenticação

| Tipo | Descrição | Uso |
|------|-----------|-----|
| `'any'` | Aceita header, bearer OU query (padrão) | Máxima flexibilidade |
| `'header'` | Apenas header customizado | Servidores que suportam headers |
| `'bearer'` | Apenas Authorization Bearer | APIs modernas |
| `'query'` | Apenas query string | Servidores limitados |

#### Métodos de Configuração
```php
$middleware = (new CronMiddleware())
    ->setToken('abc123')              // Define token manualmente
    ->setEnvVar('MY_TOKEN')            // Lê do .env
    ->setHeaderName('X-My-Auth')       // Nome do header
    ->setAuthType('header')            // Tipo de auth
    ->setQueryParam('api_key')         // Nome do parâmetro query
    ->setAllowCli(true);               // Permite via terminal
```

#### Configurando no Crontab
```bash
# Opção 1: Header (recomendado)
0 2 * * * curl -H "X-Cron-Auth: TOKEN" https://seusite.com/cron/daily

# Opção 2: Bearer
0 2 * * * curl -H "Authorization: Bearer TOKEN" https://seusite.com/cron/daily

# Opção 3: Query String (se servidor não suportar headers)
0 2 * * * curl "https://seusite.com/cron/daily?cron_token=TOKEN"
```

### CorsMiddleware - APIs com CORS
```php
MiddlewareManager::register('cors', CorsMiddleware::class);

$router->group(['prefix' => 'api', 'middleware' => 'cors'], function($router) {
    $router->get('/users', 'ApiController::users');
});
```

**Customizando:**
```php
$cors = (new CorsMiddleware())
    ->setAllowedOrigins(['https://meuapp.com', 'https://app.example.com'])
    ->setAllowedMethods(['GET', 'POST', 'PUT', 'DELETE'])
    ->setAllowCredentials(true);
```

### RefererMiddleware - Validação de Origem
```php
$referer = new RefererMiddleware(['meusite.com', 'api.meusite.com']);

$router->post('/webhook', 'WebhookController::handle')->middleware([$referer]);
```

### Criando Middleware Customizado
```php
<?php

namespace App\Middlewares;

use PHPRouter\Middleware;

class RateLimitMiddleware extends Middleware
{
    public function handle(callable $next)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Sua lógica aqui
        if ($this->exceedsLimit($ip)) {
            $this->json(['error' => 'Rate limit exceeded'], 429);
        }
        
        return $next();
    }
    
    private function exceedsLimit($ip) {
        // Implementar lógica de rate limiting
        return false;
    }
}
```

## 📝 Rotas Nomeadas
```php
// Definir rota nomeada
$router->get('/users/{id}', 'UserController::show')->name('users.show');

// Gerar URL
$url = $router->route('users.show', ['id' => 123]);
// Resultado: /users/123
```

## 🎛️ Handlers Customizados
```php
$dispatcher = new Dispatcher($router);

// Handler 404 customizado
$dispatcher->setNotFoundHandler(function() {
    http_response_code(404);
    echo "<h1>404 - Página não encontrada</h1>";
});

// Handler de erro customizado
$dispatcher->setErrorHandler(function($e) {
    http_response_code(500);
    echo "<h1>Erro: " . htmlspecialchars($e->getMessage()) . "</h1>";
});

$dispatcher->dispatch();
```

## 💡 Exemplos Práticos

### Landing Page + Painel Admin
```php
$router->namespace('App\Controllers');

// Landing page (público)
$router->get('/', 'HomeController::index');
$router->get('/about', 'HomeController::about');
$router->get('/pricing', 'HomeController::pricing');

// Login/Registro (apenas não logados)
$router->group(['middleware' => 'guest', 'namespace' => 'Auth'], function($router) {
    $router->get('/login', 'AuthController::loginForm');
    $router->post('/login', 'AuthController::login');
    $router->get('/register', 'AuthController::registerForm');
});

// Dashboard (autenticado)
$router->group([
    'prefix' => 'dashboard',
    'middleware' => 'auth',
    'namespace' => 'User'
], function($router) {
    $router->get('/', 'DashboardController::index');
    $router->get('/profile', 'ProfileController::show');
});

// Admin (autenticado + admin)
$router->group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'admin'],
    'namespace' => 'Admin'
], function($router) {
    $router->get('/', 'AdminController::index');
    $router->get('/users', 'AdminController::users');
});
```

### API REST Completa
```php
$router->namespace('App\Controllers');

$router->group([
    'prefix' => 'api/v1',
    'middleware' => 'cors',
    'namespace' => 'Api\V1'
], function($router) {
    
    // Rotas públicas
    $router->post('/login', 'AuthController::login');
    $router->get('/products', 'ProductController::index');
    
    // Rotas protegidas
    $router->group(['middleware' => 'auth'], function($router) {
        $router->get('/user', 'UserController::current');
        $router->post('/orders', 'OrderController::create');
        $router->get('/orders', 'OrderController::myOrders');
    });
});
```

### Sistema com CRON Jobs
```php
$router->namespace('App\Controllers');

// Rotas web normais
$router->get('/', 'HomeController::index');

// CRON Jobs protegidos
MiddlewareManager::register('cron', CronMiddleware::class);

$router->group([
    'prefix' => 'cron',
    'middleware' => 'cron',
    'namespace' => 'Cron'
], function($router) {
    $router->get('/daily-cleanup', 'CronController::dailyCleanup');
    $router->get('/send-emails', 'CronController::sendEmails');
    $router->get('/backup', 'CronController::backup');
});
```

## ⚙️ Configuração do .env
```bash
# ============================================
# APLICAÇÃO
# ============================================
APP_NAME="Minha Aplicação"
APP_ENV=development  # development, production
APP_DEBUG=true

# ============================================
# TOKENS DE SEGURANÇA
# ============================================
# Token para CRON Jobs (gere com: openssl rand -hex 32)
CRON_TOKEN=b3a5514697975e4daa19757391df83ce

# Tokens específicos
BACKUP_TOKEN=abc123def456
WEBHOOK_TOKEN=xyz789uvw456

# ============================================
# CONFIGURAÇÕES DE SESSÃO
# ============================================
SESSION_LIFETIME=120
AUTH_SESSION_KEY=user_authenticated
```

## 📁 Estrutura Recomendada
```
seu-projeto/
├── public/
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   └── js/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   ├── User/
│   │   │   └── DashboardController.php
│   │   ├── Admin/
│   │   │   └── AdminController.php
│   │   └── Api/
│   │       └── V1/
│   │           └── UserController.php
│   └── Middlewares/
│       └── CustomMiddleware.php
├── vendor/
├── .env
└── composer.json
```

### public/index.php
```php
<?php
ob_start();

require __DIR__ . '/../vendor/autoload.php';

use PHPRouter\Router;
use PHPRouter\Dispatcher;
use PHPRouter\MiddlewareManager;
use PHPRouter\Middlewares\AuthMiddleware;
use PHPRouter\Middlewares\AdminMiddleware;
use PHPRouter\Middlewares\CronMiddleware;

// Registrar middlewares
MiddlewareManager::registerMultiple([
    'auth' => AuthMiddleware::class,
    'admin' => AdminMiddleware::class,
    'cron' => CronMiddleware::class,
]);

// Criar router
$router = new Router();
$router->setSeparator(':');
$router->namespace('App\Controllers');

// Definir rotas
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

// Despachar
$dispatcher = new Dispatcher($router);
$dispatcher->setNotFoundHandler(function() {
    http_response_code(404);
    require __DIR__ . '/../views/errors/404.php';
});
$dispatcher->dispatch();

ob_end_flush();
```

## 🔍 Métodos Úteis
```php
// Listar todas as rotas (debug)
$routes = $router->listRoutes();
print_r($routes);

// Obter namespace atual
$namespace = $router->getNamespace();

// Obter separador atual
$separator = $router->getSeparator();

// Verificar middlewares registrados
$aliases = MiddlewareManager::getAliases();
$globals = MiddlewareManager::getGlobals();
```

## 🚨 Troubleshooting

### Erro 404 em todas as rotas
- Verifique se o `.htaccess` está correto
- Verifique se `mod_rewrite` está habilitado no Apache
- Para subdiretórios, ajuste o `RewriteBase`

### Middleware não está funcionando
- Certifique-se de registrar ANTES de usar
- Verifique se aplicou na rota ou grupo
- Verifique a ordem dos middlewares

### Sessão não persiste
- Verifique configurações de sessão no `php.ini`
- Certifique-se que `session_start()` é chamado
- Em desenvolvimento, verifique domínio/path dos cookies

### CRON retorna 403
- Verifique se o token no `.env` está correto
- Teste manualmente com CURL
- Verifique se o tipo de auth está correto (`header`, `bearer`, `query`)

### Namespace não funciona
- Certifique-se que o autoload do Composer está correto
- Rode `composer dump-autoload`
- Verifique se o namespace da classe corresponde ao configurado

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 👨‍💻 Créditos

Desenvolvido por [Edivan B. Fontes](https://github.com/ebfDevelopment)

## 💬 Suporte

- **Issues**: [https://github.com/ebfDevelopment/php-router/issues](https://github.com/ebfDevelopment/php-router/issues)
- **Email**: ebezerradevelopment@gmail.com

---

⭐ Se este projeto te ajudou, considere dar uma estrela no GitHub!