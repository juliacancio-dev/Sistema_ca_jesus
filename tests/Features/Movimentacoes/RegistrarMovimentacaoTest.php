<?php

namespace App\Tests\Features\Movimentacoes;

use App\Core\ConfirmacaoNecessaria;
use App\Core\ErroDeNegocio;
use App\Features\Movimentacoes\RegistrarMovimentacao;
use App\Tests\BancoDeTeste;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RegistrarMovimentacaoTest extends TestCase
{
    private const MARCA = 'Galão 20 L - Teste';

    private static PDO $pdo;
    private RegistrarMovimentacao $registrar;
    private int $produtoId;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = BancoDeTeste::recriar();
        self::$pdo->exec("INSERT INTO tbfornecedores (nome_fornecedor, telefone_fornecedor, cnpj_fornecedor, email_fornecedor, cep_fornecedor)
                          VALUES ('Fornecedor', '1733334444', '80.883.391/0001-79', 'f@teste.com', '15130-971')");
        self::$pdo->exec("INSERT INTO tbusuarios (tipo_usuario, nome_usuario, email_usuario, senha_usuario, cpf_usuario, rg_usuario, telefone_usuario, sexo_usuario, cep_usuario)
                          VALUES (1, 'Usuária', 'u@teste.com', 'x', '94405576807', '1', '17999862135', 'Feminino', '15130-067')");
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM tb_auditoria');
        self::$pdo->exec('DELETE FROM tbmovimentacoes');
        self::$pdo->exec('DELETE FROM tbprodutos');
        $stmt = self::$pdo->prepare('INSERT INTO tbprodutos (marca_produto, preco_custo_produto, preco_venda_produto, estoque_produto, fornecedor_id) VALUES (?, 10, 20, 10, 1)');
        $stmt->execute([self::MARCA]);
        $this->produtoId = (int)self::$pdo->lastInsertId();
        $this->registrar = new RegistrarMovimentacao(self::$pdo);
    }

    public function testEntradaSomaAoSaldoEGravaAMovimentacao(): void
    {
        $saldo = $this->registrar->executar($this->produtoId, 'entrada', 5, 10.0, 20.0, 'Compra', 1);

        $this->assertSame(15, $saldo);
        $this->assertSame(15, $this->saldo());

        $mov = self::$pdo->query('SELECT * FROM tbmovimentacoes')->fetch();
        $this->assertSame('entrada', $mov['acao_movimentacao']);
        $this->assertSame(5, (int)$mov['quantidade_movimentacao']);
        $this->assertSame('10.00', $mov['preco_custo_movimentacao']);
        $this->assertSame('20.00', $mov['preco_venda_movimentacao']);
        $this->assertSame('Compra', $mov['observacao_movimentacao']);
        $this->assertSame(1, (int)$mov['usuario_id']);
        $this->assertSame('0.00', $mov['lucro_movimentacao']);
    }

    public function testSaidaSubtraiDoSaldoECalculaOLucro(): void
    {
        $saldo = $this->registrar->executar($this->produtoId, 'saida', 4, 10.0, 20.0, null, 1);

        $this->assertSame(6, $saldo);
        $this->assertSame(6, $this->saldo());
        $this->assertSame('40.00', self::$pdo->query('SELECT lucro_movimentacao FROM tbmovimentacoes')->fetchColumn());
    }

    public function testSaidaDeTodoOEstoqueZeraOSaldo(): void
    {
        $this->assertSame(0, $this->registrar->executar($this->produtoId, 'saida', 10, 10.0, 20.0, null, 1));
    }

    public function testSaidaAcimaDoSaldoERecusadaENadaMuda(): void
    {
        try {
            $this->registrar->executar($this->produtoId, 'saida', 11, 10.0, 20.0, null, 1);
            $this->fail('Deveria ter recusado.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame('Quantidade de saída maior que o estoque disponível (10).', $e->getMessage());
            $this->assertSame(422, $e->status());
        }
        $this->assertSame(10, $this->saldo());
        $this->assertSame(0, $this->totalDeMovimentacoes());
        $this->assertFalse(self::$pdo->inTransaction());
    }

    public function testSaidaDeProdutoZeradoERecusada(): void
    {
        self::$pdo->exec('UPDATE tbprodutos SET estoque_produto = 0');
        try {
            $this->registrar->executar($this->produtoId, 'saida', 1, 10.0, 20.0, null, 1);
            $this->fail('Deveria ter recusado.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame('Estoque do produto está zerado. Não é possível registrar saída.', $e->getMessage());
        }
        $this->assertSame(0, $this->saldo());
        $this->assertSame(0, $this->totalDeMovimentacoes());
    }

    public function testProdutoInexistenteERecusado(): void
    {
        try {
            $this->registrar->executar(999999, 'entrada', 1, 10.0, 20.0, null, 1);
            $this->fail('Deveria ter recusado.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame(404, $e->status());
        }
        $this->assertFalse(self::$pdo->inTransaction());
    }

    public static function dadosInvalidos(): array
    {
        return [
            'sem produto' => [0, 'entrada', 1, 10.0, 20.0],
            'tipo inválido' => [null, 'ajuste', 1, 10.0, 20.0],
            'quantidade zero' => [null, 'entrada', 0, 10.0, 20.0],
            'quantidade negativa' => [null, 'saida', -1, 10.0, 20.0],
            'custo negativo' => [null, 'entrada', 1, -1.0, 20.0],
            'venda negativa' => [null, 'entrada', 1, 10.0, -1.0],
        ];
    }

    #[DataProvider('dadosInvalidos')]
    public function testDadosInvalidosSaoRecusados(?int $produto, string $acao, int $quantidade, float $custo, float $venda): void
    {
        try {
            $this->registrar->executar($produto ?? $this->produtoId, $acao, $quantidade, $custo, $venda, null, 1);
            $this->fail('Deveria ter recusado.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame(422, $e->status());
        }
        $this->assertSame(10, $this->saldo());
        $this->assertSame(0, $this->totalDeMovimentacoes());
    }

    public static function acoes(): array
    {
        return ['entrada' => ['entrada', 12], 'saida' => ['saida', 8]];
    }

    #[DataProvider('acoes')]
    public function testCustoMaiorQueVendaPedeConfirmacaoENadaMuda(string $acao, int $saldoComConfirmacao): void
    {
        try {
            $this->registrar->executar($this->produtoId, $acao, 2, 30.0, 20.0, null, 1);
            $this->fail('Deveria ter pedido confirmação.');
        } catch (ConfirmacaoNecessaria $e) {
            $this->assertSame(200, $e->status());
        }
        $this->assertSame(10, $this->saldo());
        $this->assertSame(0, $this->totalDeMovimentacoes());
    }

    #[DataProvider('acoes')]
    public function testCustoMaiorQueVendaPassaComConfirmacao(string $acao, int $saldoComConfirmacao): void
    {
        $this->assertSame($saldoComConfirmacao, $this->registrar->executar($this->produtoId, $acao, 2, 30.0, 20.0, null, 1, true));
    }

    public function testConfirmacaoNaoLiberaSaidaAcimaDoSaldo(): void
    {
        $this->expectException(ErroDeNegocio::class);
        $this->expectExceptionMessage('maior que o estoque');
        $this->registrar->executar($this->produtoId, 'saida', 11, 30.0, 20.0, null, 1, true);
    }

    public function testFalhaAoGravarDesfazTudo(): void
    {
        try {
            // usuario_id inexistente viola a FK ao gravar a movimentação.
            $this->registrar->executar($this->produtoId, 'entrada', 5, 10.0, 20.0, null, 999999);
            $this->fail('Deveria ter falhado.');
        } catch (PDOException) {
        }
        $this->assertFalse(self::$pdo->inTransaction());
        $this->assertSame(10, $this->saldo());
        $this->assertSame(0, $this->totalDeMovimentacoes());
    }

    public function testEsperaOLockDoProduto(): void
    {
        $outraConexao = BancoDeTeste::conectar();
        $outraConexao->beginTransaction();
        $outraConexao->query('SELECT id_produto FROM tbprodutos FOR UPDATE')->fetchAll();

        self::$pdo->exec('SET innodb_lock_wait_timeout = 1');
        try {
            $this->registrar->executar($this->produtoId, 'saida', 1, 10.0, 20.0, null, 1);
            $this->fail('Deveria ter esperado o lock da outra conexão.');
        } catch (PDOException $e) {
            $this->assertSame(1205, $e->errorInfo[1]);
        } finally {
            $outraConexao->rollBack();
            self::$pdo->exec('SET innodb_lock_wait_timeout = 50');
        }
        $this->assertSame(10, $this->saldo());
    }

    public function testRenomearOProdutoNaoAfetaAsMovimentacoes(): void
    {
        $this->registrar->executar($this->produtoId, 'entrada', 5, 10.0, 20.0, null, 1);

        self::$pdo->exec("UPDATE tbprodutos SET marca_produto = 'Nome Novo'");

        $this->assertSame(1, $this->totalDeMovimentacoes());
        $this->assertSame($this->produtoId, (int)self::$pdo->query('SELECT produto_id FROM tbmovimentacoes')->fetchColumn());
        $this->assertSame(15, $this->saldo());
    }

    public function testNaoExcluiProdutoComMovimentacao(): void
    {
        $this->registrar->executar($this->produtoId, 'entrada', 5, 10.0, 20.0, null, 1);

        $this->expectException(PDOException::class);
        self::$pdo->exec('DELETE FROM tbprodutos');
    }

    public function testAuditoriaRegistraAMudancaDeSaldo(): void
    {
        $this->registrar->executar($this->produtoId, 'saida', 3, 10.0, 20.0, null, 1);

        $auditoria = self::$pdo->query('SELECT * FROM tb_auditoria')->fetchAll();
        $this->assertCount(1, $auditoria);
        $this->assertSame(10, json_decode($auditoria[0]['dados_anteriores'], true)['estoque_produto']);
        $this->assertSame(7, json_decode($auditoria[0]['dados_novos'], true)['estoque_produto']);
    }

    public function testBancoRecusaSaldoNegativo(): void
    {
        $this->expectException(PDOException::class);
        self::$pdo->exec('UPDATE tbprodutos SET estoque_produto = -1');
    }

    public function testSoOTriggerDeAuditoriaExiste(): void
    {
        $triggers = array_column(self::$pdo->query('SHOW TRIGGERS')->fetchAll(), 'Trigger');
        $this->assertSame(['tr_auditoria_estoque'], $triggers);
    }

    private function saldo(): int
    {
        return (int)self::$pdo->query('SELECT estoque_produto FROM tbprodutos')->fetchColumn();
    }

    private function totalDeMovimentacoes(): int
    {
        return (int)self::$pdo->query('SELECT COUNT(*) FROM tbmovimentacoes')->fetchColumn();
    }
}
