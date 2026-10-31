<?php

namespace App\Features\Relatorios;

use App\Core\Database;
use PDO;

// Leituras próprias: quem escreve nessas tabelas é Movimentações e Produtos.
class RelatorioConsultas
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function movimentacoesDoPeriodo(string $inicio, string $fim): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.marca_produto AS produto,
                m.data_movimentacao AS data,
                m.acao_movimentacao AS acao,
                m.quantidade_movimentacao AS quantidade,
                m.preco_custo_movimentacao AS preco_custo,
                m.preco_venda_movimentacao AS preco_venda,
                m.observacao_movimentacao AS observacao
            FROM tbmovimentacoes m
            JOIN tbprodutos p ON m.produto_id = p.id_produto
            WHERE DATE(m.data_movimentacao) BETWEEN ? AND ?
            ORDER BY m.data_movimentacao DESC
        ");
        $stmt->execute([$inicio, $fim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function produtos(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id_produto AS id,
                marca_produto AS marca,
                estoque_produto AS estoque
            FROM tbprodutos
            ORDER BY marca_produto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
