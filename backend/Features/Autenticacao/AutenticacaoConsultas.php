<?php

namespace App\Features\Autenticacao;

use App\Core\Database;
use PDO;

// Leitura própria de tbusuarios, cujo dono é Usuarios. A única escrita daqui é ultimo_login.
class AutenticacaoConsultas
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                id_usuario AS id,
                tipo_usuario AS tipo,
                nome_usuario AS nome,
                email_usuario AS email,
                senha_usuario AS senha,
                COALESCE(ativo_usuario, 1) AS ativo
            FROM tbusuarios
            WHERE email_usuario = ? AND deletado_em IS NULL
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function registrarLogin(int $id): void
    {
        $this->db->prepare('UPDATE tbusuarios SET ultimo_login = NOW() WHERE id_usuario = ?')->execute([$id]);
    }
}
