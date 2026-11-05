<?php

namespace App\Shared;

// Lê o que veio do navegador sem converter em silêncio: (int) e (float) nunca falham,
// e transformam "2abc" em 2 e "12,50" em 12. Valor inválido vira null, e quem chama recusa.
final class Entrada
{
    private const MAIOR_INT = 2147483647;

    // Um campo enviado como lista (nome[]=x) conta como vazio.
    public static function texto(mixed $valor): string
    {
        return is_string($valor) ? trim($valor) : '';
    }

    // Senha não passa por trim: espaço nas pontas faz parte dela.
    public static function senha(mixed $valor): string
    {
        return is_string($valor) ? $valor : '';
    }

    public static function inteiro(mixed $valor, int $minimo = 1, int $maximo = self::MAIOR_INT): ?int
    {
        if (is_int($valor)) {
            $valor = (string)$valor;
        }
        if (!is_string($valor) || !preg_match('/^\d{1,10}\z/', $valor)) {
            return null;
        }
        $numero = (int)$valor;
        return $numero >= $minimo && $numero <= $maximo ? $numero : null;
    }

    // Cabe em DECIMAL(10,2). Sem notação científica, que o input number aceita digitar.
    public static function preco(mixed $valor): ?float
    {
        if (!is_string($valor) || !preg_match('/^(\d{1,8}(\.\d{1,2})?|\.\d{1,2})\z/', $valor)) {
            return null;
        }
        return (float)$valor;
    }
}
