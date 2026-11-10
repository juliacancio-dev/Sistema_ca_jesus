<?php

namespace App\Features\Relatorios;

use App\Core\ErroDeNegocio;
use DateTimeImmutable;

// Quem decide o intervalo é o período escolhido. As datas só valem no "personalizado":
// antes, as datas vinham sempre preenchidas e o seletor nunca tinha efeito.
final class PeriodoDoRelatorio
{
    /** @return array{0: string, 1: string} início e fim, em Y-m-d */
    public static function intervalo(string $periodo, string $inicio, string $fim, string $hoje): array
    {
        switch ($periodo) {
            case 'diario':
                return [$hoje, $hoje];
            case 'semanal':
                return [(new DateTimeImmutable($hoje))->modify('-6 days')->format('Y-m-d'), $hoje];
            case 'mensal':
                return [substr($hoje, 0, 8) . '01', $hoje];
            case 'anual':
                return [substr($hoje, 0, 5) . '01-01', $hoje];
            case 'personalizado':
                return self::personalizado($inicio, $fim);
        }
        throw new ErroDeNegocio('Período inválido.');
    }

    private static function personalizado(string $inicio, string $fim): array
    {
        if (!self::dataValida($inicio) || !self::dataValida($fim)) {
            throw new ErroDeNegocio('Informe datas válidas de início e fim.');
        }
        if ($inicio > $fim) {
            throw new ErroDeNegocio('A data de início não pode ser depois da data de fim.');
        }
        // A série diária do relatório financeiro tem uma linha por dia; sem limite,
        // 0001-01-01 a 9999-12-31 esgota a memória do PHP.
        if ($inicio < (new DateTimeImmutable($fim))->modify('-10 years')->format('Y-m-d')) {
            throw new ErroDeNegocio('O período pode ter no máximo 10 anos.');
        }
        return [$inicio, $fim];
    }

    // createFromFormat aceita 2026-02-31 e vira março; a volta para texto denuncia isso.
    private static function dataValida(string $data): bool
    {
        $convertida = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        return $convertida !== false && $convertida->format('Y-m-d') === $data;
    }
}
