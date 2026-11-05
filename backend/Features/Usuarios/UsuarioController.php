<?php

namespace App\Features\Usuarios;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\ErroDeNegocio;
use App\Core\Session;
use App\Shared\Entrada;
use Exception;
use InvalidArgumentException;

// Permissão de administrador: garantida pelas rotas (AuthMiddleware + AdminMiddleware).
class UsuarioController extends Controller
{
    private $usuarioModel;
    private const TIPOS_USUARIO = [1 => 'Administrador', 2 => 'Funcionário'];
    private const SEXOS_VALIDOS = ['Masculino', 'Feminino', 'Outro'];

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    public function index()
    {
        $termoBusca = trim($_GET['search'] ?? '');
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $itensPorPagina = 10;

        if (!empty($termoBusca)) {
            $resultado = $this->usuarioModel->buscarComPaginacao($termoBusca, $pagina, $itensPorPagina);
        } else {
            $resultado = $this->usuarioModel->listarTodosComPaginacao($pagina, $itensPorPagina);
        }

        $this->render('usuarios/index', [
            'usuarios' => $resultado['dados'],
            'termoBusca' => $termoBusca,
            'paginacao' => $resultado['paginacao']
        ]);
    }

    public function salvar()
    {
        try {
            $dados = $this->processarDados();
            $erros = $this->validarDados($dados);

            if (!empty($erros)) {
                Session::flash('error', implode("\n", $erros));
                $this->redirect('/usuarios');
            }
            $this->exigirMesmoAcessoSeForOProprio($dados);
            if ($this->usuarioModel->verificarEmailExistente($dados['email'], $dados['id'])) {
                Session::flash('error', 'Este email já está cadastrado.');
                $this->redirect('/usuarios');
            }
            if ($this->usuarioModel->verificarCpfExistente($dados['cpf'], $dados['id'])) {
                Session::flash('error', 'Este CPF já está cadastrado.');
                $this->redirect('/usuarios');
            }
            if (!empty($dados['senha'])) {
                if (strlen($dados['senha']) < 8) {
                    Session::flash('error', 'Senha deve ter no mínimo 8 caracteres.');
                    $this->redirect('/usuarios');
                }
                $dados['senha'] = UsuarioModel::gerarHashDeSenha($dados['senha']);
            } elseif (empty($dados['id'])) {
                Session::flash('error', 'Senha é obrigatória para novo usuário.');
                $this->redirect('/usuarios');
            } else {
                unset($dados['senha']);
            }

            $resultado = $this->usuarioModel->salvar($dados);

            if ($resultado) {
                if (isset($dados['senha']) && $dados['id'] == $_SESSION['user']['id']) {
                    Auth::renovarSessaoAposTrocaDeSenha($dados['senha']);
                }
                $mensagem = $dados['id'] ? 'Usuário atualizado com sucesso.' : 'Usuário criado com sucesso.';
                Session::flash('success', $mensagem);
                $this->registrarAcao($dados['id'] ? 'usuario_editado' : 'usuario_criado', ['id' => $dados['id'], 'email' => $dados['email']]);
            } else {
                Session::flash('error', 'Usuário não encontrado.');
            }
        } catch (ErroDeNegocio $e) {
            Session::flash('error', $e->getMessage());
        } catch (Exception $e) {
            Session::flash('error', 'Erro ao salvar usuário.');
            error_log('[usuarios] ' . $e);
        }

        $this->redirect('/usuarios');
    }

    public function excluir($id)
    {
        try {
            $id = $this->validarId($id);

            if ($_SESSION['user']['id'] == $id) {
                $this->json(false, 'Você não pode excluir seu próprio usuário.', status: 422);
            }
            $usuario = $this->usuarioModel->buscarPorId($id);

            if (!$usuario) {
                $this->json(false, 'Usuário não encontrado.', status: 404);
            }

            $resultado = $this->usuarioModel->deletar($id);
        } catch (ErroDeNegocio $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('[usuarios] ' . $e);
            $this->json(false, 'Erro ao excluir usuário.', status: 500);
        }
        if (!$resultado) {
            $this->json(false, 'Usuário não encontrado.', status: 404);
        }
        $this->registrarAcao('usuario_excluido', ['id' => $id, 'email' => $usuario['email']]);
        Session::flash('success', 'Usuário excluído com sucesso.');
        $this->json(true, 'Usuário excluído com sucesso.');
    }

    public function buscar($id)
    {
        try {
            $id = $this->validarId($id);
            $usuario = $this->usuarioModel->buscarPorId($id);
        } catch (Exception $e) {
            error_log('[usuarios] ' . $e);
            $this->json(false, 'Erro ao carregar os dados do usuário.', status: 500);
        }
        if (!$usuario) {
            $this->json(false, 'Usuário não encontrado.', status: 404);
        }
        unset($usuario['senha']);
        $this->json(true, dados: $usuario);
    }

    public function toggleStatus($id)
    {
        try {
            $id = $this->validarId($id);
            $dados = json_decode(file_get_contents('php://input'), true);
            $novoStatus = Entrada::inteiro(is_array($dados) ? ($dados['status'] ?? null) : null, 0, 1);
            if ($novoStatus === null) {
                $this->json(false, 'Status inválido.', status: 422);
            }

            if ($_SESSION['user']['id'] == $id) {
                $this->json(false, 'Você não pode alterar o status do seu próprio usuário.', status: 422);
            }
            $usuario = $this->usuarioModel->buscarPorId($id);
            if (!$usuario) {
                $this->json(false, 'Usuário não encontrado.', status: 404);
            }

            $ok = $this->usuarioModel->alternarStatus($id, $novoStatus);
        } catch (ErroDeNegocio $e) {
            throw $e;
        } catch (Exception $e) {
            error_log('[usuarios] ' . $e);
            $this->json(false, 'Erro ao alterar status do usuário.', status: 500);
        }
        if (!$ok) {
            $this->json(false, 'Usuário não encontrado.', status: 404);
        }
        $mensagem = $novoStatus ? 'Usuário ativado com sucesso' : 'Usuário desativado com sucesso';
        Session::flash('success', $mensagem);
        $this->registrarAcao('usuario_status_alterado', ['id' => $id, 'status' => $novoStatus]);
        $this->json(true, $mensagem);
    }

    private function processarDados(): array
    {
        return [
            'id' => !empty($_POST['id']) ? $this->validarId($_POST['id']) : null,
            'tipo' => Entrada::inteiro($_POST['tipo'] ?? null) ?? 0,
            'nome' => Entrada::texto($_POST['nome'] ?? ''),
            'email' => filter_var(Entrada::texto($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'senha' => Entrada::senha($_POST['senha'] ?? ''),
            'cpf' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['cpf'] ?? '')),
            'rg' => Entrada::texto($_POST['rg'] ?? ''),
            'telefone' => preg_replace('/[^0-9\s\-\(\)]/', '', Entrada::texto($_POST['telefone'] ?? '')),
            'sexo' => Entrada::texto($_POST['sexo'] ?? ''),
            'cep' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['cep'] ?? '')),
            'endereco' => Entrada::texto($_POST['endereco'] ?? ''),
            'cidade' => Entrada::texto($_POST['cidade'] ?? ''),
            'estado' => strtoupper(Entrada::texto($_POST['estado'] ?? '')),
            'numero' => Entrada::texto($_POST['numero'] ?? ''),
            'complemento' => Entrada::texto($_POST['complemento'] ?? ''),
            'bairro' => Entrada::texto($_POST['bairro'] ?? ''),
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
    }

    private function validarDados(array $dados): array
    {
        $erros = ValidacaoDeUsuario::dadosPessoais($dados['nome'], $dados['telefone']);
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Email inválido.';
        }
        if (!array_key_exists($dados['tipo'], self::TIPOS_USUARIO)) {
            $erros[] = 'Tipo de usuário inválido.';
        }
        if (!$this->validarCpf($dados['cpf'])) {
            $erros[] = 'CPF inválido.';
        }
        if (!empty($dados['rg']) && (mb_strlen($dados['rg']) < 3 || mb_strlen($dados['rg']) > 20)) {
            $erros[] = 'RG deve ter entre 3 e 20 caracteres quando informado.';
        }
        if (!in_array($dados['sexo'], self::SEXOS_VALIDOS)) {
            $erros[] = 'Sexo inválido.';
        }

        return array_merge($erros, ValidacaoDeUsuario::endereco($dados));
    }

    // Quem se rebaixa ou se desativa perde o acesso na hora, sem como voltar pela aplicação.
    private function exigirMesmoAcessoSeForOProprio(array $dados): void
    {
        if ((int)$dados['id'] !== (int)$_SESSION['user']['id']) {
            return;
        }
        $atual = $this->usuarioModel->buscarPorId($dados['id']);
        if ($atual && ((int)$atual['tipo'] !== $dados['tipo'] || (int)$atual['ativo'] !== $dados['ativo'])) {
            throw new ErroDeNegocio('Você não pode alterar o tipo nem o status do seu próprio usuário.');
        }
    }

    private function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    private function validarId($id): int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new InvalidArgumentException('ID inválido');
        }
        return $id;
    }

    private function registrarAcao(string $acao, array $dados = []): void
    {
        error_log("[usuarios] $acao por " . ($_SESSION['user']['id'] ?? '?') . ': ' . json_encode($dados));
    }
}
