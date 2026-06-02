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
            header('Location: ' . BASE_URL . '/public/produtos');
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

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Todos os campos são obrigatórios e devem ser válidos";
            header('Location: ' . BASE_URL . '/public/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            if ($this->isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(["error" => "Preço de venda não pode ser menor que o preço de custo."]);
                exit;
            }
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Preço de venda não pode ser menor que o preço de custo.";
            header('Location: ' . BASE_URL . '/public/produtos');
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

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION["success"] = "Produto cadastrado com sucesso!";
            } else {
                if ($this->isAjaxRequest()) {
                    http_response_code(500);
                    echo json_encode(["error" => "Erro ao salvar produto no banco de dados"]);
                    exit;
                }

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION["error"] = "Erro ao salvar produto no banco de dados";
            }
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao cadastrar produto', 'Cadastro de Produto');

            if ($this->isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao cadastrar produto: " . $e->getMessage()]);
                exit;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Erro ao cadastrar produto: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/public/produtos');
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
            header('Location: ' . BASE_URL . '/public/produtos');
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
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Todos os campos são obrigatórios e devem ser válidos";
            header('Location: ' . BASE_URL . '/public/produtos');
            exit;
        }

        if ($dados["preco_venda"] < $dados["preco_custo"]) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Preço de venda não pode ser menor que o preço de custo.";
            header('Location: ' . BASE_URL . '/public/produtos');
            exit;
        }

        try {
            $result = $this->produtoModel->salvar($dados);
            if ($result) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION["success"] = "Produto atualizado com sucesso!";
            } else {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION["error"] = "Erro ao salvar produto no banco de dados";
            }
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["error"] = "Erro ao atualizar produto: " . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/public/produtos');
        exit;
    }

    public function listar()
    {
        header("Content-Type: application/json");
        $produtos = $this->produtoModel->listarTodos();
        echo json_encode($produtos);
    }

    public function buscar()
    {
        header("Content-Type: application/json");
        if (isset($_GET["id"])) {
            $id = intval($_GET["id"]);
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
                echo json_encode(["error" => "Erro ao buscar produto: " . $e->getMessage()]);
            }
        } else {
            $termo = $_GET["q"] ?? "";
            try {
                $produtos = $this->produtoModel->buscar($termo);
                echo json_encode($produtos);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "Erro ao buscar produtos: " . $e->getMessage()]);
            }
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
                http_response_code(500);
                echo json_encode(["error" => "Erro ao excluir produto: " . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao excluir produto: " . $e->getMessage()]);
        }
    }
}
