<?php

namespace App\Tests\Core;

use App\Core\ErroDeNegocio;
use App\Core\RespostaJson;
use PHPUnit\Framework\TestCase;

final class RespostaJsonTest extends TestCase
{
    private array $servidorOriginal;

    protected function setUp(): void
    {
        $this->servidorOriginal = $_SERVER;
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_X_REQUESTED_WITH']);
        $_SERVER['REQUEST_URI'] = '/sistema_ca_jesus/public/produtos';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->servidorOriginal;
    }

    public function testNavegacaoNormalNaoEsperaJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml';
        $this->assertFalse(RespostaJson::esperada());
    }

    public function testRotaDeApiEsperaJson(): void
    {
        $_SERVER['REQUEST_URI'] = '/sistema_ca_jesus/public/api/produtos/buscar?id=1';
        $this->assertTrue(RespostaJson::esperada());
    }

    public function testFetchComAcceptJsonEsperaJson(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $this->assertTrue(RespostaJson::esperada());
    }

    public function testXmlHttpRequestEsperaJson(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue(RespostaJson::esperada());
    }

    public function testErroDeNegocioUsa422PorPadrao(): void
    {
        $erro = new ErroDeNegocio('Saída maior que o estoque disponível.');
        $this->assertSame(422, $erro->status());
        $this->assertSame('Saída maior que o estoque disponível.', $erro->getMessage());
    }

    public function testErroDeNegocioAceitaOutroStatus(): void
    {
        $this->assertSame(404, (new ErroDeNegocio('Produto não encontrado.', 404))->status());
    }

    public function testErroDeNegocioContinuaSendoDomainException(): void
    {
        $this->assertInstanceOf(\DomainException::class, new ErroDeNegocio('x'));
    }
}
