<?php

class AuthMiddleware
{

    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $loginPath = '/sistema_ca_jesus/public/login';
        $rootPath = '/sistema_ca_jesus/public';

        if (!AuthHelper::isLoggedIn()) {
            if ($currentPath === $rootPath || strpos($currentPath, $loginPath) === 0) {
                return;
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.';
            header('Location: ' . $loginPath);
            exit;
        }
        AuthHelper::checkSessionTimeout();
    }
}
