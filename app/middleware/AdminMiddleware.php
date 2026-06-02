<?php

class AdminMiddleware
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!AuthHelper::isAdmin()) {
            error_log("[AdminMiddleware] Acesso de administrador negado.");
            $_SESSION['error'] = "Acesso restrito. Você precisa ser administrador para acessar esta área.";
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        error_log("[AdminMiddleware] Acesso de administrador concedido.");
    }
}
