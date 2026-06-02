<?php

class ProdutoModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function listarComFornecedor() {
        $stmt = $this->db->prepare("
            SELECT 
                p.id_produto AS id,
                p.marca_produto AS marca,
                p.preco_custo_produto AS preco_custo,
                p.preco_venda_produto AS preco_venda,
                p.estoque_produto AS estoque,
                f.nome_fornecedor AS fornecedor,
                p.fornecedor_id
            FROM tbprodutos p
            LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor
            ORDER BY p.marca_produto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("
            SELECT 
                id_produto AS id,
                marca_produto AS marca,
                preco_custo_produto AS preco_custo,
                preco_venda_produto AS preco_venda,
                estoque_produto AS estoque,
                fornecedor_id
            FROM tbprodutos 
            WHERE id_produto = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function salvar($dados) {
        if (empty($dados['id'])) {
            return $this->criar($dados);
        } else {
            return $this->atualizar($dados['id'], $dados);
        }
    }

    private function criar($dados) {
        $stmt = $this->db->prepare("
            INSERT INTO tbprodutos (
                marca_produto, 
                preco_custo_produto, 
                preco_venda_produto, 
                fornecedor_id,
                estoque_produto
            ) VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $dados['marca'],
            $dados['preco_custo'],
            $dados['preco_venda'],
            $dados['fornecedor_id'],
            $dados['estoque'] ?? 0
        ]);
    }

    private function atualizar($id, $dados) {
        $stmt = $this->db->prepare("
            UPDATE tbprodutos 
            SET 
                marca_produto = ?,
                preco_custo_produto = ?,
                preco_venda_produto = ?,
                fornecedor_id = ?
            WHERE id_produto = ?
        ");
        return $stmt->execute([
            $dados['marca'],
            $dados['preco_custo'],
            $dados['preco_venda'],
            $dados['fornecedor_id'],
            $id
        ]);
    }

    public function deletar($id) {
        $stmt = $this->db->prepare("DELETE FROM tbprodutos WHERE id_produto = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function buscar($termo) {
        $stmt = $this->db->prepare("
            SELECT 
                p.id_produto AS id,
                p.marca_produto AS marca,
                p.preco_custo_produto AS preco_custo,
                p.preco_venda_produto AS preco_venda,
                p.estoque_produto AS estoque,
                f.nome_fornecedor AS fornecedor,
                p.fornecedor_id
            FROM tbprodutos p
            LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor
            WHERE p.marca_produto LIKE :termo OR f.nome_fornecedor LIKE :termo
            ORDER BY p.marca_produto ASC
        ");
        $termo = '%' . $termo . '%';
        $stmt->bindParam(':termo', $termo);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTodos() {
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

    public function getBaixoEstoque($limite = 10) {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM tbprodutos 
            WHERE estoque_produto < ? 
            ORDER BY estoque_produto ASC
        ");
        $stmt->execute([$limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function contarTotal() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbprodutos");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function atualizarSomenteNome($id, $novoNome) {
        $stmt = $this->db->prepare("\n            UPDATE tbprodutos \n            SET marca_produto = ? \n            WHERE id_produto = ?\n        ");
        return $stmt->execute([$novoNome, $id]);
    }

    public function contarPorFornecedor($fornecedorId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tbprodutos WHERE fornecedor_id = ?");
        $stmt->execute([$fornecedorId]);
        return (int)$stmt->fetchColumn();
    }
}