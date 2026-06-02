<?php

class MovimentacaoController extends Controller
{
    public function index()
    {

        $movimentacaoModel = new MovimentacaoModel();
        $produtoModel = new ProdutoModel();

        $produtos = $produtoModel->listarTodos();
        $movimentacoes = $movimentacaoModel->listarTodas();
        $estoqueAtual = $produtoModel->listarTodos();

        $this->renderView('layouts/movimentacao/index', [
            'produtos' => $produtos,
            'movimentacoes' => $movimentacoes,
            'estoqueAtual' => $estoqueAtual,
            'pageTitle' => 'Movimentações'
        ]);
    }

    public function registrar()
    {
        $model = new MovimentacaoModel();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $produtoMarca = trim($_POST['produto'] ?? '');
        $acao = trim($_POST['tipo'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $precoCusto = (float)($_POST['preco_custo'] ?? 0);
        $precoVenda = (float)($_POST['preco_venda'] ?? 0);
        $observacao = $_POST['observacao'] ?? '';
        $usuarioId = $_SESSION['user']['id'] ?? null;

        if (empty($produtoMarca) || empty($acao) || $quantidade <= 0 || $precoCusto < 0 || $precoVenda < 0) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Preencha todos os campos obrigatórios corretamente.'];
            return $this->redirect('/movimentacoes');
        }

        if ($precoCusto > $precoVenda) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Preço de custo não pode ser maior que o preço de venda.'];
            return $this->redirect('/movimentacoes');
        }

        if ($acao === 'saida') {
            $produtoModel = new ProdutoModel();
            $estoqueAtual = null;
            foreach ($produtoModel->listarTodos() as $p) {
                if (isset($p['marca']) && $p['marca'] === $produtoMarca) {
                    $estoqueAtual = (int)$p['estoque'];
                    break;
                }
            }
            if ($estoqueAtual === null) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produto não encontrado.'];
                return $this->redirect('/movimentacoes');
            }
            if ($estoqueAtual <= 0) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Estoque do produto está zerado. Não é possível registrar saída.'];
                return $this->redirect('/movimentacoes');
            }
            if ($quantidade > $estoqueAtual) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Quantidade de saída maior que o estoque disponível (' . $estoqueAtual . ').'];
                return $this->redirect('/movimentacoes');
            }
        }

        $dados = [
            'marca_produto' => $produtoMarca,
            'acao' => $acao,
            'quantidade' => $quantidade,
            'preco_custo' => $precoCusto,
            'preco_venda' => $precoVenda,
            'observacao' => $observacao,
            'usuario_id' => $usuarioId
        ];

        try {
            $ok = $model->registrar($dados);
            ErrorHelper::logSuccess('Movimentação registrada com sucesso', 'Registro de Movimentação', json_encode($dados));
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Movimentação registrada com sucesso!'
            ];
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao registrar movimentação', 'Registro de Movimentação');
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
        }
        $this->redirect('/movimentacoes');
    }

    public function movimentacoesJson()
    {
        $movimentacaoModel = new MovimentacaoModel();
        header('Content-Type: application/json');
        $movimentacoes = $movimentacaoModel->listarTodas();
        echo json_encode($movimentacoes);
        exit;
    }

    public function estoqueAtualJson()
    {
        $produtoModel = new ProdutoModel();
        header('Content-Type: application/json');
        $estoqueAtual = $produtoModel->listarTodos();
        echo json_encode($estoqueAtual);
        exit;
    }
}
