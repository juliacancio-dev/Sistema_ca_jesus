<?php

namespace App\Features\Autenticacao;

use App\Core\Auth;
use App\Core\Controller;
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

        if (empty($email) || empty($senha)) {
            $this->voltarAoLogin('Email e senha são obrigatórios');
        }

        try {
            $consultas = new AutenticacaoConsultas();
            $usuario = $consultas->buscarPorEmail($email);

            if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                $this->voltarAoLogin('Email ou senha incorretos');
            }
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
        $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/dashboard';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
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
