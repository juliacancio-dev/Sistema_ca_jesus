<?php

class FornecedorController extends Controller
{
    public function index()
    {
        $model = new FornecedorModel();

        $termoBusca = $_GET['search'] ?? '';

        if (!empty($termoBusca)) {
            $fornecedores = $model->buscar($termoBusca);
        } else {
            $fornecedores = $model->listarTodos();
        }

        $this->renderView('layouts/fornecedor/index', [
            'fornecedores' => $fornecedores,
            'termoBusca' => $termoBusca
        ]);
    }

    public function novo()
    {
        $this->renderView('fornecedor/form', ['fornecedor' => null]);
    }

    public function editar($id)
    {
        $model = new FornecedorModel();
        $fornecedor = $model->buscarPorId($id);

        $this->renderView('fornecedor/form', ['fornecedor' => $fornecedor]);
    }

    public function salvar()
    {
        try {
            $dados = $this->processarDados();
            $erros = $this->validarDados($dados);

            if (!empty($erros)) {
                session_start();
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'message' => implode('<br>', $erros)
                ];
                header('Location: /sistema_ca_jesus/public/fornecedores');
                exit;
            }

            $model = new FornecedorModel();
            $resultado = $model->salvar($dados);

            if ($resultado) {
                ErrorHelper::logSuccess('Fornecedor salvo com sucesso', 'Teste de Sistema', json_encode($dados));
                session_start();
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => $dados['id'] ? 'Fornecedor atualizado com sucesso.' : 'Fornecedor criado com sucesso.'
                ];
            } else {
                throw new Exception('Erro ao salvar fornecedor.');
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao salvar fornecedor', 'Teste de Sistema');
            session_start();
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
        }
        header('Location: /sistema_ca_jesus/public/fornecedores');
        exit;
    }

    public function excluir($id)
    {
        $model = new FornecedorModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            try {
                $ok = $model->deletar((int)$id);
                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Fornecedor excluído com sucesso.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erro ao excluir fornecedor.']);
                }
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                if (strpos($msg, '1451') !== false || strpos($msg, '23000') !== false) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Não é possível excluir este fornecedor, pois existem registros relacionados (ex.: produtos).']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Erro no banco ao excluir fornecedor.']);
                }
            } catch (Throwable $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro inesperado ao excluir fornecedor.']);
            }
            exit;
        }
        session_start();
        $ok = $model->deletar((int)$id);
        $_SESSION['flash'] = [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Fornecedor excluído com sucesso.' : 'Erro ao excluir fornecedor.'
        ];
        header('Location: /sistema_ca_jesus/public/fornecedores');
        exit;
    }

    public function toggleStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método não permitido');
        }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $raw = file_get_contents('php://input');
            $dados = json_decode($raw, true);
            $novoStatus = null;
            if (is_array($dados) && array_key_exists('status', $dados)) {
                $novoStatus = (int)$dados['status'];
            } elseif (isset($_POST['status'])) {
                $novoStatus = (int)$_POST['status'];
            } elseif (isset($_GET['status'])) {
                $novoStatus = (int)$_GET['status'];
            }
            if ($novoStatus === null) {
                $novoStatus = 0;
            }

            $model = new FornecedorModel();
            $fornecedor = $model->buscarPorId((int)$id);
            if (!$fornecedor) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Fornecedor não encontrado']);
                exit;
            }
            if ($novoStatus === 0) {
                $qtd = $model->contarProdutosVinculados((int)$id);
                if ($qtd > 0) {
                    http_response_code(409);
                    echo json_encode(['success' => false, 'message' => 'Não é possível desativar: existem produtos vinculados a este fornecedor.']);
                    exit;
                }
            }

            $ok = $model->alternarStatus((int)$id, $novoStatus);
            if ($ok) {
                $mensagem = $novoStatus ? 'Fornecedor ativado com sucesso' : 'Fornecedor desativado com sucesso';
                echo json_encode(['success' => true, 'message' => $mensagem]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao alterar status do fornecedor']);
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao alterar status do fornecedor', 'Teste de Sistema');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
        exit;
    }

    public function listar()
    {
        require_once __DIR__ . '/../models/FornecedorModel.php';
        $model = new FornecedorModel();
        $fornecedores = $model->listarParaSelect();

        header('Content-Type: application/json');
        echo json_encode($fornecedores);
        exit;
    }
    private function processarDados(): array
    {
        return [
            'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'cnpj' => preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'telefone' => preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? ''),
            'cep' => preg_replace('/[^0-9]/', '', $_POST['cep'] ?? ''),
            'endereco' => htmlspecialchars(trim($_POST['endereco'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'numero' => htmlspecialchars(trim($_POST['numero'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'complemento' => htmlspecialchars(trim($_POST['complemento'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'bairro' => htmlspecialchars(trim($_POST['bairro'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'cidade' => htmlspecialchars(trim($_POST['cidade'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'estado' => strtoupper(htmlspecialchars(trim($_POST['estado'] ?? ''), ENT_QUOTES, 'UTF-8')),
            'ativo' => isset($_POST['ativo']) ? 1 : 0
        ];
    }
    private function validarDados(array $dados): array
    {
        $erros = [];
        if (empty($dados['nome']) || strlen($dados['nome']) < 3 || strlen($dados['nome']) > 100) {
            $erros[] = 'Nome é obrigatório e deve ter entre 3 e 100 caracteres.';
        }
        if (!$this->validarCNPJ($dados['cnpj'])) {
            $erros[] = 'CNPJ inválido.';
        }
        if (empty($dados['email']) || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Email inválido.';
        }
        $telefone = preg_replace('/[^0-9]/', '', $dados['telefone']);
        if (empty($telefone) || strlen($telefone) < 10 || strlen($telefone) > 11) {
            $erros[] = 'Telefone inválido. Deve ter 10 ou 11 dígitos.';
        }
        if (!empty($dados['cep']) && strlen($dados['cep']) !== 8) {
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
    private function validarCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }

        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}
