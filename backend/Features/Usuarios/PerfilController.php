<?php

namespace App\Features\Usuarios;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Shared\Entrada;
use Exception;

// O próprio usuário logado, seja qual for o tipo: só exige login, e o id vem sempre da sessão.
class PerfilController extends Controller
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    public function perfil()
    {
        $usuario = $this->usuarioModel->buscarPorId($_SESSION['user']['id']);

        if (!$usuario) {
            Session::flash('error', 'Usuário não encontrado.');
            $this->redirect('/dashboard');
        }

        $this->render('usuarios/perfil', [
            'usuario' => $usuario
        ]);
    }

    public function atualizarDados()
    {
        try {
            $nome = Entrada::texto($_POST['nome'] ?? '');
            $telefone = preg_replace('/[^0-9\s\-\(\)]/', '', Entrada::texto($_POST['telefone'] ?? ''));

            $erros = ValidacaoDeUsuario::dadosPessoais($nome, $telefone);
            if (!empty($erros)) {
                Session::flash('error', implode("\n", $erros));
                $this->redirect('/usuarios/perfil');
            }

            if ($this->usuarioModel->atualizarDadosPessoais($_SESSION['user']['id'], $nome, $telefone)) {
                // O cabeçalho mostra o nome guardado na sessão.
                $_SESSION['user']['nome'] = $nome;
                Session::flash('success', 'Perfil atualizado com sucesso.');
                $this->registrarAcao('dados_atualizados');
            } else {
                Session::flash('error', 'Erro ao atualizar perfil.');
            }
        } catch (Exception $e) {
            error_log('[perfil] ' . $e);
            Session::flash('error', 'Erro ao atualizar perfil.');
        }

        $this->redirect('/usuarios/perfil');
    }

    public function atualizarEndereco()
    {
        try {
            $endereco = [
                'endereco' => Entrada::texto($_POST['endereco'] ?? ''),
                'cidade' => Entrada::texto($_POST['cidade'] ?? ''),
                'estado' => strtoupper(Entrada::texto($_POST['estado'] ?? '')),
                'cep' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['cep'] ?? '')),
                'numero' => Entrada::texto($_POST['numero'] ?? ''),
                'complemento' => Entrada::texto($_POST['complemento'] ?? ''),
                'bairro' => Entrada::texto($_POST['bairro'] ?? '')
            ];

            $erros = ValidacaoDeUsuario::endereco($endereco);
            if (!empty($erros)) {
                Session::flash('error', implode("\n", $erros));
                $this->redirect('/usuarios/perfil');
            }

            if ($this->usuarioModel->atualizarEndereco($_SESSION['user']['id'], $endereco)) {
                Session::flash('success', 'Perfil atualizado com sucesso.');
                $this->registrarAcao('endereco_atualizado');
            } else {
                Session::flash('error', 'Erro ao atualizar perfil.');
            }
        } catch (Exception $e) {
            error_log('[perfil] ' . $e);
            Session::flash('error', 'Erro ao atualizar perfil.');
        }

        $this->redirect('/usuarios/perfil');
    }

    public function alterarSenha()
    {
        try {
            $senhaAtual = Entrada::senha($_POST['senha_atual'] ?? '');
            $novaSenha = Entrada::senha($_POST['nova_senha'] ?? '');
            $confirmarSenha = Entrada::senha($_POST['confirmar_senha'] ?? '');

            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                Session::flash('error', 'Todos os campos são obrigatórios.');
                $this->redirect('/usuarios/perfil');
            }

            if ($novaSenha !== $confirmarSenha) {
                Session::flash('error', 'Nova senha e confirmação não coincidem.');
                $this->redirect('/usuarios/perfil');
            }

            if (strlen($novaSenha) < 8) {
                Session::flash('error', 'Nova senha deve ter no mínimo 8 caracteres.');
                $this->redirect('/usuarios/perfil');
            }

            $usuario = $this->usuarioModel->buscarPorId($_SESSION['user']['id']);

            if (!password_verify($senhaAtual, $usuario['senha'])) {
                Session::flash('error', 'Senha atual incorreta.');
                $this->redirect('/usuarios/perfil');
            }

            $novoHash = UsuarioModel::gerarHashDeSenha($novaSenha);
            if ($this->usuarioModel->alterarSenha($_SESSION['user']['id'], $novoHash)) {
                Auth::renovarSessaoAposTrocaDeSenha($novoHash);
                Session::flash('success', 'Senha alterada com sucesso.');
                $this->registrarAcao('senha_alterada');
            } else {
                Session::flash('error', 'Erro ao alterar senha.');
            }
        } catch (Exception $e) {
            error_log('[perfil] ' . $e);
            Session::flash('error', 'Erro ao alterar senha.');
        }

        $this->redirect('/usuarios/perfil');
    }

    private function registrarAcao(string $acao): void
    {
        error_log("[perfil] $acao por " . $_SESSION['user']['id']);
    }
}
