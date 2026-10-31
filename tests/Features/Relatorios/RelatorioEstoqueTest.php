<?php

namespace App\Tests\Features\Relatorios;

use App\Features\Relatorios\RelatorioEstoque;
use PHPUnit\Framework\TestCase;

final class RelatorioEstoqueTest extends TestCase
{
    private function mov(string $acao, int $qtd, string $produto = 'Galão', string $obs = ''): array
    {
        return ['data' => '2026-10-01 10:00:00', 'acao' => $acao, 'quantidade' => $qtd, 'produto' => $produto, 'observacao' => $obs];
    }

    public function testTotalEListaDeProdutos(): void
    {
        $r = RelatorioEstoque::montar([], [
            ['id' => 1, 'marca' => 'Galão', 'estoque' => 10],
            ['id' => 2, 'marca' => 'Fardo', 'estoque' => 5],
        ], 'mensal', '2026-10-01', '2026-10-31');

        $this->assertSame('estoque', $r['tipo']);
        $this->assertSame(15, $r['resumo']['quantidade_total_estoque']);
        $this->assertSame(['nome' => 'Galão', 'codigo' => 1, 'estoque_atual' => 10], $r['produtos'][0]);
        $this->assertSame([], $r['historico_movimentacoes']);
        $this->assertSame([], $r['produtos_mais_vendidos']);
    }

    public function testSeparaEntradasESaidasEMantemOHistorico(): void
    {
        $r = RelatorioEstoque::montar([
            $this->mov('entrada', 5, 'Galão', 'Compra'),
            $this->mov('saida', 2, 'Fardo'),
        ], [], 'mensal', '2026-10-01', '2026-10-31');

        $this->assertCount(2, $r['historico_movimentacoes']);
        $this->assertCount(1, $r['entradas_estoque']);
        $this->assertCount(1, $r['saidas_estoque']);
        $this->assertSame('Compra', $r['entradas_estoque'][0]['observacao']);
        $this->assertSame('entrada', $r['entradas_estoque'][0]['tipo']);
        $this->assertSame('Fardo', $r['saidas_estoque'][0]['produto']);
    }

    public function testMaisVendidosSomaSaidasEOrdena(): void
    {
        $r = RelatorioEstoque::montar([
            $this->mov('saida', 2, 'Pouco'),
            $this->mov('saida', 3, 'Muito'),
            $this->mov('saida', 4, 'Muito'),
            $this->mov('entrada', 100, 'Só comprado'),
        ], [], 'mensal', '2026-10-01', '2026-10-31');

        $this->assertSame([
            ['produto' => 'Muito', 'quantidade' => 7],
            ['produto' => 'Pouco', 'quantidade' => 2],
        ], $r['produtos_mais_vendidos']);
    }

    public function testMaisVendidosTraz10NoMaximo(): void
    {
        $movimentacoes = [];
        for ($i = 1; $i <= 12; $i++) {
            $movimentacoes[] = $this->mov('saida', $i, "Produto $i");
        }

        $r = RelatorioEstoque::montar($movimentacoes, [], 'mensal', '2026-10-01', '2026-10-31');

        $this->assertCount(10, $r['produtos_mais_vendidos']);
        $this->assertSame('Produto 12', $r['produtos_mais_vendidos'][0]['produto']);
    }
}
