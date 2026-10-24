<?php

use App\Core\RespostaJson;

class Router {
    private $routes = [];

    public function get($path, $handler, $middlewares = []) {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post($path, $handler, $middlewares = []) {
        $this->addRoute('POST', $path, $handler, $middlewares);
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
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = $this->caminhoDaRota(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        foreach ($this->routes as $route) {
            $pattern = $this->createPatternFromPath($route['path']);
            $matches = [];

            if ($route['method'] === $requestMethod && preg_match($pattern, $requestPath, $matches)) {
                array_shift($matches);

                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $middlewareInstance->handle();
                }

                $controller = new $route['handler'][0]();
                call_user_func_array([$controller, $route['handler'][1]], $matches);
                return;
            }
        }

        $this->naoEncontrado($requestPath);
    }

    // Remove o prefixo de publicação da URL. Aceita tanto ".../public/rota"
    // quanto ".../rota" (quando o .htaccess da raiz reescreve para public/).
    private function caminhoDaRota($path) {
        $prefixos = [BASE_URL, rtrim(str_replace('\\', '/', dirname(BASE_URL)), '/')];
        foreach ($prefixos as $prefixo) {
            if ($prefixo !== '' && strpos($path, $prefixo . '/') === 0) {
                return substr($path, strlen($prefixo));
            }
            if ($prefixo !== '' && $path === $prefixo) {
                return '/';
            }
        }
        return $path === '' ? '/' : $path;
    }

    private function naoEncontrado($requestPath) {
        if (RespostaJson::esperada()) {
            RespostaJson::enviar(false, 'Rota não encontrada.', status: 404);
        }
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Página não encontrada</title></head>';
        echo '<body style="font-family:sans-serif;max-width:720px;margin:40px auto;padding:0 16px;">';
        echo '<h2>Página não encontrada</h2>';
        echo '<p><a href="' . BASE_URL . '/dashboard">Voltar ao início</a></p>';
        echo '</body></html>';
    }

    private function createPatternFromPath($path) {
        $pattern = preg_replace('/{([^\/]+)}/', '([^\/]+)', $path);
        return "#^{$pattern}$#";
    }
}
