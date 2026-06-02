<?php

class LogModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function registrarLogMovimentacao($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tb_logs_movimentacao (
                session_id, request_id, marca_produto, acao_movimentacao,
                quantidade_movimentacao, preco_custo_movimentacao, 
                preco_venda_movimentacao, observacao_movimentacao,
                usuario_id, ip_usuario, user_agent
            ) VALUES (
                :session_id, :request_id, :marca_produto, :acao,
                :quantidade, :preco_custo, :preco_venda, :observacao,
                :usuario_id, :ip_usuario, :user_agent
            )
        ");

        return $stmt->execute([
            ':session_id' => $data['session_id'],
            ':request_id' => $data['request_id'],
            ':marca_produto' => $data['marca_produto'],
            ':acao' => $data['acao'],
            ':quantidade' => $data['quantidade'],
            ':preco_custo' => $data['preco_custo'],
            ':preco_venda' => $data['preco_venda'],
            ':observacao' => $data['observacao'] ?? null,
            ':usuario_id' => $data['usuario_id'],
            ':ip_usuario' => $data['ip_usuario'],
            ':user_agent' => $data['user_agent']
        ]);
    }

    public function atualizarLogMovimentacao($request_id, $status, $id_movimentacao_gerada = null, $erro_detalhes = null, $tempo_processamento_ms = null) {
        $stmt = $this->db->prepare("
            UPDATE tb_logs_movimentacao 
            SET 
                status_processamento = :status,
                id_movimentacao_gerada = :id_movimentacao_gerada,
                erro_detalhes = :erro_detalhes,
                data_processamento = NOW(),
                tempo_processamento_ms = :tempo_processamento_ms
            WHERE request_id = :request_id
        ");

        return $stmt->execute([
            ':status' => $status,
            ':id_movimentacao_gerada' => $id_movimentacao_gerada,
            ':erro_detalhes' => $erro_detalhes,
            ':tempo_processamento_ms' => $tempo_processamento_ms,
            ':request_id' => $request_id
        ]);
    }

    public function registrarAuditoria($data) {
        $stmt = $this->db->prepare("
            INSERT INTO tb_auditoria (
                tabela_afetada, operacao, id_registro,
                dados_anteriores, dados_novos, usuario_id,
                ip_usuario, observacoes
            ) VALUES (
                :tabela_afetada, :operacao, :id_registro,
                :dados_anteriores, :dados_novos, :usuario_id,
                :ip_usuario, :observacoes
            )
        ");

        return $stmt->execute([
            ':tabela_afetada' => $data['tabela_afetada'],
            ':operacao' => $data['operacao'],
            ':id_registro' => $data['id_registro'],
            ':dados_anteriores' => $data['dados_anteriores'] ?? null,
            ':dados_novos' => $data['dados_novos'] ?? null,
            ':usuario_id' => $data['usuario_id'] ?? null,
            ':ip_usuario' => $data['ip_usuario'] ?? null,
            ':observacoes' => $data['observacoes'] ?? null
        ]);
    }

    public function buscarLogsMovimentacao($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['session_id'])) {
            $where[] = "session_id = :session_id";
            $params[':session_id'] = $filters['session_id'];
        }
        if (!empty($filters['request_id'])) {
            $where[] = "request_id = :request_id";
            $params[':request_id'] = $filters['request_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "status_processamento = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['usuario_id'])) {
            $where[] = "usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }
        if (!empty($filters['data_inicio'])) {
            $where[] = "data_criacao >= :data_inicio";
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where[] = "data_criacao <= :data_fim";
            $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM tb_logs_movimentacao";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY data_criacao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarAuditoria($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['tabela_afetada'])) {
            $where[] = "tabela_afetada = :tabela_afetada";
            $params[':tabela_afetada'] = $filters['tabela_afetada'];
        }
        if (!empty($filters['operacao'])) {
            $where[] = "operacao = :operacao";
            $params[':operacao'] = $filters['operacao'];
        }
        if (!empty($filters['usuario_id'])) {
            $where[] = "usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }
        if (!empty($filters['data_inicio'])) {
            $where[] = "data_operacao >= :data_inicio";
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where[] = "data_operacao <= :data_fim";
            $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM tb_auditoria";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY data_operacao DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function verificarDuplicacao($session_id, $request_id, $marca_produto, $acao, $quantidade, $usuario_id) {
        $stmt = $this->db->prepare("CALL verificar_duplicacao_movimentacao(?, ?, ?, ?, ?, ?, @p_resultado, @p_mensagem)");
        $stmt->execute([$session_id, $request_id, $marca_produto, $acao, $quantidade, $usuario_id]);
        
        $result = $this->db->query("SELECT @p_resultado AS resultado, @p_mensagem AS mensagem")->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEstatisticas() {
        $logs = $this->db->query("
            SELECT 
                COUNT(*) as total_registros,
                COUNT(CASE WHEN status_processamento = 'processado' THEN 1 END) as processados,
                COUNT(CASE WHEN status_processamento = 'duplicado' THEN 1 END) as duplicados,
                COUNT(CASE WHEN status_processamento = 'erro' THEN 1 END) as erros,
                AVG(tempo_processamento_ms) as tempo_medio_ms
            FROM tb_logs_movimentacao
        ")->fetch(PDO::FETCH_ASSOC);

        $auditoria = $this->db->query("
            SELECT 
                COUNT(*) as total_registros,
                COUNT(CASE WHEN operacao = 'INSERT' THEN 1 END) as inserts,
                COUNT(CASE WHEN operacao = 'UPDATE' THEN 1 END) as updates,
                COUNT(CASE WHEN operacao = 'DELETE' THEN 1 END) as deletes
            FROM tb_auditoria
        ")->fetch(PDO::FETCH_ASSOC);

        return [
            'logs' => $logs,
            'auditoria' => $auditoria
        ];
    }

    public function limparLogsAntigos($diasManter = 90) {
        $stmt = $this->db->prepare("CALL limpar_logs_antigos(?)");
        $stmt->execute([$diasManter]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRelatorioLogs() {
        $stmt = $this->db->query("SELECT * FROM vw_relatorio_logs");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRelatorioDuplicacoes() {
        $stmt = $this->db->query("SELECT * FROM vw_relatorio_duplicacoes");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditoriaEstoque() {
        $stmt = $this->db->query("SELECT * FROM vw_auditoria_estoque");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}