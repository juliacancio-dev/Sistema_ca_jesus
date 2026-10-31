<?php

namespace App\Tests\Features\Relatorios;

use App\Features\Relatorios\RelatorioFinanceiro;
use PHPUnit\Framework\TestCase;

final class RelatorioFinanceiroTest extends TestCase
{
    private function mov(string $data, string $acao, float $qtd, float $custo, float $venda, string $produto = 'Galão'): array
    {
        return ['data' => "$data 10:00:00", 'acao' => $acao, 'quantidade' => $qtd, 'preco_custo' => $custo, 'preco_venda' => $venda, 'produto' => $produto];
    }

    public function testSemMovimentacoesTudoEZeroMasASerieCobreOPeriodo(): void
    {
        $r = RelatorioFinanceiro::montar([], 'mensal', '2026-10-01', '2026-10-03');

        $this->assertSame('financeiro', $r['tipo']);
        $this->assertSame(0.0, $r['resumo']['receitas']);
        $this->assertSame(0.0, $r['resumo']['despesas']);
        $this->assertSame(0.0, $r['resumo']['lucro']);
        $this->assertSame(0.0, $r['resumo']['saldo_final']);
        $this->assertSame([], $r['entradas_detalhadas']);
        $this->assertSame([], $r['saidas_detalhadas']);
        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03'], array_column($r['series']['receitas'], 'data'));
        $this->assertSame([0.0, 0.0, 0.0], array_column($r['series']['despesas'], 'valor'));
    }

    public function testReceitaDespesaLucroESaldo(): void
    {
        $r = RelatorioFinanceiro::montar([
            $this->mov('2026-10-01', 'saida', 2, 10, 25),    // receita 50, custo vendido 20
            $this->mov('2026-10-02', 'saida', 1, 10, 30),    // receita 30, custo vendido 10
            $this->mov('2026-10-02', 'entrada', 5, 12, 25),  // despesa 60; preço de venda não conta
        ], 'mensal', '2026-10-01', '2026-10-02');

        $this->assertEqualsWithDelta(80.0, $r['resumo']['receitas'], 0.001);
        $this->assertEqualsWithDelta(60.0, $r['resumo']['despesas'], 0.001);
        $this->assertEqualsWithDelta(50.0, $r['resumo']['lucro'], 0.001);       // 80 - 30, não 80 - 60
        $this->assertEqualsWithDelta(20.0, $r['resumo']['saldo_final'], 0.001); // 80 - 60
        $this->assertSame($r['resumo']['receitas'], $r['resumo']['total_receitas']);
        $this->assertSame($r['resumo']['despesas'], $r['resumo']['total_despesas']);
    }

    public function testVendaAbaixoDoCustoDaLucroNegativo(): void
    {
        $r = RelatorioFinanceiro::montar([$this->mov('2026-10-01', 'saida', 2, 30, 20)], 'mensal', '2026-10-01', '2026-10-01');

        $this->assertEqualsWithDelta(-20.0, $r['resumo']['lucro'], 0.001);
    }

    public function testDetalhesSeparamReceitasDeDespesas(): void
    {
        $r = RelatorioFinanceiro::montar([
            $this->mov('2026-10-01', 'saida', 2, 10, 25, 'Vendido'),
            $this->mov('2026-10-01', 'entrada', 1, 12, 25, 'Comprado'),
        ], 'mensal', '2026-10-01', '2026-10-01');

        // "entradas" aqui é entrada de dinheiro: vem das saídas de estoque.
        $this->assertSame('Vendido', $r['entradas_detalhadas'][0]['descricao']);
        $this->assertEqualsWithDelta(50.0, $r['entradas_detalhadas'][0]['valor'], 0.001);
        $this->assertSame('Comprado', $r['saidas_detalhadas'][0]['descricao']);
        $this->assertEqualsWithDelta(12.0, $r['saidas_detalhadas'][0]['valor'], 0.001);
    }

    public function testSerieSomaPorDia(): void
    {
        $r = RelatorioFinanceiro::montar([
            $this->mov('2026-10-01', 'saida', 1, 10, 20),
            $this->mov('2026-10-01', 'saida', 1, 10, 30),
            $this->mov('2026-10-03', 'entrada', 2, 10, 20),
        ], 'mensal', '2026-10-01', '2026-10-03');

        $this->assertEquals([50, 0, 0], array_column($r['series']['receitas'], 'valor'));
        $this->assertEquals([0, 0, 20], array_column($r['series']['despesas'], 'valor'));
    }

    public function testInformaOPeriodo(): void
    {
        $r = RelatorioFinanceiro::montar([], 'semanal', '2026-10-01', '2026-10-07');

        $this->assertSame('semanal', $r['periodo']);
        $this->assertSame('2026-10-01', $r['data_inicio']);
        $this->assertSame('2026-10-07', $r['data_fim']);
    }
}
