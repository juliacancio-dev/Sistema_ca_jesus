<?php

class MovimentacaoModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function registrar($dados) {
        try {
            $this->db->beginTransaction();

            $produto = $this->buscarProdutoPorMarca($dados["marca_produto"]);
            if (!$produto) {
                throw new Exception("Produto não encontrado");
            }

            if ($dados["acao"] === "saida" && $produto["estoque_produto"] < $dados["quantidade"]) {
                throw new Exception("Estoque insuficiente para esta operação");
            }

            $lucro = 0;
            if ($dados["acao"] === "saida") {
                $lucro = ($dados["preco_venda"] - $dados["preco_custo"]) * $dados["quantidade"];
            }

            $stmt = $this->db->prepare("
                INSERT INTO tbmovimentacoes (
                    marca_produto,
                    acao_movimentacao,
                    quantidade_movimentacao,
                    preco_custo_movimentacao,
                    preco_venda_movimentacao,
                    lucro_movimentacao,
                    observacao_movimentacao,
                    usuario_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ok = $stmt->execute([
                $dados["marca_produto"],
                $dados["acao"],
                $dados["quantidade"],
                $dados["preco_custo"],
                $dados["preco_venda"],
                $lucro,
                $dados["observacao"],
                $dados["usuario_id"]
            ]);

            $this->db->commit();
            return $ok;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function buscarProdutoPorMarca($marca) {
        $stmt = $this->db->prepare("
            SELECT id_produto, estoque_produto 
            FROM tbprodutos 
            WHERE marca_produto = ?
        ");
        $stmt->execute([$marca]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function atualizarEstoqueProduto($id, $estoque) {
        $stmt = $this->db->prepare("
            UPDATE tbprodutos 
            SET estoque_produto = ? 
            WHERE id_produto = ?
        ");
        return $stmt->execute([$estoque, $id]);
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

    public function getTotalEntradas() {
        $stmt = $this->db->prepare("
            SELECT SUM(quantidade_movimentacao) as total 
            FROM tbmovimentacoes 
            WHERE acao_movimentacao = \"entrada\"
        ");
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
    }

    public function getTotalSaidas() {
        $stmt = $this->db->prepare("
            SELECT SUM(quantidade_movimentacao) as total 
            FROM tbmovimentacoes 
            WHERE acao_movimentacao = \"saida\"
        ");
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
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
