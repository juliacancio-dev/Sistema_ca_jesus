<?php

namespace App\Features\Relatorios;

// Cálculo puro: receita = venda x qtd das saídas; despesa = custo x qtd das entradas;
// lucro = receita - custo do que foi vendido; saldo = receita - despesa.
final class RelatorioFinanceiro
{
    public static function montar(array $movimentacoes, string $periodo, string $inicio, string $fim): array
    {
        $receitas = 0.0;
        $custosVendidos = 0.0;
        $despesasCompras = 0.0;

        $entradasDetalhadas = [];
        $saidasDetalhadas = [];

        $serieReceitas = [];
        $serieDespesas = [];

        $mapReceitasDia = [];
        $mapDespesasDia = [];

        foreach ($movimentacoes as $m) {
            $acao = $m['acao'] ?? '';
            $qtd = (float)($m['quantidade'] ?? 0);
            $pv = (float)($m['preco_venda'] ?? 0);
            $pc = (float)($m['preco_custo'] ?? 0);
            $data = substr($m['data'], 0, 10);
            $produto = $m['produto'] ?? '-';

            if ($acao === 'saida') {
                $valorVenda = $pv * $qtd;
                $valorCusto = $pc * $qtd;
                $receitas += $valorVenda;
                $custosVendidos += $valorCusto;
                $entradasDetalhadas[] = [
                    'data' => $m['data'],
                    'descricao' => $produto,
                    'valor' => $valorVenda
                ];
                $mapReceitasDia[$data] = ($mapReceitasDia[$data] ?? 0) + $valorVenda;
            } elseif ($acao === 'entrada') {
                $valorCompra = $pc * $qtd;
                $despesasCompras += $valorCompra;
                $saidasDetalhadas[] = [
                    'data' => $m['data'],
                    'descricao' => $produto,
                    'valor' => $valorCompra,
                ];
                $mapDespesasDia[$data] = ($mapDespesasDia[$data] ?? 0) + $valorCompra;
            }
        }

        $lucro = $receitas - $custosVendidos;
        $saldoFinal = $receitas - $despesasCompras;

        $cursor = strtotime($inicio);
        $end = strtotime($fim);
        while ($cursor <= $end) {
            $d = date('Y-m-d', $cursor);
            $serieReceitas[] = ['data' => $d, 'valor' => (float)($mapReceitasDia[$d] ?? 0)];
            $serieDespesas[] = ['data' => $d, 'valor' => (float)($mapDespesasDia[$d] ?? 0)];
            $cursor = strtotime('+1 day', $cursor);
        }

        return [
            'tipo' => 'financeiro',
            'periodo' => $periodo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'resumo' => [
                'receitas' => $receitas,
                'despesas' => $despesasCompras,
                'total_receitas' => $receitas,
                'total_despesas' => $despesasCompras,
                'saldo_final' => $saldoFinal,
                'lucro' => $lucro
            ],
            'entradas_detalhadas' => $entradasDetalhadas,
            'saidas_detalhadas' => $saidasDetalhadas,
            'series' => [
                'receitas' => $serieReceitas,
                'despesas' => $serieDespesas
            ]
        ];
    }
}
