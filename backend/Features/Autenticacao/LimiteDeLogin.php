<?php

namespace App\Features\Autenticacao;

use PDO;
use Throwable;

// Limita as tentativas de login por conta + IP e, com folga maior, só por IP.
// O bloqueio é sempre temporário e por conta + IP: ninguém consegue trancar a conta de outra pessoa.
final class LimiteDeLogin
{
    public const LIMITE_POR_CONTA = 5;
    public const LIMITE_POR_IP = 50;
    public const JANELA = 900;
    public const BLOQUEIO = 900;

    // Verificado quando a conta não existe, para o login levar o mesmo tempo nos dois casos.
    // É o hash de um valor aleatório descartado, com o mesmo custo de UsuarioModel::gerarHashDeSenha.
    public const HASH_FICTICIO = '$argon2id$v=19$m=65536,t=4,p=3$YkFMcUpHNG1LbXAuMkYzRw$xTJTMcEQ4hip3X65KzUH7pB2a09oVFHdnJCTrTmVik4';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Conta a tentativa antes de verificar a senha, para valer também em paralelo.
     * @return int segundos de espera; 0 libera a tentativa
     */
    public function consumir(string $email, string $ip, ?int $agora = null): int
    {
        $agora ??= time();
        // A ordem é sempre a mesma, para duas requisições não se travarem uma à outra.
        $limites = [
            [$this->chaveDoIp($ip), self::LIMITE_POR_IP],
            [$this->chaveDaConta($email, $ip), self::LIMITE_POR_CONTA],
        ];

        // Fora da transação: criar a linha lá dentro causa deadlock entre tentativas simultâneas.
        foreach ($limites as [$chave]) {
            $this->db->prepare('INSERT IGNORE INTO tb_tentativas_login (chave, tentativas, janela_inicio, bloqueado_ate) VALUES (?, 0, ?, 0)')
                ->execute([$chave, $agora]);
        }

        $this->db->beginTransaction();
        try {
            $registros = [];
            $espera = 0;
            foreach ($limites as [$chave]) {
                $registro = $this->travar($chave);
                $registros[$chave] = $registro;
                $espera = max($espera, (int)$registro['bloqueado_ate'] - $agora);
            }

            if ($espera <= 0) {
                foreach ($limites as [$chave, $limite]) {
                    $this->contar($chave, $registros[$chave], $limite, $agora);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return max($espera, 0);
    }

    // A tentativa certa não conta: zera a conta e devolve a vaga do IP.
    public function registrarSucesso(string $email, string $ip): void
    {
        $this->removerVencidos();
        $this->db->prepare('DELETE FROM tb_tentativas_login WHERE chave = ?')
            ->execute([$this->chaveDaConta($email, $ip)]);
        $this->db->prepare('UPDATE tb_tentativas_login SET tentativas = GREATEST(tentativas - 1, 0), bloqueado_ate = 0 WHERE chave = ?')
            ->execute([$this->chaveDoIp($ip)]);
    }

    public function removerVencidos(?int $agora = null): void
    {
        $agora ??= time();
        $this->db->prepare('DELETE FROM tb_tentativas_login WHERE janela_inicio < ? AND bloqueado_ate < ?')
            ->execute([$agora - self::JANELA, $agora]);
    }

    private function travar(string $chave): array
    {
        $stmt = $this->db->prepare('SELECT tentativas, janela_inicio, bloqueado_ate FROM tb_tentativas_login WHERE chave = ? FOR UPDATE');
        $stmt->execute([$chave]);
        // Sem linha: removerVencidos a apagou nesse instante; conta como janela nova.
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['tentativas' => 0, 'janela_inicio' => 0, 'bloqueado_ate' => 0];
    }

    private function contar(string $chave, array $registro, int $limite, int $agora): void
    {
        $vencido = (int)$registro['janela_inicio'] <= $agora - self::JANELA || (int)$registro['bloqueado_ate'] > 0;
        $tentativas = $vencido ? 1 : (int)$registro['tentativas'] + 1;
        $janela = $vencido ? $agora : (int)$registro['janela_inicio'];
        $bloqueio = $tentativas >= $limite ? $agora + self::BLOQUEIO : 0;

        $this->db->prepare('UPDATE tb_tentativas_login SET tentativas = ?, janela_inicio = ?, bloqueado_ate = ? WHERE chave = ?')
            ->execute([$tentativas, $janela, $bloqueio, $chave]);
    }

    // Com hash: a tabela não guarda e-mail nem IP legíveis.
    private function chaveDaConta(string $email, string $ip): string
    {
        return hash('sha256', 'conta|' . mb_strtolower(trim($email)) . '|' . $ip);
    }

    private function chaveDoIp(string $ip): string
    {
        return hash('sha256', 'ip|' . $ip);
    }
}
