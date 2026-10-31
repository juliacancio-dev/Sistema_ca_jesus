<?php

namespace App\Core;

use Exception;

abstract class Controller {
    private const PASTA_VIEWS = __DIR__ . '/../../frontend/views/';

    // Toda resposta JSON passa por aqui (contrato em docs/arquitetura.md).
    protected function json(bool $sucesso, ?string $mensagem = null, mixed $dados = null, int $status = 200, bool $requerConfirmacao = false): never {
        RespostaJson::enviar($sucesso, $mensagem, $dados, $status, $requerConfirmacao);
    }

    // Erros de renderização sobem para o tratamento único em public/index.php.
    protected function render(string $view, array $data = [], bool $comLayout = true): void {
        if (!isset($data['usuarioAtual']) && Auth::isLoggedIn()) {
            $data['usuarioAtual'] = Auth::getUser();
        }

        extract($data);

        $viewFile = realpath(self::PASTA_VIEWS . $view . '.php');
        if (!$viewFile) {
            throw new Exception("View não encontrada: $view");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (!$comLayout) {
            echo $content;
            return;
        }

        require self::PASTA_VIEWS . 'layout.php';
    }

    protected function redirect($url, $message = null, $isError = false) {
        if ($message) {
            Session::flash($isError ? 'error' : 'success', $message);
        }

        if (strpos($url, 'http') !== 0) {
            if ($url[0] !== '/') {
                $url = '/' . $url;
            }
            $url = BASE_URL . $url;
        }

        header("Location: $url");
        exit;
    }
}
