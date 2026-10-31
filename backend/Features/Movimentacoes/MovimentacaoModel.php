<?php

namespace App\Features\Movimentacoes;

use App\Core\Database;
use PDO;

class MovimentacaoModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Leitura própria: quem escreve no cadastro de tbprodutos é Produtos.
    public function listarProdutos() {
        $stmt = $this->db->prepare("
            SELECT
                id_produto AS id,
                marca_produto AS marca,
                preco_custo_produto AS preco_custo,
                preco_venda_produto AS preco_venda,
                estoque_produto AS estoque
            FROM tbprodutos
            ORDER BY marca_produto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodas() {
        $stmt = $this->db->prepare("
            SELECT 
                m.id_movimentacao AS id,
                m.marca_produto AS produto,
                m.data_movimentacao AS data,
                m.acao_movimentacao AS acao,
                m.quantidade_movimentacao AS quantidade,
                m.preco_custo_movimentacao AS preco_custo,
                m.preco_venda_movimentacao AS preco_venda,
                m.lucro_movimentacao AS lucro,
                m.observacao_movimentacao AS observacao,
                u.nome_usuario AS usuario
            FROM tbmovimentacoes m
            LEFT JOIN tbusuarios u ON m.usuario_id = u.id_usuario
            ORDER BY m.data_movimentacao DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
