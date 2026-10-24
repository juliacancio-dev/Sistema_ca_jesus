<?php

require_once __DIR__ . '/../core/Exceptions.php';

class AuthController extends Controller
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    public function loginForm()
    {
        if (AuthHelper::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        $this->renderView('layouts/auth/login');
    }

    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }

            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                $_SESSION['error'] = 'Email e senha são obrigatórios';
                header('Location: ' . BASE_URL . '/login');
                exit;
            }

            error_log("[AuthController] Tentativa de login para: $email");

            try {
                $uDados = $this->usuarioModel->buscarPorEmail($email);
                if ($uDados && isset($uDados['ativo']) && (int)$uDados['ativo'] === 0) {
                    $_SESSION['error'] = 'Seu usuário está inativo. Contate o administrador.';
                    header('Location: ' . BASE_URL . '/login');
                    exit;
                }
            } catch (Throwable $e) {
            }

            $usuario = $this->usuarioModel->autenticar($email, $senha);

            if ($usuario) {
                AuthHelper::login($usuario);
                $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/dashboard';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            }

            error_log("[AuthController] Falha de login para: $email");
            $_SESSION['error'] = 'Email ou senha incorretos';
            header('Location: ' . BASE_URL . '/login');
            exit;
        } catch (DatabaseException $e) {
            error_log('[AuthController] Erro de banco de dados no login: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro interno do sistema. Tente novamente.';
            header('Location: ' . BASE_URL . '/login');
            exit;
        } catch (Exception $e) {
            error_log('[AuthController] Erro no login: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro interno do sistema. Tente novamente.';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public function logout()
    {
        AuthHelper::logout();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
