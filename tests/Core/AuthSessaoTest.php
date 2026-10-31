<?php

namespace App\Tests\Core;

use App\Core\Auth;
use PHPUnit\Framework\TestCase;

final class AuthSessaoTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [
            'user' => ['id' => 1, 'tipo' => 2, 'nome' => 'Ana'],
            'login_em' => time(),
            'last_activity' => time(),
            'assinatura_senha' => Auth::assinaturaDe('hash-atual'),
        ];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSessaoRecenteNaoExpira(): void
    {
        $this->assertFalse(Auth::sessaoExpirada());
    }

    public function testInatividadeDeMaisDeDuasHorasExpira(): void
    {
        $_SESSION['last_activity'] = time() - Auth::LIMITE_INATIVIDADE - 1;
        $this->assertTrue(Auth::sessaoExpirada());
    }

    public function testInatividadeNoLimiteNaoExpira(): void
    {
        $_SESSION['last_activity'] = time() - Auth::LIMITE_INATIVIDADE + 5;
        $this->assertFalse(Auth::sessaoExpirada());
    }

    public function testExpiraDepoisDoLimiteAbsolutoMesmoEmUso(): void
    {
        $_SESSION['login_em'] = time() - Auth::LIMITE_ABSOLUTO - 1;
        $_SESSION['last_activity'] = time();
        $this->assertTrue(Auth::sessaoExpirada());
    }

    public function testSessaoSemHorarioDeLoginExpira(): void
    {
        unset($_SESSION['login_em']);
        $this->assertTrue(Auth::sessaoExpirada());
    }

    public function testAssinaturaConfereComAMesmaSenha(): void
    {
        $this->assertTrue(Auth::senhaInalterada('hash-atual'));
    }

    public function testAssinaturaRecusaOutraSenha(): void
    {
        $this->assertFalse(Auth::senhaInalterada('hash-novo'));
    }

    public function testSessaoSemAssinaturaERecusada(): void
    {
        unset($_SESSION['assinatura_senha']);
        $this->assertFalse(Auth::senhaInalterada('hash-atual'));
    }

    public function testAssinaturaNaoGuardaOHash(): void
    {
        $this->assertStringNotContainsString('hash-atual', Auth::assinaturaDe('hash-atual'));
    }

    public function testAtualizarDadosMudaTipoENome(): void
    {
        Auth::atualizarDados(1, 'Ana Maria');
        $this->assertSame(1, $_SESSION['user']['tipo']);
        $this->assertSame('Ana Maria', $_SESSION['user']['nome']);
        $this->assertTrue(Auth::isAdmin());
    }
}
