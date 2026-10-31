<?php

use App\Core\Session;

class ErrorHelper
{
    // Registra o erro no log. A mensagem ao usuário é responsabilidade de quem chamou (Session::flash ou JSON).
    public static function handle($exception, $userMessage = null, $tipoTeste = 'Teste de Sistema')
    {
        require_once __DIR__ . '/LogHelper.php';

        error_log('[ErrorHelper] ' . $exception->getMessage());

        $detalhes = $exception->getFile() . ':' . $exception->getLine() . ' | Trace: ' . $exception->getTraceAsString();
        LogHelper::registrar('error', $exception->getMessage(), $tipoTeste, $detalhes);
    }

    public static function logSuccess($mensagem, $tipoTeste = 'Teste de Sistema', $detalhes = null)
    {
        require_once __DIR__ . '/LogHelper.php';
        LogHelper::registrar('success', $mensagem, $tipoTeste, $detalhes);
    }
}
