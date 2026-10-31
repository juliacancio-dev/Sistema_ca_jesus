<?php

namespace App\Features\Relatorios;

use App\Core\Controller;

class RelatorioController extends Controller
{
    public function index()
    {
        $title = "Relatórios - Sistema de Gestão de Estoque";
        $pageTitle = "Relatórios";

        $this->render('relatorios/index', [
            'title' => $title,
            'pageTitle' => $pageTitle
        ]);
    }

    public function getDados()
    {
        $tipo = $_GET['tipo'] ?? 'financeiro';
        $periodo = $_GET['periodo'] ?? 'mensal';
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;

        $consultas = new RelatorioConsultas();

        [$inicio, $fim] = $this->determinarIntervaloDatas($periodo, $dataInicio, $dataFim);

        $movimentacoes = $consultas->movimentacoesDoPeriodo($inicio, $fim);

        if ($tipo === 'financeiro') {
            $this->json(true, dados: RelatorioFinanceiro::montar($movimentacoes, $periodo, $inicio, $fim));
        }

        if ($tipo === 'estoque') {
            $this->json(true, dados: RelatorioEstoque::montar($movimentacoes, $consultas->produtos(), $periodo, $inicio, $fim));
        }

        $this->json(false, 'Tipo de relatório inválido.', status: 422);
    }

    private function determinarIntervaloDatas(string $periodo, ?string $dataInicio, ?string $dataFim): array
    {
        if ($dataInicio && $dataFim) {
            return [$dataInicio, $dataFim];
        }

        $hoje = date('Y-m-d');
        switch (strtolower($periodo)) {
            case 'diario':
                return [$hoje, $hoje];
            case 'semanal':
                return [date('Y-m-d', strtotime('-6 days')), $hoje];
            case 'anual':
                return [date('Y-01-01'), $hoje];
            case 'mensal':
            default:
                return [date('Y-m-01'), $hoje];
        }
    }
}
