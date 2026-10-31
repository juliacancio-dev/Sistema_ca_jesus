<?php

namespace App\Tests\Features\Dashboard;

use App\Features\Dashboard\DashboardConsultas;
use App\Tests\BancoDeTeste;
use PDO;
use PHPUnit\Framework\TestCase;

final class DashboardConsultasTest extends TestCase
{
    private static PDO $pdo;
    private DashboardConsultas $consultas;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = BancoDeTeste::recriar();
        self::$pdo->exec("INSERT INTO tbfornecedores (nome_fornecedor, telefone_fornecedor, cnpj_fornecedor, email_fornecedor, cep_fornecedor)
                          VALUES ('Fornecedor', '1733334444', '80.883.391/0001-79', 'f@teste.com', '15130-971')");
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM tbmovimentacoes');
        self::$pdo->exec('DELETE FROM tbprodutos');
        $this->consultas = new DashboardConsultas(self::$pdo);
    }

    private function produto(string $marca, int $estoque): void
    {
        self::$pdo->prepare('INSERT INTO tbprodutos (marca_produto, preco_custo_produto, preco_venda_produto, estoque_produto, fornecedor_id) VALUES (?, 10, 20, ?, 1)')
            ->execute([$marca, $estoque]);
    }

    private function mov(string $marca, string $data, string $acao, int $qtd, float $custo = 10, float $venda = 20): void
    {
        self::$pdo->prepare('INSERT INTO tbmovimentacoes (produto_id, data_movimentacao, acao_movimentacao, quantidade_movimentacao, preco_custo_movimentacao, preco_venda_movimentacao) VALUES ((SELECT id_produto FROM tbprodutos WHERE marca_produto = ?), ?, ?, ?, ?, ?)')
            ->execute([$marca, $data, $acao, $qtd, $custo, $venda]);
    }

    public function testBancoVazioDaZeroEListasVazias(): void
    {
        $this->assertSame(0, $this->consultas->totalDeProdutos());
        $this->assertSame(0, $this->consultas->estoqueTotal());
        $this->assertSame(0, $this->consultas->movimentacoesDoDia('2026-10-31'));
        $this->assertSame(0.0, $this->consultas->saldoFinanceiro());
        $this->assertSame([], $this->consultas->atividadesRecentes());
        $this->assertSame([], $this->consultas->movimentacoesDosUltimos7Dias());
        $this->assertSame([], $this->consultas->estoquePorProduto());
    }

    public function testTotaisDeProdutosEEstoque(): void
    {
        $this->produto('A', 10);
        $this->produto('B', 5);

        $this->assertSame(2, $this->consultas->totalDeProdutos());
        $this->assertSame(15, $this->consultas->estoqueTotal());
    }

    public function testEstoquePorProdutoEmOrdemDeMarca(): void
    {
        $this->produto('B', 5);
        $this->produto('A', 10);

        $this->assertSame(['A', 'B'], array_column($this->consultas->estoquePorProduto(), 'marca'));
    }

    public function testMovimentacoesDoDiaIgnoramOsOutrosDias(): void
    {
        $this->produto('A', 0);
        $this->mov('A', '2026-10-31 00:00:00', 'entrada', 1);
        $this->mov('A', '2026-10-31 23:59:59', 'saida', 1);
        $this->mov('A', '2026-10-30 23:59:59', 'entrada', 1);

        $this->assertSame(2, $this->consultas->movimentacoesDoDia('2026-10-31'));
    }

    public function testSaldoEVendasMenosCompras(): void
    {
        $this->produto('A', 0);
        $this->mov('A', '2026-10-01 10:00:00', 'saida', 2, 10, 25);   // +50
        $this->mov('A', '2026-10-01 10:00:00', 'entrada', 3, 12, 25); // -36

        $this->assertEqualsWithDelta(14.0, $this->consultas->saldoFinanceiro(), 0.001);
    }

    public function testAtividadesTrazemAsMaisRecentesPrimeiroELimitadas(): void
    {
        $this->produto('A', 0);
        for ($dia = 1; $dia <= 7; $dia++) {
            $this->mov('A', "2026-10-0$dia 10:00:00", 'entrada', $dia);
        }

        $atividades = $this->consultas->atividadesRecentes();

        $this->assertSame([7, 6, 5, 4, 3], array_map('intval', array_column($atividades, 'quantidade')));
    }

    public function testAtividadesNoMesmoSegundoSaemDaUltimaParaAPrimeira(): void
    {
        $this->produto('A', 0);
        $this->mov('A', '2026-10-01 10:00:00', 'entrada', 1);
        $this->mov('A', '2026-10-01 10:00:00', 'saida', 2);

        $this->assertSame([2, 1], array_map('intval', array_column($this->consultas->atividadesRecentes(), 'quantidade')));
    }

    public function testAtividadesMostramONomeAtualDoProduto(): void
    {
        $this->produto('A', 0);
        $this->mov('A', '2026-10-01 10:00:00', 'entrada', 1);
        self::$pdo->exec("UPDATE tbprodutos SET marca_produto = 'Nome Novo'");

        $this->assertSame('Nome Novo', $this->consultas->atividadesRecentes()[0]['produto']);
    }

    public function testUltimos7DiasCobremHojeESeisDiasAtras(): void
    {
        $this->produto('A', 0);
        self::$pdo->exec("INSERT INTO tbmovimentacoes (produto_id, data_movimentacao, acao_movimentacao, quantidade_movimentacao, preco_custo_movimentacao, preco_venda_movimentacao) VALUES
            ((SELECT id_produto FROM tbprodutos WHERE marca_produto = 'A'), NOW(), 'entrada', 1, 10, 20),
            ((SELECT id_produto FROM tbprodutos WHERE marca_produto = 'A'), CURDATE() - INTERVAL 6 DAY, 'entrada', 2, 10, 20),
            ((SELECT id_produto FROM tbprodutos WHERE marca_produto = 'A'), CURDATE() - INTERVAL 7 DAY, 'entrada', 3, 10, 20)");

        $quantidades = array_map('intval', array_column($this->consultas->movimentacoesDosUltimos7Dias(), 'quantidade'));
        sort($quantidades);

        $this->assertSame([1, 2], $quantidades);
    }
}
