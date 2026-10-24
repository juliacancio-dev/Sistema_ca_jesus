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

        $produtoMarca = trim($_POST['produto'] ?? '');
        $acao = trim($_POST['tipo'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $precoCusto = (float)($_POST['preco_custo'] ?? 0);
        $precoVenda = (float)($_POST['preco_venda'] ?? 0);
        $observacao = $_POST['observacao'] ?? '';
        $usuarioId = $_SESSION['user']['id'] ?? null;

        if (empty($produtoMarca) || empty($acao) || $quantidade <= 0 || $precoCusto < 0 || $precoVenda < 0) {
            Session::flash('error', 'Preencha todos os campos obrigatórios corretamente.');
            return $this->redirect('/movimentacoes');
        }

        if ($precoCusto > $precoVenda) {
            Session::flash('error', 'Preço de custo não pode ser maior que o preço de venda.');
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
                Session::flash('error', 'Produto não encontrado.');
                return $this->redirect('/movimentacoes');
            }
            if ($estoqueAtual <= 0) {
                Session::flash('error', 'Estoque do produto está zerado. Não é possível registrar saída.');
                return $this->redirect('/movimentacoes');
            }
            if ($quantidade > $estoqueAtual) {
                Session::flash('error', 'Quantidade de saída maior que o estoque disponível (' . $estoqueAtual . ').');
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
            $model->registrar($dados);
            ErrorHelper::logSuccess('Movimentação registrada com sucesso', 'Registro de Movimentação', json_encode($dados));
            Session::flash('success', 'Movimentação registrada com sucesso!');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (PDOException $e) {
            ErrorHelper::handle($e, 'Erro ao registrar movimentação', 'Registro de Movimentação');
            // SQLSTATE 45000 = regra de negócio sinalizada por trigger (ex.: estoque insuficiente)
            $mensagemDoBanco = ($e->errorInfo[0] ?? '') === '45000' ? ($e->errorInfo[2] ?? null) : null;
            Session::flash('error', $mensagemDoBanco ?: 'Não foi possível registrar a movimentação. Tente novamente.');
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao registrar movimentação', 'Registro de Movimentação');
            Session::flash('error', 'Não foi possível registrar a movimentação. Tente novamente.');
        }
        $this->redirect('/movimentacoes');
    }

    public function estoqueAtualJson()
    {
        $produtoModel = new ProdutoModel();
        $this->json(true, dados: $produtoModel->listarTodos());
    }
}
