<?php

require_once __DIR__ . '/../core/Exceptions.php';

class AuthController extends Controller
{
    private $usuarioModel;
    private $db;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        try {
            $this->usuarioModel = new UsuarioModel();
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            error_log('[AuthController] Erro ao inicializar: ' . $e->getMessage());
            throw $e;
        }
    }

    public function loginForm()
    {
        if (AuthHelper::isLoggedIn()) {
            header('Location: /sistema_ca_jesus/public/dashboard');
            exit;
        }
        $this->renderView('layouts/auth/login');
    }

    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: /sistema_ca_jesus/public/login');
                exit;
            }

            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                $_SESSION['error'] = 'Email e senha são obrigatórios';
                header('Location: /sistema_ca_jesus/public/login');
                exit;
            }

            error_log("[AuthController] Tentativa de login para: $email");

            try {
                $uDados = $this->usuarioModel->buscarPorEmail($email);
                if ($uDados && isset($uDados['ativo']) && (int)$uDados['ativo'] === 0) {
                    $_SESSION['error'] = 'Seu usuário está inativo. Contate o administrador.';
                    header('Location: /sistema_ca_jesus/public/login');
                    exit;
                }
            } catch (Throwable $e) {
            }

            $usuario = $this->usuarioModel->autenticar($email, $senha);

            if ($usuario) {
                AuthHelper::login($usuario);
                $redirect = $_SESSION['redirect_after_login'] ?? '/sistema_ca_jesus/public/dashboard';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            }

            error_log("[AuthController] Falha de login para: $email");
            $_SESSION['error'] = 'Email ou senha incorretos';
            header('Location: /sistema_ca_jesus/public/login');
            exit;
        } catch (DatabaseException $e) {
            error_log('[AuthController] Erro de banco de dados no login: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro interno do sistema. Tente novamente.';
            header('Location: /sistema_ca_jesus/public/login');
            exit;
        } catch (Exception $e) {
            error_log('[AuthController] Erro no login: ' . $e->getMessage());
            $_SESSION['error'] = 'Erro interno do sistema. Tente novamente.';
            header('Location: /sistema_ca_jesus/public/login');
            exit;
        }
    }

    public function logout()
    {
        AuthHelper::logout();
        header('Location: /sistema_ca_jesus/public/login');
        exit;
    }
}
