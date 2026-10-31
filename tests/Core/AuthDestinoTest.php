<?php

namespace App\Tests\Core;

use App\Core\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthDestinoTest extends TestCase
{
    public static function destinosLocais(): array
    {
        return [
            'raiz' => ['/'],
            'rota' => ['/produtos'],
            'subpasta' => ['/sistema_ca_jesus/public/produtos'],
            'com busca' => ['/usuarios?search=ana&pagina=2'],
            'com acento codificado' => ['/usuarios?search=Jos%C3%A9'],
        ];
    }

    #[DataProvider('destinosLocais')]
    public function testAceitaDestinoLocal(string $destino): void
    {
        $this->assertTrue(Auth::destinoLocal($destino));
    }

    public static function destinosPerigosos(): array
    {
        return [
            'vazio' => [''],
            'outro site sem protocolo' => ['//evil.example/dashboard'],
            'outro site com protocolo' => ['https://evil.example/dashboard'],
            'barra invertida' => ['/\\evil.example/dashboard'],
            'duas barras invertidas' => ['\\\\evil.example'],
            'barra codificada' => ['/%2fevil.example/dashboard'],
            'barra codificada em maiúscula' => ['/%2Fevil.example'],
            'barra invertida codificada' => ['/%5cevil.example'],
            'duas barras codificadas' => ['%2f%2fevil.example'],
            'sem barra no início' => ['evil.example/dashboard'],
            'protocolo no meio' => ['/x?y=https://evil.example'],
            'javascript' => ['javascript:alert(1)'],
            'quebra de linha' => ["/produtos\r\nSet-Cookie: x=1"],
            'quebra de linha codificada' => ['/produtos%0d%0aSet-Cookie:x=1'],
            'tabulação antes das barras' => ["/\t/evil.example"],
        ];
    }

    #[DataProvider('destinosPerigosos')]
    public function testRecusaDestinoPerigoso(string $destino): void
    {
        $this->assertFalse(Auth::destinoLocal($destino));
    }
}
