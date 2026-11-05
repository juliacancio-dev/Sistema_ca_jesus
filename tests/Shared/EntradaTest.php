<?php

namespace App\Tests\Shared;

use App\Shared\Entrada;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EntradaTest extends TestCase
{
    public static function inteirosInvalidos(): array
    {
        return [
            'notação científica' => ['1e3'],
            'letras no fim' => ['2abc'],
            'decimal' => ['1.9'],
            'lista' => [['50']],
            'vazio' => [''],
            'nulo' => [null],
            'negativo' => ['-1'],
            'zero abaixo do mínimo' => ['0'],
            'sinal' => ['+5'],
            'espaço' => [' 5'],
            'quebra de linha no fim' => ["5\n"],
            'maior que INT' => ['2147483648'],
            'número gigante' => ['99999999999999999999'],
        ];
    }

    #[DataProvider('inteirosInvalidos')]
    public function testRecusaInteiroInvalido(mixed $valor): void
    {
        $this->assertNull(Entrada::inteiro($valor));
    }

    public function testAceitaInteiroValido(): void
    {
        $this->assertSame(5, Entrada::inteiro('5'));
        $this->assertSame(5, Entrada::inteiro(5));
        $this->assertSame(2147483647, Entrada::inteiro('2147483647'));
    }

    public function testRespeitaAFaixaDoStatus(): void
    {
        $this->assertSame(0, Entrada::inteiro('0', 0, 1));
        $this->assertSame(1, Entrada::inteiro(1, 0, 1));
        $this->assertNull(Entrada::inteiro(2, 0, 1));
    }

    public static function precosInvalidos(): array
    {
        return [
            'vírgula' => ['12,50'],
            'texto' => ['abc'],
            'lista' => [['1']],
            'vazio' => [''],
            'nulo' => [null],
            'negativo' => ['-1'],
            'notação científica' => ['1e3'],
            'três casas' => ['1.999'],
            'infinito' => ['INF'],
            'não cabe na coluna' => ['100000000'],
        ];
    }

    #[DataProvider('precosInvalidos')]
    public function testRecusaPrecoInvalido(mixed $valor): void
    {
        $this->assertNull(Entrada::preco($valor));
    }

    public function testAceitaPrecoValido(): void
    {
        $this->assertSame(0.0, Entrada::preco('0'));
        $this->assertSame(12.5, Entrada::preco('12.50'));
        $this->assertSame(0.5, Entrada::preco('.5'));
        $this->assertSame(99999999.99, Entrada::preco('99999999.99'));
    }

    public function testTextoApagaEspacosEIgnoraLista(): void
    {
        $this->assertSame('Marca', Entrada::texto('  Marca '));
        $this->assertSame('', Entrada::texto('   '));
        $this->assertSame('', Entrada::texto(['x']));
        $this->assertSame('', Entrada::texto(null));
    }

    public function testSenhaMantemEspacosEIgnoraLista(): void
    {
        $this->assertSame(' senha ', Entrada::senha(' senha '));
        $this->assertSame('', Entrada::senha(['x']));
    }
}
