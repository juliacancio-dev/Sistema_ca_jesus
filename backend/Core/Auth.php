<?php

namespace App\Core;

class Auth
{
    public const LIMITE_INATIVIDADE = 7200;
    public const LIMITE_ABSOLUTO = 43200;

    public static function isLoggedIn()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']) &&
            isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']);
    }

    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function login($user, string $hashDaSenha)
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);

        $_SESSION['user'] = $user;
        $_SESSION['login_em'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['assinatura_senha'] = self::assinaturaDe($hashDaSenha);
    }

    // Identifica a senha atual sem guardar o hash na sessão. Quando a senha muda,
    // a assinatura deixa de conferir e as sessões abertas antes disso são encerradas.
    public static function assinaturaDe(string $hashDaSenha): string
    {
        return hash('sha256', $hashDaSenha);
    }

    public static function senhaInalterada(string $hashDaSenha): bool
    {
        $assinatura = $_SESSION['assinatura_senha'] ?? '';
        return $assinatura !== '' && hash_equals($assinatura, self::assinaturaDe($hashDaSenha));
    }

    // Quem trocou a própria senha continua logado, com um id de sessão novo.
    public static function renovarSessaoAposTrocaDeSenha(string $hashDaSenha): void
    {
        session_regenerate_id(true);
        $_SESSION['assinatura_senha'] = self::assinaturaDe($hashDaSenha);
    }

    public static function sessaoExpirada(): bool
    {
        $agora = time();
        $inativa = $agora - ($_SESSION['last_activity'] ?? 0) > self::LIMITE_INATIVIDADE;
        $antiga = $agora - ($_SESSION['login_em'] ?? 0) > self::LIMITE_ABSOLUTO;
        return $inativa || $antiga;
    }

    public static function registrarAtividade(): void
    {
        $_SESSION['last_activity'] = time();
    }

    // A sessão é só um cache: o banco é quem manda no tipo e no nome.
    public static function atualizarDados(int $tipo, string $nome): void
    {
        $_SESSION['user']['tipo'] = $tipo;
        $_SESSION['user']['nome'] = $nome;
    }

    public static function logout()
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function isAdmin()
    {
        $user = self::getUser();
        return $user && isset($user['tipo']) && (int)$user['tipo'] === 1;
    }
}
