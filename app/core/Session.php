<?php

use App\Core\ErroDeNegocio;

// Mensagem de uma requisição para a próxima (ex.: depois de um redirect).
// É o único mecanismo de feedback ao usuário: o layout e a tela de login exibem.
class Session
{
    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['flash'] = ['type' => $tipo, 'message' => $mensagem];
    }

    public static function pullFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // Token CSRF: um por sessão, para funcionar com várias abas abertas.
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    // Formulários mandam o campo csrf_token; o fetch manda o header X-CSRF-Token.
    public static function exigirCsrf(): void
    {
        $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        $esperado = $_SESSION['csrf_token'] ?? '';

        if (!is_string($enviado) || $esperado === '' || !hash_equals($esperado, $enviado)) {
            throw new ErroDeNegocio('Esta página expirou. Recarregue e tente novamente.', 403);
        }
    }
}
