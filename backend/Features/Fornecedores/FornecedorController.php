<?php

namespace App\Features\Fornecedores;

use App\Core\Controller;
use App\Core\DatabaseException;
use App\Core\ErroDeNegocio;
use App\Core\Session;
use App\Shared\Entrada;
use Exception;
use Throwable;

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

        $this->render('fornecedores/index', [
            'fornecedores' => $fornecedores,
            'termoBusca' => $termoBusca
        ]);
    }

    public function salvar()
    {
        try {
            $dados = $this->processarDados();
            $erros = $this->validarDados($dados);

            if (!empty($erros)) {
                Session::flash('error', implode("\n", $erros));
                header('Location: ' . BASE_URL . '/fornecedores');
                exit;
            }

            $model = new FornecedorModel();
            $resultado = $model->salvar($dados);

            if ($resultado) {
                Session::flash('success', $dados['id'] ? 'Fornecedor atualizado com sucesso.' : 'Fornecedor criado com sucesso.');
            } else {
                Session::flash('error', 'Fornecedor não encontrado.');
            }
        } catch (ErroDeNegocio | DatabaseException $e) {
            Session::flash('error', $e->getMessage());
        } catch (Exception $e) {
            error_log('[fornecedores] ' . $e);
            Session::flash('error', 'Erro ao salvar fornecedor.');
        }
        header('Location: ' . BASE_URL . '/fornecedores');
        exit;
    }

    public function excluir($id)
    {
        $model = new FornecedorModel();

        try {
            // O model converte erros de PDO em DatabaseException, então o vínculo é checado antes.
            if ($model->contarProdutosVinculados((int)$id) > 0) {
                $this->json(false, 'Não é possível excluir este fornecedor, pois existem produtos vinculados a ele.', status: 409);
            }
            $ok = $model->deletar((int)$id);
        } catch (Throwable $e) {
            error_log('[fornecedores] ' . $e);
            $this->json(false, 'Erro ao excluir fornecedor.', status: 500);
        }
        if (!$ok) {
            $this->json(false, 'Fornecedor não encontrado.', status: 404);
        }
        Session::flash('success', 'Fornecedor excluído com sucesso.');
        $this->json(true, 'Fornecedor excluído com sucesso.');
    }

    public function toggleStatus($id)
    {
        try {
            $dados = json_decode(file_get_contents('php://input'), true);
            $novoStatus = Entrada::inteiro(is_array($dados) ? ($dados['status'] ?? null) : null, 0, 1);
            if ($novoStatus === null) {
                $this->json(false, 'Status inválido.', status: 422);
            }

            $model = new FornecedorModel();
            $fornecedor = $model->buscarPorId((int)$id);
            if (!$fornecedor) {
                $this->json(false, 'Fornecedor não encontrado.', status: 404);
            }
            if ($novoStatus === 0 && $model->contarProdutosVinculados((int)$id) > 0) {
                $this->json(false, 'Não é possível desativar: existem produtos vinculados a este fornecedor.', status: 409);
            }

            $ok = $model->alternarStatus((int)$id, $novoStatus);
        } catch (Exception $e) {
            error_log('[fornecedores] ' . $e);
            $this->json(false, 'Erro ao alterar status do fornecedor.', status: 500);
        }
        if (!$ok) {
            $this->json(false, 'Fornecedor não encontrado.', status: 404);
        }
        $mensagem = $novoStatus ? 'Fornecedor ativado com sucesso' : 'Fornecedor desativado com sucesso';
        Session::flash('success', $mensagem);
        $this->json(true, $mensagem);
    }

    private function processarDados(): array
    {
        $id = null;
        if (!empty($_POST['id'])) {
            // Um id inválido viraria 0, e o formulário criaria outro fornecedor em vez de editar.
            $id = Entrada::inteiro($_POST['id']) ?? throw new ErroDeNegocio('Fornecedor não encontrado.', 404);
        }

        return [
            'id' => $id,
            'nome' => Entrada::texto($_POST['nome'] ?? ''),
            'cnpj' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['cnpj'] ?? '')),
            'email' => filter_var(Entrada::texto($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'telefone' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['telefone'] ?? '')),
            'cep' => preg_replace('/[^0-9]/', '', Entrada::texto($_POST['cep'] ?? '')),
            'endereco' => Entrada::texto($_POST['endereco'] ?? ''),
            'numero' => Entrada::texto($_POST['numero'] ?? ''),
            'complemento' => Entrada::texto($_POST['complemento'] ?? ''),
            'bairro' => Entrada::texto($_POST['bairro'] ?? ''),
            'cidade' => Entrada::texto($_POST['cidade'] ?? ''),
            'estado' => strtoupper(Entrada::texto($_POST['estado'] ?? ''))
        ];
    }
    private function validarDados(array $dados): array
    {
        $erros = [];
        if (empty($dados['nome']) || mb_strlen($dados['nome']) < 3 || mb_strlen($dados['nome']) > 100) {
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
        if (empty($dados['endereco']) || mb_strlen($dados['endereco']) > 200) {
            $erros[] = 'Endereço é obrigatório e deve ter no máximo 200 caracteres.';
        }

        if (empty($dados['cidade']) || mb_strlen($dados['cidade']) > 100) {
            $erros[] = 'Cidade é obrigatória e deve ter no máximo 100 caracteres.';
        }

        if (empty($dados['estado']) || strlen($dados['estado']) !== 2) {
            $erros[] = 'Estado é obrigatório e deve ter 2 caracteres.';
        }

        if (empty($dados['bairro']) || mb_strlen($dados['bairro']) > 100) {
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
