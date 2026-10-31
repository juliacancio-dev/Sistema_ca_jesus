<?php

namespace App\Features\Movimentacoes;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class MovimentacaoController extends Controller
{
    public function index()
    {
        $model = new MovimentacaoModel();

        $this->render('movimentacoes/index', [
            'produtos' => $model->listarProdutos(),
            'movimentacoes' => $model->listarTodas(),
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
        $this->json(true, dados: (new MovimentacaoModel())->listarProdutos());
    }
}
