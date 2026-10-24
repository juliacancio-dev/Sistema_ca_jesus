<?php

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

        $this->renderView('layouts/produto/index', [
            'produtos' => $produtos,
            'fornecedores' => $fornecedores,
            'title' => $title,
            'pageTitle' => $pageTitle
        ]);
    }

    public function salvar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            ErrorHelper::logSuccess('Acesso incorreto via GET bloqueado', 'Validação de Método');
            if ($this->isAjaxRequest()) {
                http_response_code(405);
                echo json_encode(["error" => "Método não permitido"]);
                exit;
            }
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        $dados = [
            "marca" => $_POST["marca"] ?? "",
            "preco_custo" => floatval($_POST["preco_custo"] ?? 0),
            "preco_venda" => floatval($_POST["preco_venda"] ?? 0),
            "fornecedor_id" => intval($_POST["fornecedor_id"] ?? 0),
            "estoque" => 0
        ];

        if (empty($dados["marca"]) || $dados["preco_custo"] < 0 || $dados["preco_venda"] < 0 || $dados["fornecedor_id"] <= 0) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(["error" => "Todos os campos são obrigatórios e devem ser válidos"]);
                exit;
            }

            Session::flash('error', "Todos os campos são obrigatórios e devem ser válidos");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(["error" => "Preço de venda não pode ser menor que o preço de custo."]);
                exit;
            }
            Session::flash('error', "Preço de venda não pode ser menor que o preço de custo.");
            header('Location: ' . BASE_URL . '/produtos');
            exit;
        }

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                ErrorHelper::logSuccess('Produto cadastrado com sucesso', 'Cadastro de Produto', json_encode($dados));

                if ($this->isAjaxRequest()) {
                    echo json_encode(["success" => true, "message" => "Produto cadastrado com sucesso!"]);
                    exit;
                }

                Session::flash('success', "Produto cadastrado com sucesso!");
            } else {
                if ($this->isAjaxRequest()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao salvar produto no banco de dados"]);
                    exit;
                }

                Session::flash('error', "Erro ao salvar produto no banco de dados");
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao cadastrar produto', 'Cadastro de Produto');

            if ($this->isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(["error" => $this->mensagemDeErro($e, "Erro ao cadastrar produto.")]);
                exit;
            }

            Session::flash('error', $this->mensagemDeErro($e, "Erro ao cadastrar produto."));
        }

        header('Location: ' . BASE_URL . '/produtos');
        exit;
    }

    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
        header("Content-Type: application/json");
        $id = intval($_GET["id"] ?? 0);
        try {
            $produto = $this->produtoModel->buscarPorId($id);
            if ($produto) {
                echo json_encode($produto);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Produto não encontrado."]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            ErrorHelper::handle($e, 'Erro ao buscar produto', 'Cadastro de Produto');
            echo json_encode(["error" => "Erro ao buscar produto."]);
        }
    }

    public function excluir($id)
    {
        header("Content-Type: application/json");
        if ($_SERVER["REQUEST_METHOD"] !== "DELETE" && $_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Método não permitido."]);
            exit;
        }
        try {
            $ok = $this->produtoModel->deletar($id);
            if ($ok) {
                Session::flash('success', "Produto excluído com sucesso!");
                echo json_encode(["success" => true, "message" => "Produto excluído com sucesso!"]);
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Produto não encontrado."]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);
                echo json_encode(["error" => "Não é possível excluir este produto porque há movimentações associadas."]);
            } else {
                ErrorHelper::handle($e, 'Erro ao excluir produto', 'Cadastro de Produto');
                http_response_code(500);
                echo json_encode(["error" => "Erro ao excluir produto."]);
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao excluir produto', 'Cadastro de Produto');
            http_response_code(500);
            echo json_encode(["error" => "Erro ao excluir produto."]);
        }
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
