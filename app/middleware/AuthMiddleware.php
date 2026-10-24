<?php

class AuthMiddleware
{
    public function handle()
    {
        if (!AuthHelper::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            Session::flash('error', 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        AuthHelper::checkSessionTimeout();
    }
}
