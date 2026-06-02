<?php

class RelatorioModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function buscarMovimentacoes($filtros = []) {
        $where = [];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[] = 'DATE(m.data_movimentacao) >= ?';
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = 'DATE(m.data_movimentacao) <= ?';
            $params[] = $filtros['data_fim'];
        }
        if (!empty($filtros['produto'])) {
            $where[] = 'm.marca_produto = ?';
            $params[] = $filtros['produto'];
        }
        if (!empty($filtros['fornecedor'])) {
            $where[] = 'p.fornecedor_id = ?';
            $params[] = $filtros['fornecedor'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = 'm.acao_movimentacao = ?';
            $params[] = $filtros['tipo'];
        }

        $sql = "SELECT m.*, p.marca_produto, p.fornecedor_id, f.nome_fornecedor, u.nome_usuario
                FROM tbmovimentacoes m
                LEFT JOIN tbprodutos p ON m.marca_produto = p.marca_produto
                LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor
                LEFT JOIN tbusuarios u ON m.usuario_id = u.id_usuario";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY m.data_movimentacao DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarProdutosEstoque() {
        $stmt = $this->db->prepare("SELECT p.*, f.nome_fornecedor FROM tbprodutos p LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor ORDER BY p.marca_produto ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarFornecedores() {
        $stmt = $this->db->prepare("SELECT * FROM tbfornecedores ORDER BY nome_fornecedor ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function gerarResumo($filtros = []) {
        $movs = $this->buscarMovimentacoes($filtros);
        $resumo = [
            'total_entradas' => 0,
            'total_saidas' => 0,
            'lucro_total' => 0,
            'movimentacoes' => $movs
        ];
        foreach ($movs as $m) {
            if ($m['acao_movimentacao'] === 'entrada') {
                $resumo['total_entradas'] += $m['quantidade_movimentacao'];
            } elseif ($m['acao_movimentacao'] === 'saida') {
                $resumo['total_saidas'] += $m['quantidade_movimentacao'];
                $resumo['lucro_total'] += $m['lucro_movimentacao'];
            }
        }
        return $resumo;
    }

    public function exportarCSV($dados) {
        if (empty($dados)) return '';
        $f = fopen('php://temp', 'r+');
        fputcsv($f, array_keys($dados[0]));
        foreach ($dados as $linha) {
            fputcsv($f, $linha);
        }
        rewind($f);
        $csv = stream_get_contents($f);
        fclose($f);
        return $csv;
    }

    public function getDadosAPI($filtros = []) {
        return $this->buscarMovimentacoes($filtros);
    }
}
