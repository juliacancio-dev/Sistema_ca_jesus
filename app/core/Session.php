<?php

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
}
