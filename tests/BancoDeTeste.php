<?php

namespace App\Tests;

use PDO;
use RuntimeException;

// Recria o banco de teste a partir de database/schema.sql.
final class BancoDeTeste
{
    private const NOME = 'bd_sistema_teste';

    public static function recriar(): PDO
    {
        // Este método apaga o banco: nunca pode rodar contra outro que não o de teste.
        if (DB_NAME !== self::NOME) {
            throw new RuntimeException('Os testes só podem rodar em ' . self::NOME . '.');
        }

        $pdo = self::abrir('mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET);
        $pdo->exec('DROP DATABASE IF EXISTS ' . self::NOME);
        $pdo->exec('CREATE DATABASE ' . self::NOME . ' CHARACTER SET utf8mb4');
        $pdo->exec('USE ' . self::NOME);

        foreach (self::comandos(file_get_contents(__DIR__ . '/../database/schema.sql')) as $comando) {
            $pdo->exec($comando);
        }
        return $pdo;
    }

    public static function conectar(): PDO
    {
        return self::abrir('mysql:host=' . DB_HOST . ';dbname=' . self::NOME . ';charset=' . DB_CHARSET);
    }

    private static function abrir(string $dsn): PDO
    {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    // DELIMITER é um comando do cliente mysql, que o PDO não entende.
    private static function comandos(string $sql): array
    {
        $comandos = [];
        $atual = '';
        $delimitador = ';';
        foreach (preg_split('/\R/', $sql) as $linha) {
            if (preg_match('/^DELIMITER\s+(\S+)/', $linha, $m)) {
                $delimitador = $m[1];
                continue;
            }
            if (str_starts_with(trim($linha), '--')) {
                continue;
            }
            $atual .= $linha . "\n";
            if (str_ends_with(rtrim($linha), $delimitador)) {
                $comandos[] = substr(rtrim($atual), 0, -strlen($delimitador));
                $atual = '';
            }
        }
        return $comandos;
    }
}
