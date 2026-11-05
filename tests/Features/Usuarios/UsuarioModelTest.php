<?php

namespace App\Tests\Features\Usuarios;

use App\Core\ErroDeNegocio;
use App\Features\Usuarios\UsuarioModel;
use App\Tests\BancoDeTeste;
use PDO;
use PHPUnit\Framework\TestCase;

final class UsuarioModelTest extends TestCase
{
    private static PDO $pdo;
    private UsuarioModel $model;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = BancoDeTeste::recriar();
    }

    protected function setUp(): void
    {
        self::$pdo->exec('UPDATE tbmovimentacoes SET usuario_id = NULL');
        self::$pdo->exec('DELETE FROM tbusuarios');
        $this->model = new UsuarioModel(self::$pdo);
    }

    private function criar(int $tipo, string $email): int
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbusuarios (tipo_usuario, nome_usuario, email_usuario, senha_usuario, cpf_usuario, rg_usuario, telefone_usuario, sexo_usuario, cep_usuario)
            VALUES (?, 'Pessoa', ?, 'hash', ?, ?, '11999998888', 'Outro', '01001000')
        ");
        $stmt->execute([$tipo, $email, substr(md5($email), 0, 11), substr(md5($email), 11, 9)]);
        return (int)self::$pdo->lastInsertId();
    }

    private function edicao(int $id, int $tipo, int $ativo, string $nome = 'Pessoa Editada'): array
    {
        $atual = $this->model->buscarPorId($id) ?? self::$pdo->query("SELECT email_usuario AS email, cpf_usuario AS cpf, rg_usuario AS rg FROM tbusuarios WHERE id_usuario = $id")->fetch();
        return [
            'id' => $id, 'tipo' => $tipo, 'ativo' => $ativo, 'nome' => $nome,
            'email' => $atual['email'] ?? 'x@teste.com', 'cpf' => $atual['cpf'] ?? '1', 'rg' => $atual['rg'] ?? '1',
            'telefone' => '11999998888', 'sexo' => 'Outro', 'cep' => '01001000', 'endereco' => 'Rua',
            'cidade' => 'SP', 'estado' => 'SP', 'numero' => '1', 'complemento' => '', 'bairro' => 'Centro',
        ];
    }

    private function assertRecusaUltimoAdmin(callable $operacao, int $id): void
    {
        try {
            $operacao();
            $this->fail('O último admin ativo não pode sair.');
        } catch (ErroDeNegocio $e) {
            $this->assertSame(409, $e->status());
        }
        $this->assertFalse(self::$pdo->inTransaction(), 'A recusa precisa encerrar a transação.');

        $usuario = $this->model->buscarPorId($id);
        $this->assertSame(1, (int)$usuario['tipo']);
        $this->assertSame(1, (int)$usuario['ativo']);
    }

    public function testNaoExcluiOUltimoAdmin(): void
    {
        $admin = $this->criar(1, 'admin@teste.com');
        $this->assertRecusaUltimoAdmin(fn () => $this->model->deletar($admin), $admin);
    }

    public function testNaoDesativaOUltimoAdmin(): void
    {
        $admin = $this->criar(1, 'admin@teste.com');
        $this->assertRecusaUltimoAdmin(fn () => $this->model->alternarStatus($admin, 0), $admin);
    }

    public function testNaoRebaixaNemDesativaOUltimoAdminPelaEdicao(): void
    {
        $admin = $this->criar(1, 'admin@teste.com');
        $this->assertRecusaUltimoAdmin(fn () => $this->model->salvar($this->edicao($admin, 2, 1)), $admin);
        $this->assertRecusaUltimoAdmin(fn () => $this->model->salvar($this->edicao($admin, 1, 0)), $admin);
    }

    public function testUltimoAdminPodeEditarOsOutrosDados(): void
    {
        $admin = $this->criar(1, 'admin@teste.com');

        $this->assertTrue($this->model->salvar($this->edicao($admin, 1, 1, 'Nome Novo')));

        $this->assertSame('Nome Novo', $this->model->buscarPorId($admin)['nome']);
    }

    public function testAtivarOUltimoAdminJaAtivoNaoERecusado(): void
    {
        $admin = $this->criar(1, 'admin@teste.com');
        $this->assertTrue($this->model->alternarStatus($admin, 1));
    }

    public function testComDoisAdminsUmPodeSairMasOOutroNao(): void
    {
        $a = $this->criar(1, 'a@teste.com');
        $b = $this->criar(1, 'b@teste.com');

        $this->assertTrue($this->model->alternarStatus($b, 0));

        $this->assertRecusaUltimoAdmin(fn () => $this->model->deletar($a), $a);
    }

    public function testAdminInativoOuExcluidoNaoContaComoOutroAdmin(): void
    {
        $a = $this->criar(1, 'a@teste.com');
        $inativo = $this->criar(1, 'inativo@teste.com');
        $excluido = $this->criar(1, 'excluido@teste.com');
        self::$pdo->exec("UPDATE tbusuarios SET ativo_usuario = 0 WHERE id_usuario = $inativo");
        self::$pdo->exec("UPDATE tbusuarios SET deletado_em = NOW() WHERE id_usuario = $excluido");

        $this->assertRecusaUltimoAdmin(fn () => $this->model->alternarStatus($a, 0), $a);
    }

    public function testFuncionarioPodeSairComUmSoAdmin(): void
    {
        $this->criar(1, 'admin@teste.com');
        $funcionario = $this->criar(2, 'func@teste.com');

        $this->assertTrue($this->model->alternarStatus($funcionario, 0));
        $this->assertTrue($this->model->deletar($funcionario));
    }

    public function testSalvarSemMudarNadaContaComoSucesso(): void
    {
        $this->criar(1, 'admin@teste.com');
        $id = $this->criar(2, 'func@teste.com');

        $this->assertTrue($this->model->salvar($this->edicao($id, 2, 1)));
        $this->assertTrue($this->model->salvar($this->edicao($id, 2, 1)), 'Sem mudança, o MySQL informa zero linhas afetadas.');
        $this->assertTrue($this->model->alternarStatus($id, 1));
    }

    public function testIdInexistenteNaoEhSucesso(): void
    {
        $this->criar(1, 'admin@teste.com');

        $this->assertFalse($this->model->salvar($this->edicao(999999, 2, 1)));
        $this->assertFalse($this->model->alternarStatus(999999, 1));
        $this->assertFalse($this->model->deletar(999999));
    }

    public function testUsuarioExcluidoNaoEhMaisAlterado(): void
    {
        $this->criar(1, 'admin@teste.com');
        $id = $this->criar(2, 'func@teste.com');
        $edicao = $this->edicao($id, 1, 1, 'Nao Pode');
        $this->assertTrue($this->model->deletar($id));

        $this->assertFalse($this->model->salvar($edicao));
        $this->assertFalse($this->model->alternarStatus($id, 1));
        $this->assertFalse($this->model->deletar($id));

        $linha = self::$pdo->query("SELECT nome_usuario, tipo_usuario, ativo_usuario FROM tbusuarios WHERE id_usuario = $id")->fetch();
        $this->assertSame('Pessoa', $linha['nome_usuario']);
        $this->assertSame(2, (int)$linha['tipo_usuario']);
        $this->assertSame(0, (int)$linha['ativo_usuario']);
    }
}
