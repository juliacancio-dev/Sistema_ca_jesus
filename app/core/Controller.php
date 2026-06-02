<?php

abstract class Controller {
    protected function renderView($viewPath, $data = [], $layout = null) {
        try {
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
            
            ob_start();
            $viewFile = realpath(__DIR__ . "/../views/" . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewPath) . ".php");
            if (!$viewFile || !file_exists($viewFile)) {
                error_log("[Controller.php] View não encontrada: $viewPath | Caminho: $viewFile");
                throw new Exception("View não encontrada: $viewPath");
            }

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
            
            if (!$layoutFile || !file_exists($layoutFile)) {
                error_log("[Controller.php] Layout não encontrado: layouts/$layoutName.php | Caminho: $layoutFile");
                throw new Exception("Layout não encontrado: layouts/$layoutName.php");
            }
            
            require $layoutFile;
            
        } catch (Exception $e) {
            if (!class_exists('ErrorHelper')) {
                require_once __DIR__ . '/../helpers/ErrorHelper.php';
            }
            
            error_log("[Controller] Erro ao renderizar view: " . $e->getMessage());
            error_log("[Controller] " . $e->getTraceAsString());
            
            ErrorHelper::handle($e, 'Erro ao carregar página.');
            
            echo '<div class="bg-red-100 text-red-700 p-4 rounded m-4">';
            echo '<strong>Erro ao carregar página.</strong><br>';
            echo '<b>Mensagem:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
            
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo '<b>Arquivo:</b> ' . htmlspecialchars($e->getFile()) . '<br>';
                echo '<b>Linha:</b> ' . $e->getLine() . '<br>';
                echo '<b>Trace:</b><pre style="white-space:pre-wrap;font-size:12px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
            
            echo '</div>';
            
            if (method_exists('ErrorHelper', 'displayToast')) {
                ErrorHelper::displayToast();
            }
        }
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
            $url = '/sistema_ca_jesus/public' . $url;
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
