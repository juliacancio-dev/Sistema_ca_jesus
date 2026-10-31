<?php

namespace App\Tests\Features\Autenticacao;

use App\Features\Autenticacao\LimiteDeLogin;
use App\Tests\BancoDeTeste;
use PDO;
use PHPUnit\Framework\TestCase;

final class LimiteDeLoginTest extends TestCase
{
    private const IP = '10.0.0.1';
    private const AGORA = 1_800_000_000;

    private static PDO $pdo;
    private LimiteDeLogin $limite;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = BancoDeTeste::recriar();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('DELETE FROM tb_tentativas_login');
        $this->limite = new LimiteDeLogin(self::$pdo);
    }

    private function falhar(int $vezes, string $email = 'a@teste.com', string $ip = self::IP, int $agora = self::AGORA): void
    {
        for ($i = 0; $i < $vezes; $i++) {
            $this->assertSame(0, $this->limite->consumir($email, $ip, $agora), "A tentativa " . ($i + 1) . " deveria passar.");
        }
    }

    public function testAsCincoPrimeirasPassamEASextaEBloqueada(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $espera = $this->limite->consumir('a@teste.com', self::IP, self::AGORA);

        $this->assertSame(LimiteDeLogin::BLOQUEIO, $espera);
    }

    public function testBloqueioInformaOTempoRestanteEAcaba(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $this->assertSame(600, $this->limite->consumir('a@teste.com', self::IP, self::AGORA + LimiteDeLogin::BLOQUEIO - 600));
        $this->assertSame(0, $this->limite->consumir('a@teste.com', self::IP, self::AGORA + LimiteDeLogin::BLOQUEIO + 1));
    }

    public function testTentativaBloqueadaNaoEstendeOBloqueio(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $this->limite->consumir('a@teste.com', self::IP, self::AGORA + 100);

        $this->assertSame(LimiteDeLogin::BLOQUEIO - 200, $this->limite->consumir('a@teste.com', self::IP, self::AGORA + 200));
    }

    public function testOutraContaNaoEAfetada(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $this->assertSame(0, $this->limite->consumir('b@teste.com', self::IP, self::AGORA));
    }

    public function testMesmaContaEmOutroIpNaoEAfetada(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $this->assertSame(0, $this->limite->consumir('a@teste.com', '10.0.0.2', self::AGORA));
    }

    public function testEmailComMaiusculasEEspacosContaComoOMesmo(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);

        $this->assertGreaterThan(0, $this->limite->consumir('  A@TESTE.com ', self::IP, self::AGORA));
    }

    public function testTentativasAntigasSaemDaConta(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA - 1);

        $depois = self::AGORA + LimiteDeLogin::JANELA + 1;

        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA, agora: $depois);
    }

    public function testSucessoZeraAConta(): void
    {
        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);
        self::$pdo->exec('UPDATE tb_tentativas_login SET bloqueado_ate = 0');
        $this->limite->registrarSucesso('a@teste.com', self::IP);

        $this->falhar(LimiteDeLogin::LIMITE_POR_CONTA);
    }

    public function testLimitePorIpBarraContasDiferentes(): void
    {
        for ($i = 0; $i < LimiteDeLogin::LIMITE_POR_IP; $i++) {
            $this->falhar(1, "conta$i@teste.com");
        }

        $this->assertSame(LimiteDeLogin::BLOQUEIO, $this->limite->consumir('nova@teste.com', self::IP, self::AGORA));
        $this->assertSame(0, $this->limite->consumir('nova@teste.com', '10.0.0.2', self::AGORA));
    }

    public function testLoginsCertosNaoEnchemOLimiteDoIp(): void
    {
        for ($i = 0; $i < LimiteDeLogin::LIMITE_POR_IP + 5; $i++) {
            $this->falhar(1);
            $this->limite->registrarSucesso('a@teste.com', self::IP);
        }

        $this->assertSame(0, $this->limite->consumir('a@teste.com', self::IP, self::AGORA));
    }

    public function testNaoGuardaEmailNemIpLegiveis(): void
    {
        $this->falhar(1, 'pessoa@teste.com', '192.168.0.9');

        $chaves = implode(' ', self::$pdo->query('SELECT chave FROM tb_tentativas_login')->fetchAll(PDO::FETCH_COLUMN));

        $this->assertStringNotContainsString('pessoa', $chaves);
        $this->assertStringNotContainsString('192.168', $chaves);
    }

    public function testRemoveRegistrosVencidos(): void
    {
        $this->falhar(1);
        $this->falhar(1, 'b@teste.com', agora: self::AGORA + LimiteDeLogin::JANELA + 100);

        $this->limite->removerVencidos(self::AGORA + LimiteDeLogin::JANELA + 100);

        $restantes = (int)self::$pdo->query('SELECT COUNT(*) FROM tb_tentativas_login')->fetchColumn();
        $this->assertSame(2, $restantes, 'Sobram a conta b e o IP, com janela atual.');
    }

    public function testDuasConexoesNaMesmaContaNaoSeTravam(): void
    {
        $outra = new LimiteDeLogin(BancoDeTeste::conectar());

        $this->assertSame(0, $this->limite->consumir('a@teste.com', self::IP, self::AGORA));
        $this->assertSame(0, $outra->consumir('a@teste.com', self::IP, self::AGORA));
        $this->assertSame(2, (int)self::$pdo->query('SELECT MIN(tentativas) FROM tb_tentativas_login')->fetchColumn());
    }

    public function testHashFicticioUsaOMesmoCustoDasSenhasReais(): void
    {
        $ficticio = password_get_info(LimiteDeLogin::HASH_FICTICIO);
        $real = password_get_info(\App\Features\Usuarios\UsuarioModel::gerarHashDeSenha('qualquer'));

        $this->assertSame($real['algo'], $ficticio['algo']);
        $this->assertSame($real['options'], $ficticio['options']);
        $this->assertFalse(password_verify('', LimiteDeLogin::HASH_FICTICIO));
    }
}
