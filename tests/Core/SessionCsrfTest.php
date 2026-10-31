<?php

namespace App\Tests\Core;

use App\Core\ErroDeNegocio;
use PHPUnit\Framework\TestCase;
use Session;

final class SessionCsrfTest extends TestCase
{
    private array $servidorOriginal;

    protected function setUp(): void
    {
        $this->servidorOriginal = $_SERVER;
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->servidorOriginal;
        $_SESSION = [];
        $_POST = [];
    }

    public function testTokenEhEstavelNaMesmaSessao(): void
    {
        $token = Session::csrfToken();
        $this->assertSame(64, strlen($token));
        $this->assertSame($token, Session::csrfToken());
    }

    public function testCampoDoFormularioLevaOToken(): void
    {
        $this->assertStringContainsString('name="csrf_token" value="' . Session::csrfToken() . '"', Session::campoCsrf());
    }

    public function testAceitaTokenDoFormulario(): void
    {
        $_POST['csrf_token'] = Session::csrfToken();
        Session::exigirCsrf();
        $this->addToAssertionCount(1);
    }

    public function testAceitaTokenDoHeader(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = Session::csrfToken();
        Session::exigirCsrf();
        $this->addToAssertionCount(1);
    }

    public function testTokenContinuaValidoDepoisDeUsado(): void
    {
        $_POST['csrf_token'] = Session::csrfToken();
        Session::exigirCsrf();
        Session::exigirCsrf();
        $this->addToAssertionCount(1);
    }

    public function testRecusaSemToken(): void
    {
        Session::csrfToken();
        $this->assertRecusado();
    }

    public function testRecusaTokenErrado(): void
    {
        Session::csrfToken();
        $_POST['csrf_token'] = 'errado';
        $this->assertRecusado();
    }

    public function testRecusaTokenEnviadoComoLista(): void
    {
        Session::csrfToken();
        $_POST['csrf_token'] = ['x'];
        $this->assertRecusado();
    }

    public function testRecusaQuandoASessaoAindaNaoTemToken(): void
    {
        $_POST['csrf_token'] = '';
        $this->assertRecusado();
    }

    private function assertRecusado(): void
    {
        try {
            Session::exigirCsrf();
            $this->fail('Deveria ter recusado o token.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame(403, $e->status());
        }
    }
}
