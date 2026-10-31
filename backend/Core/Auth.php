<?php

namespace App\Core;

class Auth
{

    public static function isLoggedIn()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']) &&
            isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
    }

    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login($user)
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);

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

    public static function checkSessionTimeout()
    {
        if (self::isLoggedIn() && isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > 7200) {
                self::logout();
                // logout() destrói a sessão; abre uma nova só para levar a mensagem até o login.
                session_start();
                Session::flash('error', 'Sua sessão expirou por inatividade. Por favor, faça login novamente.');
                if (RespostaJson::esperada()) {
                    RespostaJson::enviar(false, 'Sua sessão expirou. Faça login novamente.', status: 401);
                }
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            $_SESSION['last_activity'] = time();
        }
    }
}
