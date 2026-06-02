<?php
class LogHelper
{
    const NIVEL_INFO = 'info';
    const NIVEL_WARN = 'warn';
    const NIVEL_ERROR = 'error';
    const NIVEL_SUCCESS = 'success';
    const NIVEL_AUDIT = 'audit';

    public static function registrar($status, $mensagem, $categoria, $detalhes = null, $contexto = [])
    {
        $logDir = __DIR__ . '/../../armazenamento/logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        if (class_exists('AuthHelper') && method_exists('AuthHelper', 'getUser')) {
            $usuario = AuthHelper::getUser();
            if ($usuario) {
                $contexto['usuario_id'] = $usuario['id'] ?? null;
                $contexto['usuario_nome'] = $usuario['nome'] ?? null;
            }
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $contexto['ip'] = $_SERVER['REMOTE_ADDR'];
            $contexto['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $contexto['request_uri'] = $_SERVER['REQUEST_URI'] ?? null;
        }

        $data = date('Y-m-d H:i:s');
        $linha = "[$data] [$status] [$categoria] $mensagem";

        if ($detalhes) {
            $linha .= " | Detalhes: $detalhes";
        }

        if (!empty($contexto)) {
            $linha .= " | Contexto: " . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }

        $linha .= "\n";
        $logFile = $logDir . 'sistema_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $linha, FILE_APPEND);
    }
    public static function logDatabase($operacao, $tabela, $detalhes = null, $contexto = [])
    {
        self::registrar(self::NIVEL_INFO, "DB $operacao em $tabela", 'Database', $detalhes, $contexto);
    }
    public static function logAuth($acao, $usuario, $sucesso = true, $detalhes = null)
    {
        $status = $sucesso ? self::NIVEL_SUCCESS : self::NIVEL_ERROR;
        self::registrar($status, "Auth $acao", 'Auth', $detalhes, ['usuario' => $usuario]);
    }
    public static function logCrud($operacao, $recurso, $id = null, $detalhes = null)
    {
        $mensagem = "CRUD $operacao em $recurso" . ($id ? " #$id" : '');
        self::registrar(self::NIVEL_AUDIT, $mensagem, 'CRUD', $detalhes);
    }
    public static function logError($mensagem, $excecao = null)
    {
        $detalhes = $excecao ? $excecao->getMessage() . ' | ' . $excecao->getTraceAsString() : null;
        self::registrar(self::NIVEL_ERROR, $mensagem, 'Sistema', $detalhes);
    }
    public static function logAudit($acao, $detalhes = null, $contexto = [])
    {
        self::registrar(self::NIVEL_AUDIT, $acao, 'Audit', $detalhes, $contexto);
    }
}
