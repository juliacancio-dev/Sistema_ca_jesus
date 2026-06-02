<?php

require_once __DIR__ . '/../core/Exceptions.php';

class UsuarioModel 
{
    private $db;
    private $tableName = 'tbusuarios';
    
    private const COLUMN_MAP = [
        'id' => 'id_usuario',
        'tipo' => 'tipo_usuario', 
        'nome' => 'nome_usuario',
        'email' => 'email_usuario',
        'senha' => 'senha_usuario',
        'cpf' => 'cpf_usuario',
        'rg' => 'rg_usuario',
        'telefone' => 'telefone_usuario',
        'sexo' => 'sexo_usuario',
        'cep' => 'cep_usuario',
        'endereco' => 'endereco_usuario',
        'cidade' => 'cidade_usuario',
        'estado' => 'estado_usuario',
        'numero' => 'numero_usuario',
        'complemento' => 'complemento_usuario',
        'bairro' => 'bairro_usuario',
        'ativo' => 'ativo_usuario',
        'data_criacao' => 'data_criacao',
        'data_atualizacao' => 'data_atualizacao',
        'ultimo_login' => 'ultimo_login'
    ];

    public function __construct() 
    {
        try {
            $this->db = Database::getInstance();
            error_log("[UsuarioModel] Instância do banco criada com sucesso");
        } catch (Exception $e) {
            error_log("[UsuarioModel] Erro ao criar instância do banco: " . $e->getMessage());
            throw $e;
        }
    }

    public function listarTodos(): array 
    {
        try {
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
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            throw new DatabaseException("Erro ao carregar lista de usuários");
        }
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

    public function buscar(string $termo): array 
    {
        try {
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
            ");
            $termoLike = '%' . $termo . '%';
            $stmt->execute([$termoLike, $termoLike, $termoLike, $termoLike]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuários: " . $e->getMessage());
            throw new DatabaseException("Erro na busca de usuários");
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

    public function buscarPorEmail(string $email): ?array 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    tipo_usuario AS tipo,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    senha_usuario AS senha,
                    COALESCE(ativo_usuario, 1) AS ativo,
                    ultimo_login
                FROM {$this->tableName} 
                WHERE email_usuario = ? AND deletado_em IS NULL
            ");
            $stmt->execute([$email]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ?: null;
        } catch (PDOException $e) {
            error_log("Erro ao buscar usu��rio por email: " . $e->getMessage());
            throw new DatabaseException("Erro ao buscar usuário");
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

    public function atualizarPerfil(array $dados): bool 
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} 
                SET 
                    nome_usuario = ?,
                    telefone_usuario = ?,
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
                $dados['nome'],
                $dados['telefone'],
                $dados['endereco'],
                $dados['cidade'],
                $dados['estado'],
                $dados['cep'],
                $dados['numero'],
                $dados['complemento'],
                $dados['bairro'],
                $dados['id']
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar perfil: " . $e->getMessage());
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

    public function contarTotal(): int 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM {$this->tableName} 
                WHERE deletado_em IS NULL
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar usuários: " . $e->getMessage());
            return 0;
        }
    }

    public function contarPorTipo(int $tipo): int 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM {$this->tableName} 
                WHERE tipo_usuario = ? AND deletado_em IS NULL
            ");
            $stmt->execute([$tipo]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar usuários por tipo: " . $e->getMessage());
            return 0;
        }
    }

    public function contarAtivos(): int 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM {$this->tableName} 
                WHERE COALESCE(ativo_usuario, 1) = 1 AND deletado_em IS NULL
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erro ao contar usuários ativos: " . $e->getMessage());
            return 0;
        }
    }

    public function verificarEmail(string $email, ?int $id = null): bool 
    {
        return $this->verificarEmailExistente($email, $id);
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

    public function autenticar(string $email, string $senha): ?array 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    tipo_usuario AS tipo,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    senha_usuario AS senha,
                    COALESCE(ativo_usuario, 1) AS ativo
                FROM {$this->tableName} 
                WHERE email_usuario = ?
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("[UsuarioModel] Usuário encontrado: " . ($usuario ? 'Sim' : 'Não'));
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                error_log("[UsuarioModel] Senha verificada com sucesso");
                
                unset($usuario['senha']);

                $ativoFlag = (int)($usuario['ativo'] ?? 1);
                if ($ativoFlag !== 1) {
                    error_log("[UsuarioModel] Usuário inativo tentou login: " . $email);
                    return null;
                }
                
                try {
                    $this->atualizarUltimoLogin($usuario['id']);
                } catch (Exception $e) {
                    error_log("[UsuarioModel] Erro ao atualizar último login (ignorado): " . $e->getMessage());
                }
                
                error_log("[UsuarioModel] Autenticação bem-sucedida para usuário ID: " . $usuario['id']);
                return $usuario;
            }
            
            error_log("[UsuarioModel] Falha na autenticação - senha incorreta ou usuário não encontrado");
            return null;
        } catch (PDOException $e) {
            error_log("[UsuarioModel] Erro PDO na autenticação: " . $e->getMessage());
            throw new DatabaseException("Erro no processo de autenticação");
        }
    }

    public function atualizarUltimoLogin(int $id): bool 
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE {$this->tableName} 
                SET ultimo_login = NOW() 
                WHERE id_usuario = ?
            ");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar último login: " . $e->getMessage());
            return false;
        }
    }

    public function buscarRecentes(int $limite = 5): array 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id_usuario AS id,
                    nome_usuario AS nome,
                    email_usuario AS email,
                    tipo_usuario AS tipo,
                    data_criacao
                FROM {$this->tableName} 
                WHERE deletado_em IS NULL
                ORDER BY data_criacao DESC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuários recentes: " . $e->getMessage());
            return [];
        }
    }

    public function obterEstatisticas(): array 
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN COALESCE(ativo_usuario, 1) = 1 THEN 1 ELSE 0 END) as ativos,
                    SUM(CASE WHEN tipo_usuario = 1 THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN tipo_usuario = 2 THEN 1 ELSE 0 END) as funcionarios,
                    SUM(CASE WHEN DATE(data_criacao) = CURDATE() THEN 1 ELSE 0 END) as criados_hoje,
                    SUM(CASE WHEN ultimo_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ativos_30_dias
                FROM {$this->tableName} 
                WHERE deletado_em IS NULL
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [
                'total' => 0,
                'ativos' => 0,
                'administradores' => 0,
                'funcionarios' => 0,
                'criados_hoje' => 0,
                'ativos_30_dias' => 0
            ];
        }
    }

    public function limparUsuariosDeletados(int $diasRetencao = 30): int 
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM {$this->tableName} 
                WHERE deletado_em IS NOT NULL 
                AND deletado_em <= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$diasRetencao]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erro na limpeza de usuários: " . $e->getMessage());
            return 0;
        }
    }

    public function verificarEstrutura(): void 
    {
        try {
            $colunas = [
                'ativo_usuario' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `ativo_usuario` TINYINT(1) DEFAULT 1',
                'data_criacao' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'data_atualizacao' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `data_atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'ultimo_login' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `ultimo_login` TIMESTAMP NULL',
                'deletado_em' => 'ALTER TABLE `' . $this->tableName . '` ADD COLUMN `deletado_em` TIMESTAMP NULL'
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
}
