<?php

use App\Core\Controller;
use App\Features\Produtos\ProdutoModel;
use App\Core\Database;
use App\Core\Session;
use App\Features\Movimentacoes\RegistrarMovimentacao;

class MovimentacaoController extends Controller
{
    public function index()
    {

        $movimentacaoModel = new MovimentacaoModel();
        $produtoModel = new ProdutoModel();

        $produtos = $produtoModel->listarTodos();
        $movimentacoes = $movimentacaoModel->listarTodas();
        $estoqueAtual = $produtoModel->listarTodos();

        $this->render('movimentacoes/index', [
            'produtos' => $produtos,
            'movimentacoes' => $movimentacoes,
            'estoqueAtual' => $estoqueAtual,
            'pageTitle' => 'Movimentações'
        ]);
    }

    public function registrar()
    {
        $registrar = new RegistrarMovimentacao(Database::getInstance()->getConnection());

        // ErroDeNegocio e ConfirmacaoNecessaria são respondidas por public/index.php.
        $registrar->executar(
            trim($_POST['produto'] ?? ''),
            trim($_POST['tipo'] ?? ''),
            (int)($_POST['quantidade'] ?? 0),
            (float)($_POST['preco_custo'] ?? 0),
            (float)($_POST['preco_venda'] ?? 0),
            $_POST['observacao'] ?? '',
            $_SESSION['user']['id'] ?? null,
            ($_POST['confirmar_custo_maior'] ?? '') === '1'
        );

        Session::flash('success', 'Movimentação registrada com sucesso!');
        $this->json(true, 'Movimentação registrada com sucesso!');
    }

    public function estoqueAtualJson()
    {
        $produtoModel = new ProdutoModel();
        $this->json(true, dados: $produtoModel->listarTodos());
    }
}
