<?php

use App\Core\Database;
use App\Core\DatabaseException;

class FornecedorModel
{
    private $db;
    private $tableName = 'tbfornecedores';

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
            error_log("[FornecedorModel] Instância do banco criada com sucesso");
        } catch (Exception $e) {
            error_log("[FornecedorModel] Erro ao criar instância do banco: " . $e->getMessage());
            throw $e;
        }
    }

    public function listarTodos(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id_fornecedor AS id,
                    nome_fornecedor AS nome,
                    cnpj_fornecedor AS cnpj,
                    email_fornecedor AS email,
                    telefone_fornecedor AS telefone,
                    endereco_fornecedor AS endereco,
                    numero_fornecedor AS numero,
                    complemento_fornecedor AS complemento,
                    bairro_fornecedor AS bairro,
                    cidade_fornecedor AS cidade,
                    estado_fornecedor AS estado,
                    cep_fornecedor AS cep,
                    COALESCE(ativo_fornecedor, 1) AS ativo,
                    data_criacao,
                    data_atualizacao
                FROM {$this->tableName}
                ORDER BY nome_fornecedor ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao listar fornecedores: " . $e->getMessage());
            throw new DatabaseException("Erro ao carregar lista de fornecedores");
        }
    }

    public function buscarPorId(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id_fornecedor AS id,
                    nome_fornecedor AS nome,
                    cnpj_fornecedor AS cnpj,
                    email_fornecedor AS email,
                    telefone_fornecedor AS telefone,
                    endereco_fornecedor AS endereco,
                    numero_fornecedor AS numero,
                    complemento_fornecedor AS complemento,
                    bairro_fornecedor AS bairro,
                    cidade_fornecedor AS cidade,
                    estado_fornecedor AS estado,
                    cep_fornecedor AS cep,
                    COALESCE(ativo_fornecedor, 1) AS ativo,
                    data_criacao,
                    data_atualizacao
                FROM {$this->tableName}
                WHERE id_fornecedor = ?
            ");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar fornecedor por ID: " . $e->getMessage());
            throw new DatabaseException("Erro ao carregar fornecedor");
        }
    }

    public function salvar(array $dados): bool
    {
        return empty($dados['id']) ? $this->criar($dados) : $this->atualizar($dados['id'], $dados);
    }

    private function criar(array $dados): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO {$this->tableName} (
                    nome_fornecedor,
                    cnpj_fornecedor,
                    email_fornecedor,
                    telefone_fornecedor,
                    endereco_fornecedor,
                    numero_fornecedor,
                    complemento_fornecedor,
                    bairro_fornecedor,
                    cidade_fornecedor,
                    estado_fornecedor,
                    cep_fornecedor,
                    ativo_fornecedor,
                    data_criacao,
                    data_atualizacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $resultado = $stmt->execute([
                $dados['nome'],
                $dados['cnpj'],
                $dados['email'],
                $dados['telefone'],
                $dados['endereco'] ?? null,
                $dados['numero'] ?? null,
                $dados['complemento'] ?? null,
                $dados['bairro'] ?? null,
                $dados['cidade'] ?? null,
                $dados['estado'] ?? null,
                $dados['cep'] ?? null,
                $dados['ativo'] ?? 1
            ]);

            $this->db->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao criar fornecedor: " . $e->getMessage());
            $this->lancarSeDuplicado($e);
            throw new DatabaseException("Erro ao criar fornecedor");
        }
    }

    private function atualizar(int $id, array $dados): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} SET
                    nome_fornecedor = ?,
                    cnpj_fornecedor = ?,
                    email_fornecedor = ?,
                    telefone_fornecedor = ?,
                    endereco_fornecedor = ?,
                    numero_fornecedor = ?,
                    complemento_fornecedor = ?,
                    bairro_fornecedor = ?,
                    cidade_fornecedor = ?,
                    estado_fornecedor = ?,
                    cep_fornecedor = ?,
                    data_atualizacao = NOW()
                WHERE id_fornecedor = ?
            ");

            $resultado = $stmt->execute([
                $dados['nome'],
                $dados['cnpj'],
                $dados['email'],
                $dados['telefone'],
                $dados['endereco'] ?? null,
                $dados['numero'] ?? null,
                $dados['complemento'] ?? null,
                $dados['bairro'] ?? null,
                $dados['cidade'] ?? null,
                $dados['estado'] ?? null,
                $dados['cep'] ?? null,
                $id
            ]);

            $this->db->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao atualizar fornecedor: " . $e->getMessage());
            $this->lancarSeDuplicado($e);
            throw new DatabaseException("Erro ao atualizar fornecedor");
        }
    }

    private function lancarSeDuplicado(PDOException $e): void
    {
        if (($e->errorInfo[1] ?? null) === 1062) {
            throw new DomainException("Já existe um fornecedor com este CNPJ ou e-mail.");
        }
    }

    public function deletar(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE id_fornecedor = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao deletar fornecedor: " . $e->getMessage());
            throw new DatabaseException("Erro ao excluir fornecedor");
        }
    }

    public function buscar(string $termo): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id_fornecedor AS id,
                    nome_fornecedor AS nome,
                    cnpj_fornecedor AS cnpj,
                    email_fornecedor AS email,
                    telefone_fornecedor AS telefone,
                    endereco_fornecedor AS endereco,
                    numero_fornecedor AS numero,
                    complemento_fornecedor AS complemento,
                    bairro_fornecedor AS bairro,
                    cidade_fornecedor AS cidade,
                    estado_fornecedor AS estado,
                    cep_fornecedor AS cep,
                    COALESCE(ativo_fornecedor, 1) AS ativo,
                    data_criacao,
                    data_atualizacao
                FROM {$this->tableName}
                WHERE nome_fornecedor LIKE ? OR
                      cnpj_fornecedor LIKE ? OR
                      email_fornecedor LIKE ? OR
                      telefone_fornecedor LIKE ?
                ORDER BY nome_fornecedor ASC
            ");
            $termoLike = '%' . $termo . '%';
            $stmt->execute([$termoLike, $termoLike, $termoLike, $termoLike]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar fornecedores: " . $e->getMessage());
            throw new DatabaseException("Erro na busca de fornecedores");
        }
    }

    public function alternarStatus(int $id, int $status): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName}
                SET ativo_fornecedor = ?, data_atualizacao = NOW()
                WHERE id_fornecedor = ?
            ");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Erro ao alterar status do fornecedor: " . $e->getMessage());
            throw new DatabaseException("Erro ao alterar status do fornecedor");
        }
    }

    public function contarProdutosVinculados(int $id): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM tbprodutos
                WHERE fornecedor_id = ?
            ");
            $stmt->execute([$id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar produtos do fornecedor: " . $e->getMessage());
            return 0;
        }
    }
}
