<?php

namespace App\Features\Dashboard;

use PDO;

// Leituras próprias do dashboard; o banco agrega, em vez de carregar as tabelas inteiras.
class DashboardConsultas
{
    public function __construct(private PDO $db)
    {
    }

    public function totalDeProdutos(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM tbprodutos')->fetchColumn();
    }

    public function estoqueTotal(): int
    {
        return (int)$this->db->query('SELECT COALESCE(SUM(estoque_produto), 0) FROM tbprodutos')->fetchColumn();
    }

    public function movimentacoesDoDia(string $dia): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM tbmovimentacoes WHERE DATE(data_movimentacao) = ?');
        $stmt->execute([$dia]);
        return (int)$stmt->fetchColumn();
    }

    // Dinheiro que entrou nas vendas menos o que saiu nas compras.
    public function saldoFinanceiro(): float
    {
        return (float)$this->db->query("
            SELECT COALESCE(SUM(
                CASE acao_movimentacao
                    WHEN 'saida' THEN preco_venda_movimentacao * quantidade_movimentacao
                    ELSE -(preco_custo_movimentacao * quantidade_movimentacao)
                END
            ), 0)
            FROM tbmovimentacoes
        ")->fetchColumn();
    }

    public function atividadesRecentes(int $limite = 5): array
    {
        $stmt = $this->db->prepare('
            SELECT
                p.marca_produto AS produto,
                m.data_movimentacao AS data,
                m.acao_movimentacao AS acao,
                m.quantidade_movimentacao AS quantidade,
                m.preco_custo_movimentacao AS preco_custo,
                m.preco_venda_movimentacao AS preco_venda
            FROM tbmovimentacoes m
            JOIN tbprodutos p ON m.produto_id = p.id_produto
            ORDER BY m.data_movimentacao DESC, m.id_movimentacao DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function movimentacoesDosUltimos7Dias(): array
    {
        return $this->db->query('
            SELECT
                acao_movimentacao AS tipo,
                quantidade_movimentacao AS quantidade,
                data_movimentacao AS data
            FROM tbmovimentacoes
            WHERE data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function estoquePorProduto(): array
    {
        return $this->db->query('
            SELECT
                id_produto AS id,
                marca_produto AS marca,
                preco_custo_produto AS preco_custo,
                preco_venda_produto AS preco_venda,
                estoque_produto AS estoque
            FROM tbprodutos
            ORDER BY marca_produto ASC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }
}
