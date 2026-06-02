<?php

class Router {
    private $routes = [];
    private $middlewares = [];

    public function get($path, $handler, $middlewares = []) {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post($path, $handler, $middlewares = []) {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put($path, $handler, $middlewares = []) {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete($path, $handler, $middlewares = []) {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute($method, $path, $handler, $middlewares) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function resolve() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        
        if (!empty($baseDir) && strpos($requestPath, $baseDir) === 0) {
            $requestPath = substr($requestPath, strlen($baseDir));
        }
        if (empty($requestPath)) {
            $requestPath = '/';
        }
        error_log("[Router Debug] Request Path após ajuste: " . $requestPath);
        error_log("[Router Debug] Request Method: " . $requestMethod);
        error_log("[Router Debug] Sessão: " . json_encode($_SESSION));

        foreach ($this->routes as $route) {
            $pattern = $this->createPatternFromPath($route['path']);
            $matches = [];
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestPath, $matches)) {
                error_log("[Router Debug] Rota encontrada: " . $route['path']);
                
                array_shift($matches);
                
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $middlewareInstance->handle();
                }
                if (is_array($route['handler'])) {
                    $controllerName = $route['handler'][0];
                    $methodName = $route['handler'][1];
                    
                    error_log("[Router Debug] Executando: {$controllerName}::{$methodName}");
                    
                    $controller = new $controllerName();
                    call_user_func_array([$controller, $methodName], $matches);
                } else {
                    call_user_func_array($route['handler'], $matches);
                }
                
                return;
            }
        }

        error_log("[Router Debug] Nenhuma rota encontrada para: {$requestMethod} {$requestPath}");
        http_response_code(404);
        echo json_encode(["error" => "Pagina nao encontrada"]);
    }
    
    private function createPatternFromPath($path) {
        $pattern = preg_replace('/{([^\/]+)}/', '([^\/]+)', $path);
        return "#^{$pattern}$#";
    }
}
