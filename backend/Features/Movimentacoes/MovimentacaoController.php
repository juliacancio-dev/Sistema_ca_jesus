<?php

namespace App\Features\Movimentacoes;

use App\Core\Controller;
use App\Core\Database;
use App\Core\ErroDeNegocio;
use App\Core\Session;
use App\Shared\Entrada;

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
        $produto = Entrada::inteiro($_POST['produto'] ?? null);
        $quantidade = Entrada::inteiro($_POST['quantidade'] ?? null);
        $precoCusto = Entrada::preco($_POST['preco_custo'] ?? null);
        $precoVenda = Entrada::preco($_POST['preco_venda'] ?? null);

        if ($produto === null || $quantidade === null || $precoCusto === null || $precoVenda === null) {
            throw new ErroDeNegocio('Preencha todos os campos obrigatórios corretamente.');
        }

        $registrar = new RegistrarMovimentacao(Database::getInstance()->getConnection());

        // ErroDeNegocio e ConfirmacaoNecessaria são respondidas por public/index.php.
        $registrar->executar(
            $produto,
            Entrada::texto($_POST['tipo'] ?? ''),
            $quantidade,
            $precoCusto,
            $precoVenda,
            Entrada::texto($_POST['observacao'] ?? ''),
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
