<?php

namespace App\Core;

// Formato único das respostas JSON (contrato em docs/arquitetura.md).
final class RespostaJson
{
    public static function enviar(
        bool $sucesso,
        ?string $mensagem = null,
        mixed $dados = null,
        int $status = 200,
        bool $requerConfirmacao = false
    ): never {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'sucesso' => $sucesso,
            'mensagem' => $mensagem,
            'dados' => $dados,
            'requer_confirmacao' => $requerConfirmacao,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // A requisição espera JSON: rota /api/ ou chamada feita por fetch com "Accept: application/json".
    public static function esperada(): bool
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

        return str_contains($caminho, '/api/') || str_contains($accept, 'application/json') || $xhr;
    }
}
