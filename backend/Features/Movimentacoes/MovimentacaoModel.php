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

    public function buscarPorPeriodo($dataInicio, $dataFim) {
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
            WHERE DATE(m.data_movimentacao) BETWEEN ? AND ?
            ORDER BY m.data_movimentacao DESC
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMovimentacoesHoje() {
        $hoje = date("Y-m-d");
        return $this->buscarPorPeriodo($hoje, $hoje);
    }

    public function getLucroTotal() {
        $stmt = $this->db->prepare("SELECT SUM(lucro_movimentacao) as total FROM tbmovimentacoes");
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
    }

    public function getMovimentacoesUltimos7Dias() {
        $stmt = $this->db->prepare("
            SELECT 
                acao_movimentacao AS tipo,
                quantidade_movimentacao AS quantidade,
                data_movimentacao AS data
            FROM tbmovimentacoes
            WHERE data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalValorSaidas() {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(preco_venda_movimentacao * quantidade_movimentacao), 0) AS total FROM tbmovimentacoes WHERE acao_movimentacao = 'saida'");
        $stmt->execute();
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function getTotalValorEntradas() {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(preco_custo_movimentacao * quantidade_movimentacao), 0) AS total FROM tbmovimentacoes WHERE acao_movimentacao = 'entrada'");
        $stmt->execute();
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function getSaldoFinanceiro() {
        $saidas = $this->getTotalValorSaidas();
        $entradas = $this->getTotalValorEntradas();
        return $saidas - $entradas;
    }
}
