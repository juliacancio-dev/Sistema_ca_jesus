<?php
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

require_once __DIR__ . '/../config/config.php';

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

error_log("[index.php] __FILE__: " . __FILE__);
error_log("[index.php] __DIR__: " . __DIR__);
error_log("[index.php] SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
error_log("[index.php] REQUEST_URI: " . $_SERVER['REQUEST_URI']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Exceptions.php';
require_once __DIR__ . '/../app/helpers/AuthHelper.php';
require_once __DIR__ . '/../app/helpers/ErrorHelper.php';
require_once __DIR__ . '/../app/helpers/functions.php';

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
  
    error_log("[index.php] Classe não encontrada: $className");
    error_log("[index.php] Caminhos verificados: " . json_encode($paths));
});

if (class_exists('Router')) {
    error_log('[index.php] Router carregado, tamo junto!');
} else {
    error_log('[index.php] ERRO: Router não carregou, deu ruim!');
    http_response_code(500);
    exit('Erro crítico: Router não carregada.');
}

$router = new Router();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_log("[index.php] SESSION: " . json_encode($_SESSION));
error_log("[index.php] is_logged_in: " . (AuthHelper::isLoggedIn() ? 'true' : 'false'));

$router->get('/', ['AuthController', 'loginForm']);
$router->get('/login', ['AuthController', 'loginForm']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

$authMiddleware = ['AuthMiddleware']; 

$router->get('/dashboard', ['DashboardController', 'index'], $authMiddleware);

$router->get('/produtos', ['ProdutoController', 'index'], $authMiddleware);
$router->get('/produtos/novo', ['ProdutoController', 'novo'], $authMiddleware);
$router->get('/produtos/editar/{id}', ['ProdutoController', 'editar'], $authMiddleware);
$router->post('/produtos/salvar', ['ProdutoController', 'salvar'], $authMiddleware);
$router->post('/produtos/atualizar/{id}', ['ProdutoController', 'atualizar'], $authMiddleware);
$router->delete('/produtos/excluir/{id}', ['ProdutoController', 'excluir'], $authMiddleware);
$router->post('/produtos/excluir/{id}', ['ProdutoController', 'excluir'], $authMiddleware);
$router->get('/produtos/listar', ['ProdutoController', 'listar'], $authMiddleware);

$router->get('/movimentacoes', ['MovimentacaoController', 'index'], $authMiddleware);
$router->post('/movimentacoes/registrar', ['MovimentacaoController', 'registrar'], $authMiddleware);

$router->get('/relatorios', ['RelatorioController', 'index'], $authMiddleware);
$router->post('/relatorios/gerar', ['RelatorioController', 'gerar'], $authMiddleware);

$adminMiddleware = ['AuthMiddleware', 'AdminMiddleware'];

$router->get('/usuarios', ['UsuarioController', 'index'], $adminMiddleware);
$router->get('/usuarios/novo', ['UsuarioController', 'novo'], $adminMiddleware);
$router->get('/usuarios/editar/{id}', ['UsuarioController', 'editar'], $adminMiddleware);
$router->get('/usuarios/buscar/{id}', ['UsuarioController', 'buscar'], $adminMiddleware);
$router->post('/usuarios/salvar', ['UsuarioController', 'salvar'], $adminMiddleware);
$router->post('/usuarios/store', ['UsuarioController', 'salvar'], $adminMiddleware);
$router->post('/usuarios/update/{id}', ['UsuarioController', 'salvar'], $adminMiddleware);
$router->post('/usuarios/atualizar/{id}', ['UsuarioController', 'salvar'], $adminMiddleware);
$router->post('/usuarios/toggle-status/{id}', ['UsuarioController', 'toggleStatus'], $adminMiddleware);
$router->post('/usuarios/excluir/{id}', ['UsuarioController', 'excluir'], $adminMiddleware);
$router->delete('/usuarios/excluir/{id}', ['UsuarioController', 'excluir'], $adminMiddleware);

$router->get('/usuarios/perfil', ['UsuarioController', 'perfil'], $authMiddleware);
$router->post('/usuarios/atualizarPerfil', ['UsuarioController', 'atualizarPerfil'], $authMiddleware);
$router->post('/usuarios/alterarSenha', ['UsuarioController', 'alterarSenha'], $authMiddleware);

$router->get('/fornecedores', ['FornecedorController', 'index'], $adminMiddleware);
$router->get('/fornecedores/novo', ['FornecedorController', 'novo'], $adminMiddleware);
$router->get('/fornecedores/editar/{id}', ['FornecedorController', 'editar'], $adminMiddleware);
$router->post('/fornecedores/salvar', ['FornecedorController', 'salvar'], $adminMiddleware);
$router->post('/fornecedores/store', ['FornecedorController', 'salvar'], $adminMiddleware);
$router->post('/fornecedores/update/{id}', ['FornecedorController', 'salvar'], $adminMiddleware);
$router->post('/fornecedores/atualizar/{id}', ['FornecedorController', 'salvar'], $adminMiddleware);
$router->post('/fornecedores/toggle-status/{id}', ['FornecedorController', 'toggleStatus'], $adminMiddleware);
$router->post('/fornecedores/excluir/{id}', ['FornecedorController', 'excluir'], $adminMiddleware);
$router->delete('/fornecedores/excluir/{id}', ['FornecedorController', 'excluir'], $adminMiddleware);

$router->get('/api/dashboard/estatisticas', ['DashboardController', 'getEstatisticas'], $authMiddleware);
$router->get('/api/produtos/buscar', ['ProdutoController', 'buscar'], $authMiddleware);
$router->get('/api/relatorios/dados', ['RelatorioController', 'getDados'], $authMiddleware);
$router->get('/api/fornecedores/listar', ['FornecedorController', 'listar'], $adminMiddleware);
$router->get('/api/usuarios/listar', ['UsuarioController', 'usuariosJson'], $adminMiddleware);
$router->get('/api/movimentacoes/listar', ['MovimentacaoController', 'movimentacoesJson'], $authMiddleware);
$router->get('/api/estoque/atual', ['MovimentacaoController', 'estoqueAtualJson'], $authMiddleware);

try {
    $router->resolve();
} catch (Exception $e) {
    http_response_code(500);
    error_log("[index.php] Deu ruim: " . $e->getMessage());
    error_log("[index.php] Trace: " . $e->getTraceAsString());
    
    echo '<div style="background-color: #ffebee; color: #b71c1c; padding: 15px; margin: 15px; border-radius: 5px;">';
    echo '<h2>Ops! Algo deu errado</h2>';
    echo '<p>Tivemos um problema ao processar sua solicitação. Tenta de novo mais tarde?</p>';
    echo '<p>Se continuar dando erro, fala com o administrador do sistema.</p>';
    echo '</div>';
}
