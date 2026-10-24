<?php

use App\Core\RespostaJson;

class AuthMiddleware
{
    public function handle()
    {
        if (!AuthHelper::isLoggedIn()) {
            Session::flash('error', 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.');
            // Numa chamada fetch, o front recebe 401 e leva o usuário ao login.
            if (RespostaJson::esperada()) {
                RespostaJson::enviar(false, 'Sua sessão expirou. Faça login novamente.', status: 401);
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        AuthHelper::checkSessionTimeout();
    }
}
