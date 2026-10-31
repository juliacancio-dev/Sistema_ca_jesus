<?php

use App\Core\Auth;
use App\Core\RespostaJson;
use App\Core\Session;

class AdminMiddleware
{
    public function handle()
    {
        if (!Auth::isAdmin()) {
            error_log("[AdminMiddleware] Acesso de administrador negado.");
            if (RespostaJson::esperada()) {
                RespostaJson::enviar(false, 'Acesso restrito a administradores.', status: 403);
            }
            Session::flash('error', "Acesso restrito. Você precisa ser administrador para acessar esta área.");
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        error_log("[AdminMiddleware] Acesso de administrador concedido.");
    }
}
