<?php

class EstoqueModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getBaixoEstoque($limite = 10)
    {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM tbprodutos 
            WHERE estoque_produto < :limite 
            ORDER BY estoque_produto ASC
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodosComEstoque()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, f.nome_fornecedor 
            FROM tbprodutos p 
            LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor 
            ORDER BY p.marca_produto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atualizarEstoque($idProduto, $quantidade, $operacao = 'soma')
    {
        $operador = ($operacao === 'soma') ? '+' : '-';

        $stmt = $this->db->prepare("
            UPDATE tbprodutos 
            SET estoque_produto = estoque_produto {$operador} :quantidade,
                data_atualizacao_produto = NOW()
            WHERE id_produto = :idProduto
        ");

        $stmt->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->bindValue(':idProduto', $idProduto, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getTotalEstoque()
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(estoque_produto), 0) AS total 
            FROM tbprodutos
        ");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getEstoquePorProduto($idProduto)
    {
        $stmt = $this->db->prepare("
            SELECT estoque_produto 
            FROM tbprodutos 
            WHERE id_produto = :idProduto
        ");
        $stmt->bindValue(':idProduto', $idProduto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
    }

    public function getProdutosComEstoquePorFornecedor($fornecedorId)
    {
        $stmt = $this->db->prepare("
            SELECT p.* 
            FROM tbprodutos p 
            WHERE p.fornecedor_id = :fornecedorId 
            AND p.estoque_produto > 0 
            ORDER BY p.marca_produto ASC
        ");
        $stmt->bindValue(':fornecedorId', $fornecedorId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistoricoPorProduto($idProduto)
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.nome_usuario 
            FROM tbmovimentacoes m
            INNER JOIN tbprodutos p ON m.marca_produto = p.marca_produto
            LEFT JOIN tbusuarios u ON m.usuario_id = u.id_usuario
            WHERE p.id_produto = :idProduto
            ORDER BY m.data_movimentacao DESC
        ");
        $stmt->bindValue(':idProduto', $idProduto, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstoqueCritico()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, f.nome_fornecedor 
            FROM tbprodutos p
            LEFT JOIN tbfornecedores f ON p.fornecedor_id = f.id_fornecedor
            WHERE p.estoque_produto < 5
            ORDER BY p.estoque_produto ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarMovimentacaoSegura($dados)
    {
        $stmt = $this->db->prepare("
            CALL registrar_movimentacao_segura(
                :session_id,
                :request_id,
                :marca_produto,
                :acao,
                :quantidade,
                :preco_custo,
                :preco_venda,
                :observacao,
                :usuario_id,
                :ip_usuario,
                :user_agent,
                @status,
                @mensagem,
                @id_movimentacao
            )
        ");

        $stmt->bindValue(':session_id', $dados['session_id'], PDO::PARAM_STR);
        $stmt->bindValue(':request_id', $dados['request_id'], PDO::PARAM_STR);
        $stmt->bindValue(':marca_produto', $dados['marca_produto'], PDO::PARAM_STR);
        $stmt->bindValue(':acao', $dados['acao'], PDO::PARAM_STR);
        $stmt->bindValue(':quantidade', $dados['quantidade'], PDO::PARAM_INT);
        $stmt->bindValue(':preco_custo', $dados['preco_custo'], PDO::PARAM_STR);
        $stmt->bindValue(':preco_venda', $dados['preco_venda'], PDO::PARAM_STR);
        $stmt->bindValue(':observacao', $dados['observacao'], PDO::PARAM_STR);
        $stmt->bindValue(':usuario_id', $dados['usuario_id'], PDO::PARAM_INT);
        $stmt->bindValue(':ip_usuario', $dados['ip_usuario'], PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $dados['user_agent'], PDO::PARAM_STR);

        $stmt->execute();

        $result = $this->db->query("SELECT @status AS status, @mensagem AS mensagem, @id_movimentacao AS id_movimentacao");
        return $result->fetch(PDO::FETCH_ASSOC);
    }
}
