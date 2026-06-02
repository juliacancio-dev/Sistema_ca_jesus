<?php

class AuthHelper
{

    public static function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user']) && !empty($_SESSION['user']) &&
            isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
    }

    public static function getUser()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    public static function login($user)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();

        error_log("[AuthHelper] Login realizado: " . json_encode([
            'user_id' => $user['id'],
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user'])) {
            error_log("[AuthHelper] Logout realizado: " . json_encode([
                'user_id' => $_SESSION['user']['id'] ?? 'não definido',
                'session_id' => session_id(),
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        }
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function isAdmin()
    {
        $user = self::getUser();
        return $user && isset($user['tipo']) && (int)$user['tipo'] === 1;
    }

    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

            $_SESSION['error'] = 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.';

            error_log("[AuthHelper] Falha na autenticação: " . json_encode([
                'session_id' => session_id(),
                'session_data' => $_SESSION,
                'request_uri' => $_SERVER['REQUEST_URI'],
                'timestamp' => date('Y-m-d H:i:s')
            ]));

            header('Location: /sistema_ca_jesus/public/login');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    public static function requireAdmin()
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo "Acesso negado";
            exit;
        }
    }

    public static function checkSessionTimeout()
    {
        if (self::isLoggedIn() && isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > 7200) {
                self::logout();
                $_SESSION['error'] = 'Sua sessão expirou por inatividade. Por favor, faça login novamente.';
                header('Location: /sistema_ca_jesus/public/login');
                exit;
            }
            $_SESSION['last_activity'] = time();
        }
    }
}
