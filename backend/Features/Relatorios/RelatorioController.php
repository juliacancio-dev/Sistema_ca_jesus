<?php

namespace App\Features\Relatorios;

use App\Core\Controller;
use App\Shared\Entrada;

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
        $tipo = Entrada::texto($_GET['tipo'] ?? 'financeiro');
        $periodo = Entrada::texto($_GET['periodo'] ?? 'mensal');

        if ($tipo !== 'financeiro' && $tipo !== 'estoque') {
            $this->json(false, 'Tipo de relatório inválido.', status: 422);
        }

        // ErroDeNegocio de período inválido é respondida com 422 por public/index.php.
        [$inicio, $fim] = PeriodoDoRelatorio::intervalo(
            $periodo,
            Entrada::texto($_GET['data_inicio'] ?? ''),
            Entrada::texto($_GET['data_fim'] ?? ''),
            date('Y-m-d')
        );

        $consultas = new RelatorioConsultas();
        $movimentacoes = $consultas->movimentacoesDoPeriodo($inicio, $fim);

        if ($tipo === 'financeiro') {
            $this->json(true, dados: RelatorioFinanceiro::montar($movimentacoes, $periodo, $inicio, $fim));
        }
        $this->json(true, dados: RelatorioEstoque::montar($movimentacoes, $consultas->produtos(), $periodo, $inicio, $fim));
    }
}
