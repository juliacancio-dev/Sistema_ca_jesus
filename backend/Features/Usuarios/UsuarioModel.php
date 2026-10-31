<?php

namespace App\Features\Usuarios;

use App\Core\Database;
use App\Core\DatabaseException;
use PDO;
use PDOException;

class UsuarioModel 
{
    private $db;
    private $tableName = 'tbusuarios';
    
    public function __construct() 
    {
        $this->db = Database::getInstance();
    }

    public static function gerarHashDeSenha(string $senha): string
    {
        return password_hash($senha, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    public function listarTodosComPaginacao(int $pagina = 1, int $itensPorPagina = 10): array 
    {
        try {
            $offset = ($pagina - 1) * $itensPorPagina;
            
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) FROM {$this->tableName} 
                WHERE deletado_em IS NULL
            ");
            $stmtCount->execute();
            $total = $stmtCount->fetchColumn();
            
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    tipo_usuario AS tipo,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    cpf_usuario AS cpf,
                    rg_usuario AS rg,
                    telefone_usuario AS telefone,
                    sexo_usuario AS sexo,
                    cep_usuario AS cep,
                    endereco_usuario AS endereco,
                    cidade_usuario AS cidade,
                    estado_usuario AS estado,
                    numero_usuario AS numero,
                    complemento_usuario AS complemento,
                    bairro_usuario AS bairro,
                    COALESCE(ativo_usuario, 1) AS ativo,
                    data_criacao,
                    data_atualizacao,
                    ultimo_login
                FROM {$this->tableName}
                WHERE deletado_em IS NULL
                ORDER BY nome_usuario ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$itensPorPagina, $offset]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'dados' => $dados,
                'paginacao' => [
                    'total' => $total,
                    'paginaAtual' => $pagina,
                    'itensPorPagina' => $itensPorPagina,
                    'totalPaginas' => ceil($total / $itensPorPagina),
                    'inicio' => $offset + 1,
                    'fim' => min($offset + $itensPorPagina, $total)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erro ao listar usuários com paginação: " . $e->getMessage());
            throw new DatabaseException("Erro ao carregar usuários");
        }
    }

    public function buscarComPaginacao(string $termo, int $pagina = 1, int $itensPorPagina = 10): array 
    {
        try {
            $offset = ($pagina - 1) * $itensPorPagina;
            $termoLike = '%' . $termo . '%';
            
            $stmtCount = $this->db->prepare("
                SELECT COUNT(*) FROM {$this->tableName} 
                WHERE deletado_em IS NULL
                AND (
                    nome_usuario LIKE ? OR 
                    email_usuario LIKE ? OR 
                    cpf_usuario LIKE ? OR
                    telefone_usuario LIKE ?
                )
            ");
            $stmtCount->execute([$termoLike, $termoLike, $termoLike, $termoLike]);
            $total = $stmtCount->fetchColumn();
            
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    tipo_usuario AS tipo,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    cpf_usuario AS cpf,
                    rg_usuario AS rg,
                    telefone_usuario AS telefone,
                    sexo_usuario AS sexo,
                    cep_usuario AS cep,
                    endereco_usuario AS endereco,
                    cidade_usuario AS cidade,
                    estado_usuario AS estado,
                    numero_usuario AS numero,
                    complemento_usuario AS complemento,
                    bairro_usuario AS bairro,
                    COALESCE(ativo_usuario, 1) AS ativo,
                    data_criacao,
                    data_atualizacao,
                    ultimo_login
                FROM {$this->tableName}
                WHERE deletado_em IS NULL
                AND (
                    nome_usuario LIKE ? OR 
                    email_usuario LIKE ? OR 
                    cpf_usuario LIKE ? OR
                    telefone_usuario LIKE ?
                )
                ORDER BY nome_usuario ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$termoLike, $termoLike, $termoLike, $termoLike, $itensPorPagina, $offset]);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'dados' => $dados,
                'paginacao' => [
                    'total' => $total,
                    'paginaAtual' => $pagina,
                    'itensPorPagina' => $itensPorPagina,
                    'totalPaginas' => ceil($total / $itensPorPagina),
                    'inicio' => $offset + 1,
                    'fim' => min($offset + $itensPorPagina, $total)
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuários com paginação: " . $e->getMessage());
            throw new DatabaseException("Erro na busca paginada");
        }
    }

    public function buscarPorId(int $id): ?array 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    tipo_usuario AS tipo,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    senha_usuario AS senha,
                    cpf_usuario AS cpf,
                    rg_usuario AS rg,
                    telefone_usuario AS telefone,
                    sexo_usuario AS sexo,
                    cep_usuario AS cep,
                    endereco_usuario AS endereco,
                    cidade_usuario AS cidade,
                    estado_usuario AS estado,
                    numero_usuario AS numero,
                    complemento_usuario AS complemento,
                    bairro_usuario AS bairro,
                    COALESCE(ativo_usuario, 1) AS ativo,
                    data_criacao,
                    data_atualizacao,
                    ultimo_login
                FROM {$this->tableName} 
                WHERE id_usuario = ? AND deletado_em IS NULL
            ");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário por ID: " . $e->getMessage());
            throw new DatabaseException("Erro ao carregar usuário");
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
                    tipo_usuario, 
                    nome_usuario, 
                    email_usuario, 
                    senha_usuario, 
                    cpf_usuario, 
                    rg_usuario, 
                    telefone_usuario, 
                    sexo_usuario, 
                    cep_usuario, 
                    endereco_usuario, 
                    cidade_usuario, 
                    estado_usuario, 
                    numero_usuario, 
                    complemento_usuario, 
                    bairro_usuario,
                    ativo_usuario,
                    data_criacao,
                    data_atualizacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $resultado = $stmt->execute([
                $dados['tipo'],
                $dados['nome'],
                $dados['email'],
                $dados['senha'],
                $dados['cpf'],
                $dados['rg'],
                $dados['telefone'],
                $dados['sexo'],
                $dados['cep'],
                $dados['endereco'],
                $dados['cidade'],
                $dados['estado'],
                $dados['numero'],
                $dados['complemento'] ?? null,
                $dados['bairro'],
                $dados['ativo'] ?? 1
            ]);
            
            $this->db->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao criar usuário: " . $e->getMessage());
            throw new DatabaseException("Erro ao criar usuário");
        }
    }

    private function atualizar(int $id, array $dados): bool 
    {
        try {
            $this->db->beginTransaction();
            
            $campos = [
                'tipo_usuario = ?',
                'nome_usuario = ?',
                'email_usuario = ?',
                'cpf_usuario = ?',
                'rg_usuario = ?',
                'telefone_usuario = ?',
                'sexo_usuario = ?',
                'cep_usuario = ?',
                'endereco_usuario = ?',
                'cidade_usuario = ?',
                'estado_usuario = ?',
                'numero_usuario = ?',
                'complemento_usuario = ?',
                'bairro_usuario = ?',
                'ativo_usuario = ?',
                'data_atualizacao = NOW()'
            ];
            
            $params = [
                $dados['tipo'],
                $dados['nome'],
                $dados['email'],
                $dados['cpf'],
                $dados['rg'],
                $dados['telefone'],
                $dados['sexo'],
                $dados['cep'],
                $dados['endereco'],
                $dados['cidade'],
                $dados['estado'],
                $dados['numero'],
                $dados['complemento'] ?? null,
                $dados['bairro'],
                $dados['ativo'] ?? 1
            ];
            
            if (isset($dados['senha']) && !empty($dados['senha'])) {
                $campos[] = 'senha_usuario = ?';
                $params[] = $dados['senha'];
            }
            
            $params[] = $id;
            
            $sql = "UPDATE {$this->tableName} SET " . implode(', ', $campos) . " WHERE id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            
            $resultado = $stmt->execute($params);
            $this->db->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            throw new DatabaseException("Erro ao atualizar usuário");
        }
    }

    public function atualizarDadosPessoais(int $id, string $nome, string $telefone): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName}
                SET
                    nome_usuario = ?,
                    telefone_usuario = ?,
                    data_atualizacao = NOW()
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$nome, $telefone, $id]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar dados pessoais: " . $e->getMessage());
            throw new DatabaseException("Erro ao atualizar perfil");
        }
    }

    public function atualizarEndereco(int $id, array $endereco): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName}
                SET
                    endereco_usuario = ?,
                    cidade_usuario = ?,
                    estado_usuario = ?,
                    cep_usuario = ?,
                    numero_usuario = ?,
                    complemento_usuario = ?,
                    bairro_usuario = ?,
                    data_atualizacao = NOW()
                WHERE id_usuario = ?
            ");
            return $stmt->execute([
                $endereco['endereco'],
                $endereco['cidade'],
                $endereco['estado'],
                $endereco['cep'],
                $endereco['numero'],
                $endereco['complemento'],
                $endereco['bairro'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar endereço: " . $e->getMessage());
            throw new DatabaseException("Erro ao atualizar perfil");
        }
    }

    public function alterarSenha(int $id, string $senhaHash): bool 
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} 
                SET senha_usuario = ?, data_atualizacao = NOW() 
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$senhaHash, $id]);
        } catch (PDOException $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            throw new DatabaseException("Erro ao alterar senha");
        }
    }

    public function deletar(int $id): bool 
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} 
                SET deletado_em = NOW(), ativo_usuario = 0 
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao deletar usuário: " . $e->getMessage());
            throw new DatabaseException("Erro ao excluir usuário");
        }
    }

    public function alternarStatus(int $id, int $status): bool 
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} 
                SET ativo_usuario = ?, data_atualizacao = NOW() 
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            error_log("Erro ao alterar status: " . $e->getMessage());
            throw new DatabaseException("Erro ao alterar status do usuário");
        }
    }

    public function verificarEmailExistente(string $email, ?int $id = null): bool 
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE email_usuario = ?";
            $params = [$email];
            
            if ($id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar email: " . $e->getMessage());
            return true;
        }
    }

    public function verificarCpfExistente(string $cpf, ?int $id = null): bool 
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE cpf_usuario = ? AND deletado_em IS NULL";
            $params = [$cpf];
            
            if ($id !== null) {
                $sql .= " AND id_usuario != ?";
                $params[] = $id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar CPF: " . $e->getMessage());
            return true;
        }
    }
}
