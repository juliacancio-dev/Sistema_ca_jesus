<?php
use App\Core\ConfirmacaoNecessaria;
use App\Core\ErroDeNegocio;
use App\Core\RespostaJson;
use App\Core\Router;
use App\Core\Session;
use App\Features\Autenticacao\AdminMiddleware;
use App\Features\Autenticacao\AuthController;
use App\Features\Autenticacao\AuthMiddleware;
use App\Features\Dashboard\DashboardController;
use App\Features\Fornecedores\FornecedorController;
use App\Features\Movimentacoes\MovimentacaoController;
use App\Features\Produtos\ProdutoController;
use App\Features\Relatorios\RelatorioController;
use App\Features\Usuarios\PerfilController;
use App\Features\Usuarios\UsuarioController;

if (!file_exists(__DIR__ . '/../backend/config/config.php')) {
    http_response_code(500);
    exit('Configuração não encontrada: copie backend/config/config.example.php para backend/config/config.php.');
}
require_once __DIR__ . '/../backend/config/config.php';

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

// Antes de qualquer data: o php.ini do XAMPP vem com outro fuso, e o banco usa o do sistema.
date_default_timezone_set('America/Sao_Paulo');

// Erros sempre vão para o log; só aparecem na tela em desenvolvimento.
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../backend/storage/logs/php-erros.log');

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

$router = new Router();

$router->get('/', [AuthController::class, 'loginForm']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$authMiddleware = [AuthMiddleware::class]; 

$router->get('/dashboard', [DashboardController::class, 'index'], $authMiddleware);

$router->get('/produtos', [ProdutoController::class, 'index'], $authMiddleware);
$router->post('/produtos/salvar', [ProdutoController::class, 'salvar'], $authMiddleware);
$router->post('/produtos/atualizar/{id}', [ProdutoController::class, 'atualizar'], $authMiddleware);
$router->delete('/produtos/excluir/{id}', [ProdutoController::class, 'excluir'], $authMiddleware);

$router->get('/movimentacoes', [MovimentacaoController::class, 'index'], $authMiddleware);
$router->post('/movimentacoes/registrar', [MovimentacaoController::class, 'registrar'], $authMiddleware);

$router->get('/relatorios', [RelatorioController::class, 'index'], $authMiddleware);

$adminMiddleware = [AuthMiddleware::class, AdminMiddleware::class];

$router->get('/usuarios', [UsuarioController::class, 'index'], $adminMiddleware);
$router->get('/usuarios/buscar/{id}', [UsuarioController::class, 'buscar'], $adminMiddleware);
$router->post('/usuarios/salvar', [UsuarioController::class, 'salvar'], $adminMiddleware);
$router->post('/usuarios/toggle-status/{id}', [UsuarioController::class, 'toggleStatus'], $adminMiddleware);
$router->post('/usuarios/excluir/{id}', [UsuarioController::class, 'excluir'], $adminMiddleware);

$router->get('/usuarios/perfil', [PerfilController::class, 'perfil'], $authMiddleware);
$router->post('/usuarios/atualizarDados', [PerfilController::class, 'atualizarDados'], $authMiddleware);
$router->post('/usuarios/atualizarEndereco', [PerfilController::class, 'atualizarEndereco'], $authMiddleware);
$router->post('/usuarios/alterarSenha', [PerfilController::class, 'alterarSenha'], $authMiddleware);

$router->get('/fornecedores', [FornecedorController::class, 'index'], $adminMiddleware);
$router->post('/fornecedores/salvar', [FornecedorController::class, 'salvar'], $adminMiddleware);
$router->post('/fornecedores/toggle-status/{id}', [FornecedorController::class, 'toggleStatus'], $adminMiddleware);
$router->post('/fornecedores/excluir/{id}', [FornecedorController::class, 'excluir'], $adminMiddleware);

$router->get('/api/produtos/buscar', [ProdutoController::class, 'buscar'], $authMiddleware);
$router->get('/api/relatorios/dados', [RelatorioController::class, 'getDados'], $authMiddleware);
$router->get('/api/estoque/atual', [MovimentacaoController::class, 'estoqueAtualJson'], $authMiddleware);

// Único ponto de tratamento de erros não capturados.
try {
    $router->resolve();
} catch (ErroDeNegocio $e) {
    // Recusa de regra de negócio: a mensagem é para o usuário e não vai para o log.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (RespostaJson::esperada()) {
        RespostaJson::enviar(false, $e->getMessage(), status: $e->status(), requerConfirmacao: $e instanceof ConfirmacaoNecessaria);
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
