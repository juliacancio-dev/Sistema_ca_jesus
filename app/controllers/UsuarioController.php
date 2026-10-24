<?php

require_once __DIR__ . '/../core/Exceptions.php';

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
        try {
            $this->verificarAutenticacao();
            $this->verificarPermissao(['admin', 'funcionario']);

            $termoBusca = $this->sanitizarInput($_GET['search'] ?? '');
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $itensPorPagina = 10;

            if (!empty($termoBusca)) {
                $resultado = $this->usuarioModel->buscarComPaginacao($termoBusca, $pagina, $itensPorPagina);
            } else {
                $resultado = $this->usuarioModel->listarTodosComPaginacao($pagina, $itensPorPagina);
            }

            $this->gerarCsrfToken();

            $this->renderView('layouts/usuario/index', [
                'usuarios' => $resultado['dados'],
                'termoBusca' => $termoBusca,
                'paginacao' => $resultado['paginacao'],
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        } catch (Exception $e) {
            $this->tratarErro($e, 'Erro ao listar usuários');
        }
    }
    public function salvar()
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarPermissao(['admin']);
            $this->verificarMetodoPost();
            $this->verificarCsrfToken();

            $dados = $this->processarDados();
            $erros = $this->validarDados($dados);

            if (!empty($erros)) {
                $this->setFlashMessage('error', implode("\n", $erros));
                $this->redirect('/usuarios');
            }
            if ($this->usuarioModel->verificarEmailExistente($dados['email'], $dados['id'])) {
                $this->setFlashMessage('error', 'Este email já está cadastrado.');
                $this->redirect('/usuarios');
            }
            if ($this->usuarioModel->verificarCpfExistente($dados['cpf'], $dados['id'])) {
                $this->setFlashMessage('error', 'Este CPF já está cadastrado.');
                $this->redirect('/usuarios');
            }
            if (!empty($dados['senha'])) {
                if (strlen($dados['senha']) < 8) {
                    $this->setFlashMessage('error', 'Senha deve ter no mínimo 8 caracteres.');
                    $this->redirect('/usuarios');
                }
                $dados['senha'] = $this->hashSenha($dados['senha']);
            } elseif (empty($dados['id'])) {
                $this->setFlashMessage('error', 'Senha é obrigatória para novo usuário.');
                $this->redirect('/usuarios');
            } else {
                unset($dados['senha']);
            }

            $resultado = $this->usuarioModel->salvar($dados);

            if ($resultado) {
                $mensagem = $dados['id'] ? 'Usuário atualizado com sucesso.' : 'Usuário criado com sucesso.';
                $this->setFlashMessage('success', $mensagem);
                $this->logarAcao($dados['id'] ? 'usuario_editado' : 'usuario_criado', ['id' => $dados['id'], 'email' => $dados['email']]);
            } else {
                $this->setFlashMessage('error', 'Erro ao salvar usuário.');
            }
        } catch (SecurityException $e) {
            $this->setFlashMessage('error', 'O formulário expirou. Recarregue a página e tente novamente.');
            $this->logarErro($e);
        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Erro ao salvar usuário.');
            $this->logarErro($e);
        }

        $this->redirect('/usuarios');
    }
    public function excluir($id)
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarPermissao(['admin']);

            header('Content-Type: application/json; charset=utf-8');
            $id = $this->validarId($id);

            if ($_SESSION['user']['id'] == $id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário']);
                exit;
            }
            $usuario = $this->usuarioModel->buscarPorId($id);

            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }

            $resultado = $this->usuarioModel->deletar($id);

            if ($resultado) {
                $this->logarAcao('usuario_excluido', ['id' => $id, 'email' => $usuario['email']]);
                $this->setFlashMessage('success', 'Usuário excluído com sucesso.');
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário']);
            }
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
            exit;
        }
    }
    public function buscar($id)
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarPermissao(['admin']);

            header('Content-Type: application/json; charset=utf-8');

            $id = $this->validarId($id);
            $usuario = $this->usuarioModel->buscarPorId($id);

            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }
            unset($usuario['senha']);

            echo json_encode(['success' => true, 'usuario' => $usuario]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
            exit;
        }
    }
    public function toggleStatus($id)
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarPermissao(['admin']);
            $this->verificarMetodoPost();

            header('Content-Type: application/json; charset=utf-8');

            $id = $this->validarId($id);
            $dados = json_decode(file_get_contents('php://input'), true);
            $novoStatus = isset($dados['status']) ? (int)$dados['status'] : 0;

            if ($_SESSION['user']['id'] == $id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Você não pode alterar o status do seu próprio usuário']);
                exit;
            }
            $usuario = $this->usuarioModel->buscarPorId($id);
            if (!$usuario) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }

            if ($this->usuarioModel->alternarStatus($id, $novoStatus)) {
                $mensagem = $novoStatus ? 'Usuário ativado com sucesso' : 'Usuário desativado com sucesso';
                $this->setFlashMessage('success', $mensagem);
                $this->logarAcao('usuario_status_alterado', ['id' => $id, 'status' => $novoStatus]);

                echo json_encode(['success' => true, 'message' => $mensagem]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao alterar status do usuário']);
            }
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
            exit;
        }
    }
    public function perfil()
    {
        try {
            $this->verificarAutenticacao();

            $usuario = $this->usuarioModel->buscarPorId($_SESSION['user']['id']);

            if (!$usuario) {
                $this->setFlashMessage('error', 'Usuário não encontrado.');
                $this->redirect('/dashboard');
            }

            $this->gerarCsrfToken();

            $this->renderView('layouts/usuario/perfil', [
                'usuario' => $usuario,
                'csrf_token' => $_SESSION['csrf_token']
            ]);
        } catch (Exception $e) {
            $this->tratarErro($e, 'Erro ao carregar perfil');
        }
    }
    public function atualizarPerfil()
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarMetodoPost();
            $this->verificarCsrfToken();

            $dados = [
                'id' => $_SESSION['user']['id'],
                'nome' => $this->sanitizarInput($_POST['nome'] ?? ''),
                'telefone' => preg_replace('/[^0-9\s\-\(\)]/', '', $_POST['telefone'] ?? ''),
                'endereco' => $this->sanitizarInput($_POST['endereco'] ?? ''),
                'cidade' => $this->sanitizarInput($_POST['cidade'] ?? ''),
                'estado' => strtoupper($this->sanitizarInput($_POST['estado'] ?? '')),
                'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
                'numero' => $this->sanitizarInput($_POST['numero'] ?? ''),
                'complemento' => $this->sanitizarInput($_POST['complemento'] ?? ''),
                'bairro' => $this->sanitizarInput($_POST['bairro'] ?? '')
            ];

            $erros = $this->validarDadosPerfil($dados);

            if (!empty($erros)) {
                $this->setFlashMessage('error', implode("\n", $erros));
                $this->redirect('/usuarios/perfil');
            }

            if ($this->usuarioModel->atualizarPerfil($dados)) {
                $this->setFlashMessage('success', 'Perfil atualizado com sucesso.');
                $this->logarAcao('perfil_atualizado', ['id' => $dados['id']]);
            } else {
                $this->setFlashMessage('error', 'Erro ao atualizar perfil.');
            }
        } catch (Exception $e) {
            $this->tratarErro($e, 'Erro ao atualizar perfil');
        }

        $this->redirect('/usuarios/perfil');
    }
    public function alterarSenha()
    {
        try {
            $this->verificarAutenticacao();
            $this->verificarMetodoPost();
            $this->verificarCsrfToken();

            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';

            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                $this->setFlashMessage('error', 'Todos os campos são obrigatórios.');
                $this->redirect('/usuarios/perfil');
            }

            if ($novaSenha !== $confirmarSenha) {
                $this->setFlashMessage('error', 'Nova senha e confirmação não coincidem.');
                $this->redirect('/usuarios/perfil');
            }

            if (strlen($novaSenha) < 8) {
                $this->setFlashMessage('error', 'Nova senha deve ter no mínimo 8 caracteres.');
                $this->redirect('/usuarios/perfil');
            }

            $usuario = $this->usuarioModel->buscarPorId($_SESSION['user']['id']);

            if (!password_verify($senhaAtual, $usuario['senha'])) {
                $this->setFlashMessage('error', 'Senha atual incorreta.');
                $this->redirect('/usuarios/perfil');
            }

            $novaSenhaHash = $this->hashSenha($novaSenha);

            if ($this->usuarioModel->alterarSenha($_SESSION['user']['id'], $novaSenhaHash)) {
                $this->setFlashMessage('success', 'Senha alterada com sucesso.');
                $this->logarAcao('senha_alterada', ['id' => $_SESSION['user']['id']]);
            } else {
                $this->setFlashMessage('error', 'Erro ao alterar senha.');
            }
        } catch (Exception $e) {
            $this->tratarErro($e, 'Erro ao alterar senha');
        }

        $this->redirect('/usuarios/perfil');
    }
    private function processarDados(): array
    {
        return [
            'id' => !empty($_POST['id']) ? $this->validarId($_POST['id']) : null,
            'tipo' => (int)($_POST['tipo'] ?? 0),
            'nome' => $this->sanitizarInput($_POST['nome'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'senha' => $_POST['senha'] ?? '',
            'cpf' => preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? ''),
            'rg' => $this->sanitizarInput($_POST['rg'] ?? ''),
            'telefone' => preg_replace('/[^0-9\s\-\(\)]/', '', $_POST['telefone'] ?? ''),
            'sexo' => $this->sanitizarInput($_POST['sexo'] ?? ''),
            'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
            'endereco' => $this->sanitizarInput($_POST['endereco'] ?? ''),
            'cidade' => $this->sanitizarInput($_POST['cidade'] ?? ''),
            'estado' => strtoupper($this->sanitizarInput($_POST['estado'] ?? '')),
            'numero' => $this->sanitizarInput($_POST['numero'] ?? ''),
            'complemento' => $this->sanitizarInput($_POST['complemento'] ?? ''),
            'bairro' => $this->sanitizarInput($_POST['bairro'] ?? ''),
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
    }
    private function validarDados(array $dados): array
    {
        $erros = [];
        if (empty($dados['nome']) || strlen($dados['nome']) < 3 || strlen($dados['nome']) > 100) {
            $erros[] = 'Nome é obrigatório e deve ter entre 3 e 100 caracteres.';
        }
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Email inválido.';
        }
        if (!array_key_exists($dados['tipo'], self::TIPOS_USUARIO)) {
            $erros[] = 'Tipo de usuário inválido.';
        }
        if (!$this->validarCpf($dados['cpf'])) {
            $erros[] = 'CPF inválido.';
        }
        if (!empty($dados['rg']) && (strlen($dados['rg']) < 3 || strlen($dados['rg']) > 20)) {
            $erros[] = 'RG deve ter entre 3 e 20 caracteres quando informado.';
        }
        $telefone = preg_replace('/[^0-9]/', '', $dados['telefone']);
        if (empty($telefone) || strlen($telefone) < 10 || strlen($telefone) > 11) {
            $erros[] = 'Telefone inválido. Deve ter 10 ou 11 dígitos.';
        }
        if (!in_array($dados['sexo'], self::SEXOS_VALIDOS)) {
            $erros[] = 'Sexo inválido.';
        }
        if (!$this->validarCep($dados['cep'])) {
            $erros[] = 'CEP inválido. Deve conter 8 dígitos.';
        }
        if (empty($dados['endereco']) || strlen($dados['endereco']) > 200) {
            $erros[] = 'Endereço é obrigatório e deve ter no máximo 200 caracteres.';
        }

        if (empty($dados['cidade']) || strlen($dados['cidade']) > 100) {
            $erros[] = 'Cidade é obrigatória e deve ter no máximo 100 caracteres.';
        }

        if (empty($dados['estado']) || strlen($dados['estado']) !== 2) {
            $erros[] = 'Estado é obrigatório e deve ter 2 caracteres.';
        }

        if (empty($dados['bairro']) || strlen($dados['bairro']) > 100) {
            $erros[] = 'Bairro é obrigatório e deve ter no máximo 100 caracteres.';
        }

        return $erros;
    }
    private function validarDadosPerfil(array $dados): array
    {
        $erros = [];

        if (empty($dados['nome']) || strlen($dados['nome']) < 3 || strlen($dados['nome']) > 100) {
            $erros[] = 'Nome é obrigatório e deve ter entre 3 e 100 caracteres.';
        }

        return $erros;
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
    private function validarCep(string $cep): bool
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) === 8 && preg_match('/^\d{8}$/', $cep);
    }
    private function validarId($id): int
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new InvalidArgumentException('ID inválido');
        }
        return $id;
    }
    private function sanitizarInput(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    private function hashSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    private function gerarCsrfToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    private function verificarCsrfToken(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new SecurityException('Token CSRF inválido');
        }
        unset($_SESSION['csrf_token']);
    }
    private function verificarMetodoPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new MethodNotAllowedException('Método não permitido');
        }
    }
    private function verificarAutenticacao(): void
    {
        if (!$this->estaLogado()) {
            $this->redirect('/login');
        }
    }
    private function verificarPermissao(array $tiposPermitidos): void
    {
        $tipoUsuario = $_SESSION['user']['tipo'] ?? null;
        $permissoes = [
            1 => 'admin',
            2 => 'funcionario'
        ];

        $permissaoAtual = $permissoes[$tipoUsuario] ?? null;

        if (!in_array($permissaoAtual, $tiposPermitidos)) {
            throw new ForbiddenException('Acesso negado');
        }
    }
    private function estaLogado(): bool
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }
    private function setFlashMessage(string $tipo, string $mensagem): void
    {
        Session::flash($tipo, $mensagem);
    }
    private function tratarErro(Exception $e, string $mensagemUsuario): void
    {
        $this->logarErro($e);
        $this->setFlashMessage('error', $mensagemUsuario);
    }
    private function logarAcao(string $acao, array $dados = []): void
    {
        $log = [
            'usuario_id' => $_SESSION['user']['id'] ?? null,
            'acao' => $acao,
            'dados' => json_encode($dados),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        error_log("ACTION: " . json_encode($log));
    }
    private function logarErro(Exception $e): void
    {
        $log = [
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'usuario_id' => $_SESSION['user']['id'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        error_log("ERROR: " . json_encode($log));
    }
}
