<?php

namespace App\Tests\Features\Relatorios;

use App\Core\ErroDeNegocio;
use App\Features\Relatorios\PeriodoDoRelatorio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PeriodoDoRelatorioTest extends TestCase
{
    private const HOJE = '2026-11-05';

    public static function periodos(): array
    {
        return [
            'diário' => ['diario', ['2026-11-05', '2026-11-05']],
            'semanal: hoje e os 6 dias antes' => ['semanal', ['2026-10-30', '2026-11-05']],
            'mensal: do dia 1 até hoje' => ['mensal', ['2026-11-01', '2026-11-05']],
            'anual: de janeiro até hoje' => ['anual', ['2026-01-01', '2026-11-05']],
        ];
    }

    #[DataProvider('periodos')]
    public function testCadaPeriodoTemOProprioIntervalo(string $periodo, array $esperado): void
    {
        $this->assertSame($esperado, PeriodoDoRelatorio::intervalo($periodo, '', '', self::HOJE));
    }

    public function testDatasSoValemNoPersonalizado(): void
    {
        $this->assertSame(['2026-01-01', '2026-11-05'], PeriodoDoRelatorio::intervalo('anual', '2026-10-01', '2026-10-31', self::HOJE));
        $this->assertSame(['2026-10-01', '2026-10-31'], PeriodoDoRelatorio::intervalo('personalizado', '2026-10-01', '2026-10-31', self::HOJE));
    }

    public function testSemanalAtravessaOAno(): void
    {
        $this->assertSame(['2026-12-27', '2027-01-02'], PeriodoDoRelatorio::intervalo('semanal', '', '', '2027-01-02'));
    }

    public function testPersonalizadoAceitaUmDiaEOLimite(): void
    {
        $this->assertSame(['2026-02-28', '2026-02-28'], PeriodoDoRelatorio::intervalo('personalizado', '2026-02-28', '2026-02-28', self::HOJE));
        $this->assertSame(['2016-11-05', '2026-11-05'], PeriodoDoRelatorio::intervalo('personalizado', '2016-11-05', '2026-11-05', self::HOJE));
    }

    public static function pedidosInvalidos(): array
    {
        return [
            'período desconhecido' => ['trimestral', '', ''],
            'período em maiúsculas' => ['MENSAL', '', ''],
            'personalizado sem datas' => ['personalizado', '', ''],
            'data em texto' => ['personalizado', 'abc', '2026-01-01'],
            'data inexistente' => ['personalizado', '2026-02-31', '2026-03-05'],
            'formato brasileiro' => ['personalizado', '01/10/2026', '31/10/2026'],
            'data com hora' => ['personalizado', '2026-10-01 00:00', '2026-10-31'],
            'início depois do fim' => ['personalizado', '2026-10-31', '2026-10-01'],
            'um dia além de 10 anos' => ['personalizado', '2016-11-04', '2026-11-05'],
            'período enorme' => ['personalizado', '0001-01-01', '9999-12-31'],
        ];
    }

    #[DataProvider('pedidosInvalidos')]
    public function testRecusaPedidoInvalido(string $periodo, string $inicio, string $fim): void
    {
        $this->expectException(ErroDeNegocio::class);
        PeriodoDoRelatorio::intervalo($periodo, $inicio, $fim, self::HOJE);
    }
}
