<?php

namespace App\Features\Movimentacoes;

use App\Core\ConfirmacaoNecessaria;
use App\Core\ErroDeNegocio;
use PDO;
use Throwable;

// Única regra que escreve em tbprodutos.estoque_produto.
final class RegistrarMovimentacao
{
    public function __construct(private PDO $db)
    {
    }

    /** @return int o saldo do produto depois da movimentação */
    public function executar(
        string $marca,
        string $acao,
        int $quantidade,
        float $precoCusto,
        float $precoVenda,
        ?string $observacao,
        ?int $usuarioId,
        bool $custoMaiorConfirmado = false
    ): int {
        if ($marca === '' || !in_array($acao, ['entrada', 'saida'], true) || $quantidade <= 0 || $precoCusto < 0 || $precoVenda < 0) {
            throw new ErroDeNegocio('Preencha todos os campos obrigatórios corretamente.');
        }

        $this->db->beginTransaction();
        try {
            $saldo = $this->travarSaldo($marca);

            if ($acao === 'saida' && $saldo <= 0) {
                throw new ErroDeNegocio('Estoque do produto está zerado. Não é possível registrar saída.');
            }
            if ($acao === 'saida' && $quantidade > $saldo) {
                throw new ErroDeNegocio("Quantidade de saída maior que o estoque disponível ($saldo).");
            }
            // Depois do saldo: não faz sentido pedir confirmação de algo que será recusado.
            if ($precoCusto > $precoVenda && !$custoMaiorConfirmado) {
                throw new ConfirmacaoNecessaria('O preço de custo é maior que o preço de venda. Tem certeza de que deseja registrar?');
            }

            // lucro_movimentacao é coluna calculada pelo banco (VIRTUAL); não é enviada.
            $this->db->prepare('
                INSERT INTO tbmovimentacoes (
                    marca_produto,
                    acao_movimentacao,
                    quantidade_movimentacao,
                    preco_custo_movimentacao,
                    preco_venda_movimentacao,
                    observacao_movimentacao,
                    usuario_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ')->execute([$marca, $acao, $quantidade, $precoCusto, $precoVenda, $observacao, $usuarioId]);

            $novoSaldo = $acao === 'entrada' ? $saldo + $quantidade : $saldo - $quantidade;
            $this->db->prepare('UPDATE tbprodutos SET estoque_produto = ? WHERE marca_produto = ?')
                ->execute([$novoSaldo, $marca]);

            $this->db->commit();
            return $novoSaldo;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // FOR UPDATE: duas saídas simultâneas do mesmo produto esperam uma pela outra,
    // então a segunda já enxerga o saldo atualizado pela primeira.
    private function travarSaldo(string $marca): int
    {
        $stmt = $this->db->prepare('SELECT estoque_produto FROM tbprodutos WHERE marca_produto = ? FOR UPDATE');
        $stmt->execute([$marca]);
        $saldo = $stmt->fetchColumn();

        if ($saldo === false) {
            throw new ErroDeNegocio('Produto não encontrado.', 404);
        }
        return (int)$saldo;
    }
}
