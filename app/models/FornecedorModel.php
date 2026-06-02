<?php

require_once __DIR__ . '/../core/Exceptions.php';

class FornecedorModel
{
    private $db;
    private $tableName = 'tbfornecedores';

    private const COLUMN_MAP = [
        'id' => 'id_fornecedor',
        'nome' => 'nome_fornecedor',
        'cnpj' => 'cnpj_fornecedor',
        'email' => 'email_fornecedor',
        'telefone' => 'telefone_fornecedor',
        'endereco' => 'endereco_fornecedor',
        'numero' => 'numero_fornecedor',
        'complemento' => 'complemento_fornecedor',
        'bairro' => 'bairro_fornecedor',
        'cidade' => 'cidade_fornecedor',
        'estado' => 'estado_fornecedor',
        'cep' => 'cep_fornecedor',
        'ativo' => 'ativo_fornecedor',
        'data_criacao' => 'data_criacao',
        'data_atualizacao' => 'data_atualizacao'
    ];

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

    public function verificarEstrutura(): void
    {
        try {
            $colunas = [
                'ativo_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `ativo_fornecedor` TINYINT(1) DEFAULT 1',
                'data_criacao' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'data_atualizacao' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'endereco_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `endereco_fornecedor` VARCHAR(255) NULL',
                'numero_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `numero_fornecedor` VARCHAR(10) NULL',
                'complemento_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `complemento_fornecedor` VARCHAR(50) NULL',
                'bairro_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `bairro_fornecedor` VARCHAR(100) NULL',
                'cidade_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `cidade_fornecedor` VARCHAR(100) NULL',
                'estado_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `estado_fornecedor` VARCHAR(2) NULL',
                'cep_fornecedor' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `cep_fornecedor` VARCHAR(9) NULL'
            ];

            foreach ($colunas as $coluna => $sql) {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$this->tableName}` LIKE ?");
                $stmt->execute([$coluna]);
                if (!$stmt->fetch()) {
                    $this->db->exec($sql);
                    error_log("Coluna $coluna adicionada à tabela {$this->tableName}");
                }
            }
        } catch (PDOException $e) {
            error_log("Erro na verificação da estrutura: " . $e->getMessage());
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
                    ativo_fornecedor = ?,
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
                $dados['ativo'] ?? 1,
                $id
            ]);

            $this->db->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao atualizar fornecedor: " . $e->getMessage());
            throw new DatabaseException("Erro ao atualizar fornecedor");
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

    public function contarTotal(): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->tableName}");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar fornecedores: " . $e->getMessage());
            return 0;
        }
    }

    public function listarParaSelect(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id_fornecedor AS id, nome_fornecedor AS nome
                FROM {$this->tableName}
                ORDER BY nome_fornecedor ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao listar fornecedores para select: " . $e->getMessage());
            return [];
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
