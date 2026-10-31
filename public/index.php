<?php
use App\Core\ErroDeNegocio;
use App\Core\RespostaJson;

require_once __DIR__ . '/../config/config.php';

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

// Erros sempre vão para o log; só aparecem na tela em desenvolvimento.
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../armazenamento/logs/php-erros.log');

// URL da pasta public, derivada de onde o index.php está publicado
// (ex.: "/sistema_ca_jesus/public" no XAMPP, "" se o DocumentRoot for a própria public).
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    http_response_code(500);
    exit('Dependências não instaladas: rode "composer install" na raiz do projeto.');
}
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Exceptions.php';
require_once __DIR__ . '/../app/helpers/AuthHelper.php';
require_once __DIR__ . '/../app/helpers/ErrorHelper.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Classes antigas, sem namespace. Sai na etapa 9, quando todas estiverem em backend/.
spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/../app/controllers/' . $className . '.php',
        __DIR__ . '/../app/models/' . $className . '.php',
        __DIR__ . '/../app/helpers/' . $className . '.php',
        __DIR__ . '/../app/middleware/' . $className . '.php',
        __DIR__ . '/../app/core/' . $className . '.php'
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$router = new Router();

$router->get('/', ['AuthController', 'loginForm']);
$router->get('/login', ['AuthController', 'loginForm']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

$authMiddleware = ['AuthMiddleware']; 

$router->get('/dashboard', ['DashboardController', 'index'], $authMiddleware);

$router->get('/produtos', ['ProdutoController', 'index'], $authMiddleware);
$router->post('/produtos/salvar', ['ProdutoController', 'salvar'], $authMiddleware);
$router->post('/produtos/atualizar/{id}', ['ProdutoController', 'atualizar'], $authMiddleware);
$router->delete('/produtos/excluir/{id}', ['ProdutoController', 'excluir'], $authMiddleware);

$router->get('/movimentacoes', ['MovimentacaoController', 'index'], $authMiddleware);
$router->post('/movimentacoes/registrar', ['MovimentacaoController', 'registrar'], $authMiddleware);

$router->get('/relatorios', ['RelatorioController', 'index'], $authMiddleware);

$adminMiddleware = ['AuthMiddleware', 'AdminMiddleware'];

$router->get('/usuarios', ['UsuarioController', 'index'], $adminMiddleware);
$router->get('/usuarios/buscar/{id}', ['UsuarioController', 'buscar'], $adminMiddleware);
$router->post('/usuarios/salvar', ['UsuarioController', 'salvar'], $adminMiddleware);
$router->post('/usuarios/toggle-status/{id}', ['UsuarioController', 'toggleStatus'], $adminMiddleware);
$router->post('/usuarios/excluir/{id}', ['UsuarioController', 'excluir'], $adminMiddleware);

$router->get('/usuarios/perfil', ['UsuarioController', 'perfil'], $authMiddleware);
$router->post('/usuarios/atualizarPerfil', ['UsuarioController', 'atualizarPerfil'], $authMiddleware);
$router->post('/usuarios/alterarSenha', ['UsuarioController', 'alterarSenha'], $authMiddleware);

$router->get('/fornecedores', ['FornecedorController', 'index'], $adminMiddleware);
$router->post('/fornecedores/salvar', ['FornecedorController', 'salvar'], $adminMiddleware);
$router->post('/fornecedores/toggle-status/{id}', ['FornecedorController', 'toggleStatus'], $adminMiddleware);
$router->post('/fornecedores/excluir/{id}', ['FornecedorController', 'excluir'], $adminMiddleware);

$router->get('/api/produtos/buscar', ['ProdutoController', 'buscar'], $authMiddleware);
$router->get('/api/relatorios/dados', ['RelatorioController', 'getDados'], $authMiddleware);
$router->get('/api/estoque/atual', ['MovimentacaoController', 'estoqueAtualJson'], $authMiddleware);

// Único ponto de tratamento de erros não capturados.
try {
    $router->resolve();
} catch (ErroDeNegocio $e) {
    // Recusa de regra de negócio: a mensagem é para o usuário e não vai para o log.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (RespostaJson::esperada()) {
        RespostaJson::enviar(false, $e->getMessage(), status: $e->status());
    }
    Session::flash('error', $e->getMessage());
    $voltar = $_SERVER['HTTP_REFERER'] ?? '';
    $mesmoSite = $voltar !== '' && parse_url($voltar, PHP_URL_HOST) === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    header('Location: ' . ($mesmoSite ? $voltar : BASE_URL . '/dashboard'));
    exit;
} catch (Throwable $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    error_log('[erro] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());

    $mensagem = 'Não foi possível processar sua solicitação. Tente novamente; se o erro continuar, avise o administrador do sistema.';

    if (RespostaJson::esperada()) {
        RespostaJson::enviar(false, $mensagem, status: 500);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Erro</title></head>';
    echo '<body style="font-family:sans-serif;max-width:720px;margin:40px auto;padding:0 16px;">';
    echo '<h2>Algo deu errado</h2><p>' . $mensagem . '</p>';
    echo '<p><a href="' . BASE_URL . '/dashboard">Voltar ao início</a></p>';
    if (ENVIRONMENT === 'development') {
        echo '<pre style="white-space:pre-wrap;background:#f5f5f5;padding:12px;font-size:12px;">'
            . htmlspecialchars(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString())
            . '</pre>';
    }
    echo '</body></html>';
}
