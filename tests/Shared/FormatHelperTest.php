<?php

namespace App\Tests\Shared;

use FormatHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormatHelperTest extends TestCase
{
    public function testMoedaUsaVirgulaDecimalEPontoDeMilhar(): void
    {
        $this->assertSame('R$ 1.234,50', FormatHelper::currency(1234.5));
        $this->assertSame('R$ 0,00', FormatHelper::currency(0));
    }

    public function testMoedaComValorNaoNumericoViraZero(): void
    {
        $this->assertSame('R$ 0,00', FormatHelper::currency('abc'));
        $this->assertSame('R$ 0,00', FormatHelper::currency(null));
    }

    public function testData(): void
    {
        $this->assertSame('24/10/2026 13:05', FormatHelper::date('2026-10-24 13:05:00'));
        $this->assertSame('24/10/2026', FormatHelper::date('2026-10-24', 'd/m/Y'));
        $this->assertSame('', FormatHelper::date(''));
        $this->assertSame('Data inválida', FormatHelper::date('não é data'));
    }

    public static function telefones(): array
    {
        return [
            'fixo'         => ['1133334444', '(11) 3333-4444'],
            'celular'      => ['11999998888', '(11) 99999-8888'],
            'com máscara'  => ['(11) 99999-8888', '(11) 99999-8888'],
            'tamanho errado fica só com os dígitos' => ['12-345', '12345'],
        ];
    }

    #[DataProvider('telefones')]
    public function testTelefone(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, FormatHelper::phone($entrada));
    }

    public function testCpf(): void
    {
        $this->assertSame('123.456.789-09', FormatHelper::cpf('12345678909'));
        $this->assertSame('123.456.789-09', FormatHelper::cpf('123.456.789-09'));
        $this->assertSame('123', FormatHelper::cpf('123'));
    }

    public function testCnpj(): void
    {
        $this->assertSame('11.222.333/0001-81', FormatHelper::cnpj('11222333000181'));
        $this->assertSame('123', FormatHelper::cnpj('123'));
    }

    public function testEstoqueSemCasasDecimais(): void
    {
        $this->assertSame('1.234', FormatHelper::stock(1234));
        $this->assertSame('0', FormatHelper::stock(0));
    }
}
