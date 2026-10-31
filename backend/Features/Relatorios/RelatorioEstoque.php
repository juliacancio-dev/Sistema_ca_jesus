<?php

namespace App\Features\Relatorios;

// Cálculo puro: histórico do período, saldo atual e produtos mais vendidos.
final class RelatorioEstoque
{
    public static function montar(array $movimentacoes, array $produtos, string $periodo, string $inicio, string $fim): array
    {
        $totalEstoque = 0;
        foreach ($produtos as $p) {
            $totalEstoque += (int)($p['estoque'] ?? 0);
        }

        $entradasEstoque = [];
        $saidasEstoque = [];
        $historico = [];
        $mapSaidasPorProduto = [];

        foreach ($movimentacoes as $m) {
            $acao = $m['acao'] ?? '';
            $qtd = (int)($m['quantidade'] ?? 0);
            $produto = $m['produto'] ?? '-';
            $reg = [
                'data' => $m['data'],
                'produto' => $produto,
                'tipo' => $acao,
                'quantidade' => $qtd,
                'observacao' => $m['observacao'] ?? ''
            ];

            $historico[] = $reg;
            if ($acao === 'entrada') $entradasEstoque[] = $reg;
            if ($acao === 'saida') {
                $saidasEstoque[] = $reg;
                $mapSaidasPorProduto[$produto] = ($mapSaidasPorProduto[$produto] ?? 0) + $qtd;
            }
        }

        arsort($mapSaidasPorProduto);
        $produtosMaisVendidos = [];
        foreach (array_slice($mapSaidasPorProduto, 0, 10, true) as $produto => $qtd) {
            $produtosMaisVendidos[] = ['produto' => $produto, 'quantidade' => $qtd];
        }

        $listaProdutos = array_map(function ($p) {
            return [
                'nome' => $p['marca'] ?? ($p['marca_produto'] ?? ''),
                'codigo' => $p['id'],
                'estoque_atual' => $p['estoque'] ?? ($p['estoque_produto'] ?? 0)
            ];
        }, $produtos);

        return [
            'tipo' => 'estoque',
            'periodo' => $periodo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'resumo' => [
                'quantidade_total_estoque' => $totalEstoque
            ],
            'produtos' => $listaProdutos,
            'entradas_estoque' => $entradasEstoque,
            'saidas_estoque' => $saidasEstoque,
            'historico_movimentacoes' => $historico,
            'produtos_mais_vendidos' => $produtosMaisVendidos
        ];
    }
}
