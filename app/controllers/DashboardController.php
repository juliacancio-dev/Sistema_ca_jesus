<?php

class DashboardController extends Controller
{
    private $produtoModel;
    private $movimentacaoModel;

    public function __construct()
    {
        $this->produtoModel = new ProdutoModel();
        $this->movimentacaoModel = new MovimentacaoModel();
    }

    public function index()
    {
        $user = AuthHelper::getUser();

        $stats = [
            "total_produtos" => $this->produtoModel->contarTotal(),
            "estoque_total" => $this->getEstoqueTotal(),
            "movimentacoes_hoje" => $this->getMovimentacoesHoje(),
            "lucro_estimado" => $this->getLucroEstimado(),
            "saldo_financeiro" => $this->getSaldoFinanceiro()
        ];

        $atividades = $this->getAtividadesRecentes();

        $dadosGraficos = [
            "movimentacoes" => $this->getMovimentacoesPorDia(),
            "estoque" => $this->getEstoquePorProduto()
        ];

        $title = "Dashboard - Sistema de Gestão de Estoque";
        $pageTitle = "Dashboard";

        $this->renderView('layouts/dashboard/index', [
            'stats' => $stats,
            'atividades' => $atividades,
            'dadosGraficos' => $dadosGraficos,
            'title' => $title,
            'pageTitle' => $pageTitle
        ]);
    }

    private function getEstoqueTotal()
    {
        $produtos = $this->produtoModel->listarTodos();
        return array_sum(array_column($produtos, "estoque"));
    }

    private function getMovimentacoesHoje()
    {
        $movimentacoes = $this->movimentacaoModel->getMovimentacoesHoje();
        return count($movimentacoes);
    }

    private function getLucroEstimado()
    {
        return $this->movimentacaoModel->getLucroTotal() ?? 0;
    }

    private function getSaldoFinanceiro()
    {
        return $this->movimentacaoModel->getSaldoFinanceiro() ?? 0;
    }

    private function getAtividadesRecentes()
    {
        $movimentacoes = $this->movimentacaoModel->listarTodas();
        return array_slice($movimentacoes, 0, 5);
    }

    private function getMovimentacoesPorDia()
    {
        $dias = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $entradas = array_fill(0, 7, 0);
        $saidas = array_fill(0, 7, 0);

        $movimentacoes = $this->movimentacaoModel->getMovimentacoesUltimos7Dias();
        foreach ($movimentacoes as $mov) {
            $data = date('N', strtotime($mov['data']));
            $idx = $data - 1;
            if ($mov['tipo'] === 'entrada') {
                $entradas[$idx] += $mov['quantidade'];
            } else {
                $saidas[$idx] += $mov['quantidade'];
            }
        }
        return [
            'labels' => $dias,
            'entradas' => $entradas,
            'saidas' => $saidas
        ];
    }

    private function getEstoquePorProduto()
    {
        return $this->produtoModel->listarTodos();
    }

    public function getEstatisticas()
    {
        header("Content-Type: application/json");

        $stats = [
            "total_produtos" => $this->produtoModel->contarTotal(),
            "estoque_total" => $this->getEstoqueTotal(),
            "movimentacoes_hoje" => $this->getMovimentacoesHoje(),
            "lucro_estimado" => $this->getLucroEstimado(),
            "saldo_financeiro" => $this->getSaldoFinanceiro()
        ];

        echo json_encode($stats);
    }
}
