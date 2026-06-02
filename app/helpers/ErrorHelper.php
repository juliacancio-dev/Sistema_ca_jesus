<?php
class ErrorHelper
{

    public static function handle($exception, $userMessage = null, $tipoTeste = 'Teste de Sistema')
    {
        require_once __DIR__ . '/LogHelper.php';
        $msg = $userMessage ?: 'Ocorreu um erro inesperado. Tente novamente.';

        error_log('[ErrorHelper] ' . $exception->getMessage());

        $detalhes = $exception->getFile() . ':' . $exception->getLine() . ' | Trace: ' . $exception->getTraceAsString();
        LogHelper::registrar('error', $exception->getMessage(), $tipoTeste, $detalhes);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['toast_error'] = $msg;
    }

    public static function displayToast()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['toast_error'])) {
            echo "<script>
                window.onload=function(){
                    if(typeof showToast === 'function'){
                        showToast('" . addslashes($_SESSION['toast_error']) . "','error');
                    } else {
                        alert('" . addslashes($_SESSION['toast_error']) . "');
                    }
                };
            </script>";
            unset($_SESSION['toast_error']);
        }
    }
    public static function logSuccess($mensagem, $tipoTeste = 'Teste de Sistema', $detalhes = null)
    {
        require_once __DIR__ . '/LogHelper.php';
        LogHelper::registrar('success', $mensagem, $tipoTeste, $detalhes);
    }
}
