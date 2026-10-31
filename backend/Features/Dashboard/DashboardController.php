<?php

namespace App\Features\Dashboard;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index()
    {
        $consultas = new DashboardConsultas(Database::getInstance()->getConnection());

        $stats = [
            "total_produtos" => $consultas->totalDeProdutos(),
            "estoque_total" => $consultas->estoqueTotal(),
            "movimentacoes_hoje" => $consultas->movimentacoesDoDia(date('Y-m-d')),
            "saldo_financeiro" => $consultas->saldoFinanceiro()
        ];

        $dadosGraficos = [
            "movimentacoes" => $this->movimentacoesPorDia($consultas->movimentacoesDosUltimos7Dias()),
            "estoque" => $consultas->estoquePorProduto()
        ];

        $this->render('dashboard/index', [
            'stats' => $stats,
            'atividades' => $consultas->atividadesRecentes(),
            'dadosGraficos' => $dadosGraficos,
            'title' => "Dashboard - Sistema de Gestão de Estoque",
            'pageTitle' => "Dashboard"
        ]);
    }

    private function movimentacoesPorDia(array $movimentacoes): array
    {
        $dias = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
        $entradas = array_fill(0, 7, 0);
        $saidas = array_fill(0, 7, 0);

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
}
