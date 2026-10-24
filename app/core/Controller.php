<?php

use App\Core\RespostaJson;

abstract class Controller {
    // Toda resposta JSON passa por aqui (contrato em docs/arquitetura.md).
    protected function json(bool $sucesso, ?string $mensagem = null, mixed $dados = null, int $status = 200, bool $requerConfirmacao = false): never {
        RespostaJson::enviar($sucesso, $mensagem, $dados, $status, $requerConfirmacao);
    }

    // Erros de renderização sobem para o tratamento único em public/index.php.
    protected function renderView($viewPath, $data = [], $layout = null) {
        if (!isset($data['usuarioAtual']) && AuthHelper::isLoggedIn()) {
            $data['usuarioAtual'] = AuthHelper::getUser();
        }

        extract($data);

        $viewFile = realpath(__DIR__ . "/../views/" . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewPath) . ".php");
        if (!$viewFile) {
            throw new Exception("View não encontrada: $viewPath");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $useLayout = true;

        if (strpos($viewPath, 'auth/') === 0 || strpos($viewPath, 'layouts/auth/') === 0) {
            $useLayout = false;
        }

        if ($layout === false) {
            $useLayout = false;
        }

        if (!$useLayout) {
            echo $content;
            return;
        }

        $layoutName = $layout ?? 'main';
        $layoutFile = realpath(__DIR__ . '/../views/layouts/' . $layoutName . '.php');

        if (!$layoutFile) {
            throw new Exception("Layout não encontrado: layouts/$layoutName.php");
        }

        require $layoutFile;
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
