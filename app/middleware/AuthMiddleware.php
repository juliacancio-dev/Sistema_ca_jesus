<?php

class AuthMiddleware
{

    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        error_log("[AuthMiddleware] ===== INÍCIO handle() =====");
        error_log("[AuthMiddleware] SESSION: " . json_encode($_SESSION));
        error_log("[AuthMiddleware] session_id: " . session_id());
        error_log("[AuthMiddleware] is_logged_in: " . (AuthHelper::isLoggedIn() ? 'true' : 'false'));
        error_log("[AuthMiddleware] user: " . json_encode(AuthHelper::getUser()));
        error_log("[AuthMiddleware] REQUEST_URI: " . $_SERVER['REQUEST_URI']);

        $currentPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $loginPath = '/sistema_ca_jesus/public/login';
        $rootPath = '/sistema_ca_jesus/public';

        if (!AuthHelper::isLoggedIn()) {
            if ($currentPath === $rootPath || strpos($currentPath, $loginPath) === 0) {
                if ($currentPath === $loginPath) {
                    $erroDetalhes = [
                        'session_id' => session_id(),
                        'session' => $_SESSION,
                        'server' => $_SERVER,
                        'request_uri' => $_SERVER['REQUEST_URI'],
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
                    ];
                    $erroDetalhesJson = json_encode($erroDetalhes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    error_log("[AuthMiddleware] LOOP/REDIRECT DETECTADO: $erroDetalhesJson");
                    if (class_exists('LogHelper')) {
                        LogHelper::registrar('error', 'LOOP/REDIRECT DETECTADO NO LOGIN', 'Middleware', $erroDetalhesJson);
                    }
                    http_response_code(500);
                    echo '<h2>Erro de autenticação: Loop de redirecionamento detectado.</h2>';
                    echo '<pre>' . htmlspecialchars($erroDetalhesJson) . '</pre>';
                    exit;
                }
                return;
            }
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'ATENÇÃO: Usuário logado não encontrado ou sessão expirada. Faça login novamente.';
            header('Location: ' . $loginPath);
            exit;
        }
        AuthHelper::checkSessionTimeout();
    }
}
