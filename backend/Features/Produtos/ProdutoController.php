<?php

namespace App\Features\Produtos;

use App\Core\Controller;
use App\Core\Session;
use App\Shared\Entrada;
use Exception;
use PDOException;

class ProdutoController extends Controller
{
    private $produtoModel;

    public function __construct()
    {
        $this->produtoModel = new ProdutoModel();
    }

    public function index()
    {
        $produtos = $this->produtoModel->listarComFornecedor();
        $fornecedores = $this->produtoModel->listarFornecedores();

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
        $dados = $this->dadosDoFormulario() + ["estoque" => 0];

        $this->exigirDadosValidos($dados);

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                Session::flash('success', "Produto cadastrado com sucesso!");
            } else {
                Session::flash('error', "Erro ao salvar produto no banco de dados");
            }
        } catch (Exception $e) {
            error_log('[produtos] ' . $e);
            Session::flash('error', $this->mensagemDeErro($e, "Erro ao cadastrar produto."));
        }

        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }

    public function atualizar($id)
    {
        $dados = $this->dadosDoFormulario() + ["id" => (int)$id];

        $this->exigirDadosValidos($dados);

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                Session::flash('success', "Produto atualizado com sucesso!");
            } else {
                Session::flash('error', "Produto não encontrado.");
            }
        } catch (Exception $e) {
            error_log('[produtos] ' . $e);
            Session::flash('error', $this->mensagemDeErro($e, "Erro ao atualizar produto."));
        }

        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }

    public function buscar()
    {
        $id = Entrada::inteiro($_GET["id"] ?? null) ?? 0;
        try {
            $produto = $this->produtoModel->buscarPorId($id);
        } catch (Exception $e) {
            error_log('[produtos] ' . $e);
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
            error_log('[produtos] ' . $e);
            $this->json(false, "Erro ao excluir produto.", status: 500);
        } catch (Exception $e) {
            error_log('[produtos] ' . $e);
            $this->json(false, "Erro ao excluir produto.", status: 500);
        }
        if (!$ok) {
            $this->json(false, "Produto não encontrado.", status: 404);
        }
        Session::flash('success', "Produto excluído com sucesso!");
        $this->json(true, "Produto excluído com sucesso!");
    }

    private function dadosDoFormulario(): array
    {
        return [
            "marca" => Entrada::texto($_POST["marca"] ?? ""),
            "preco_custo" => Entrada::preco($_POST["preco_custo"] ?? null),
            "preco_venda" => Entrada::preco($_POST["preco_venda"] ?? null),
            "fornecedor_id" => Entrada::inteiro($_POST["fornecedor_id"] ?? null)
        ];
    }

    private function exigirDadosValidos(array $dados): void
    {
        if ($dados["marca"] === "" || mb_strlen($dados["marca"]) > 100 || $dados["preco_custo"] === null || $dados["preco_venda"] === null || $dados["fornecedor_id"] === null) {
            Session::flash('error', "Todos os campos são obrigatórios e devem ser válidos");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            Session::flash('error', "Preço de venda não pode ser menor que o preço de custo.");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }
    }

    // Traduz erros de banco conhecidos em mensagem para o usuário; o detalhe técnico fica no log.
    private function mensagemDeErro(Exception $e, string $padrao): string
    {
        $codigoMysql = $e instanceof PDOException ? ($e->errorInfo[1] ?? null) : null;
        if ($codigoMysql === 1062) {
            return "Já existe um produto com esta marca.";
        }
        return $padrao;
    }
}
