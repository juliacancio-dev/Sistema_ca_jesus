<?php

use App\Core\Controller;
use App\Core\Session;
use App\Features\Fornecedores\FornecedorModel;

class ProdutoController extends Controller
{
    private $produtoModel;
    private $fornecedorModel;

    public function __construct()
    {
        $this->produtoModel = new ProdutoModel();
        $this->fornecedorModel = new FornecedorModel();
    }

    public function index()
    {
        $produtos = $this->produtoModel->listarComFornecedor();
        $fornecedores = $this->fornecedorModel->listarTodos();

        $title = "Produtos - Sistema de Gestão de Estoque";
        $pageTitle = "Produtos";

        $this->render('produtos/index', [
            'produtos' => $produtos,
            'fornecedores' => $fornecedores,
            'title' => $title,
            'pageTitle' => $pageTitle
        ]);
    }

    public function salvar()
    {
        $dados = [
            "marca" => $_POST["marca"] ?? "",
            "preco_custo" => floatval($_POST["preco_custo"] ?? 0),
            "preco_venda" => floatval($_POST["preco_venda"] ?? 0),
            "fornecedor_id" => intval($_POST["fornecedor_id"] ?? 0),
            "estoque" => 0
        ];

        if (empty($dados["marca"]) || $dados["preco_custo"] < 0 || $dados["preco_venda"] < 0 || $dados["fornecedor_id"] <= 0) {
            Session::flash('error', "Todos os campos são obrigatórios e devem ser válidos");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            Session::flash('error', "Preço de venda não pode ser menor que o preço de custo.");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                ErrorHelper::logSuccess('Produto cadastrado com sucesso', 'Cadastro de Produto', json_encode($dados));
                Session::flash('success', "Produto cadastrado com sucesso!");
            } else {
                Session::flash('error', "Erro ao salvar produto no banco de dados");
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao cadastrar produto', 'Cadastro de Produto');
            Session::flash('error', $this->mensagemDeErro($e, "Erro ao cadastrar produto."));
        }

        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }

    public function atualizar($id)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        $dados = [
            "id" => $id,
            "marca" => $_POST["marca"] ?? "",
            "preco_custo" => floatval($_POST["preco_custo"] ?? 0),
            "preco_venda" => floatval($_POST["preco_venda"] ?? 0),
            "fornecedor_id" => intval($_POST["fornecedor_id"] ?? 0)
        ];

        if (empty($dados["marca"]) || $dados["preco_custo"] < 0 || $dados["preco_venda"] < 0 || $dados["fornecedor_id"] <= 0) {
            Session::flash('error', "Todos os campos são obrigatórios e devem ser válidos");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            Session::flash('error', "Preço de venda não pode ser menor que o preço de custo.");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                Session::flash('success', "Produto atualizado com sucesso!");
            } else {
                Session::flash('error', "Erro ao salvar produto no banco de dados");
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao atualizar produto', 'Cadastro de Produto');
            Session::flash('error', $this->mensagemDeErro($e, "Erro ao atualizar produto."));
        }

        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }

    public function buscar()
    {
        $id = intval($_GET["id"] ?? 0);
        try {
            $produto = $this->produtoModel->buscarPorId($id);
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao buscar produto', 'Cadastro de Produto');
            $this->json(false, "Erro ao buscar produto.", status: 500);
        }
        if (!$produto) {
            $this->json(false, "Produto não encontrado.", status: 404);
        }
        $this->json(true, dados: $produto);
    }

    public function excluir($id)
    {
        try {
            $ok = $this->produtoModel->deletar($id);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->json(false, "Não é possível excluir este produto porque há movimentações associadas.", status: 409);
            }
            ErrorHelper::handle($e, 'Erro ao excluir produto', 'Cadastro de Produto');
            $this->json(false, "Erro ao excluir produto.", status: 500);
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao excluir produto', 'Cadastro de Produto');
            $this->json(false, "Erro ao excluir produto.", status: 500);
        }
        if (!$ok) {
            $this->json(false, "Produto não encontrado.", status: 404);
        }
        Session::flash('success', "Produto excluído com sucesso!");
        $this->json(true, "Produto excluído com sucesso!");
    }

    // Traduz erros de banco conhecidos em mensagem para o usuário; o detalhe técnico fica no log.
    private function mensagemDeErro(Exception $e, string $padrao): string
    {
        $codigoMysql = $e instanceof PDOException ? ($e->errorInfo[1] ?? null) : null;
        if ($codigoMysql === 1062) {
            return "Já existe um produto com esta marca.";
        }
        if ($codigoMysql === 1451) {
            return "Não é possível alterar a marca: este produto já tem movimentações registradas.";
        }
        return $padrao;
    }
}
