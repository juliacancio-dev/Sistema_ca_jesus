<?php
class LogHelper
{
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
}
