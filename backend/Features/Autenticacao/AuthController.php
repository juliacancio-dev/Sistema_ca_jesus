<?php

namespace App\Features\Autenticacao;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use Exception;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Auth::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        $this->render('autenticacao/login', [], false);
    }

    public function login()
    {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if (!is_string($email) || !is_string($senha) || $email === '' || $senha === '') {
            $this->voltarAoLogin('Email e senha são obrigatórios');
        }

        try {
            $conexao = Database::getInstance()->getConnection();
            $limite = new LimiteDeLogin($conexao);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            $espera = $limite->consumir($email, $ip);
            if ($espera > 0) {
                $minutos = (int)ceil($espera / 60);
                $this->voltarAoLogin("Muitas tentativas. Tente novamente em $minutos " . ($minutos === 1 ? 'minuto.' : 'minutos.'));
            }

            $consultas = new AutenticacaoConsultas();
            $usuario = $consultas->buscarPorEmail($email);

            // Verifica mesmo sem conta, para o tempo de resposta não revelar quais e-mails existem.
            $senhaConfere = password_verify($senha, $usuario['senha'] ?? LimiteDeLogin::HASH_FICTICIO);
            if (!$usuario || !$senhaConfere) {
                $this->voltarAoLogin('Email ou senha incorretos');
            }
            $limite->registrarSucesso($email, $ip);

            // Só depois da senha: quem não a conhece não descobre que a conta existe.
            if ((int)$usuario['ativo'] !== 1) {
                $this->voltarAoLogin('Seu usuário está inativo. Contate o administrador.');
            }

            $hashDaSenha = $usuario['senha'];
            unset($usuario['senha']);
            $consultas->registrarLogin($usuario['id']);
        } catch (Exception $e) {
            error_log('[autenticacao] ' . $e);
            $this->voltarAoLogin('Erro interno do sistema. Tente novamente.');
        }

        Auth::login($usuario, $hashDaSenha);
        $destino = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . (Auth::destinoLocal($destino) ? $destino : BASE_URL . '/dashboard'));
        exit;
    }

    public function logout()
    {
        Auth::logout();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    private function voltarAoLogin(string $mensagem): never
    {
        Session::flash('error', $mensagem);
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
