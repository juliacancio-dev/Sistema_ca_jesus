<?php

namespace App\Features\Autenticacao;

use App\Core\Auth;
use App\Core\RespostaJson;
use App\Core\Session;

class AuthMiddleware
{
    public function handle()
    {
        if (!Auth::isLoggedIn()) {
            Session::flash('error', 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.');
            // Numa chamada fetch, o front recebe 401 e leva o usuário ao login.
            if (RespostaJson::esperada()) {
                RespostaJson::enviar(false, 'Sua sessão expirou. Faça login novamente.', status: 401);
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        if (Auth::sessaoExpirada()) {
            $this->encerrar('Sua sessão expirou. Por favor, faça login novamente.');
        }

        // Desativar, excluir, rebaixar ou trocar a senha vale já na próxima requisição.
        $usuario = (new AutenticacaoConsultas())->estadoDaSessao((int)Auth::getUser()['id']);
        if (!$usuario || (int)$usuario['ativo'] !== 1 || !Auth::senhaInalterada($usuario['senha'])) {
            $this->encerrar('Seu acesso foi encerrado. Faça login novamente.');
        }

        Auth::atualizarDados((int)$usuario['tipo'], $usuario['nome']);
        Auth::registrarAtividade();
    }

    private function encerrar(string $mensagem): never
    {
        Auth::logout();
        // logout() destrói a sessão; abre uma nova só para levar a mensagem até o login.
        session_start();
        Session::flash('error', $mensagem);
        if (RespostaJson::esperada()) {
            RespostaJson::enviar(false, 'Sua sessão expirou. Faça login novamente.', status: 401);
        }
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
