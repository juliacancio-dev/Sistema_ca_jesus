<?php

abstract class Controller {
    // Erros de renderização sobem para o tratamento único em public/index.php.
    protected function renderView($viewPath, $data = [], $layout = null) {
        if (!isset($data['usuarioAtual']) && AuthHelper::isLoggedIn()) {
            $data['usuarioAtual'] = AuthHelper::getUser();
        }

        if (isset($_SESSION['error']) && !isset($data['error'])) {
            $data['error'] = $_SESSION['error'];
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['success']) && !isset($data['success'])) {
            $data['success'] = $_SESSION['success'];
            unset($_SESSION['success']);
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
    
    protected function renderJson($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url, $message = null, $isError = false) {
        if ($message) {
            if ($isError) {
                $_SESSION['error'] = $message;
            } else {
                $_SESSION['success'] = $message;
            }
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
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    protected function getRequestData() {
        if (!empty($_POST)) {
            return $_POST;
        }
        
        $inputJSON = file_get_contents('php://input');
        if ($inputJSON) {
            return json_decode($inputJSON, true) ?? [];
        }
        
        return [];
    }
}
