<?php

namespace App\Core;

// A operação é permitida, mas só depois de o usuário confirmar. O front pergunta
// e reenvia com o campo de confirmação (requer_confirmacao no contrato JSON).
class ConfirmacaoNecessaria extends ErroDeNegocio
{
    public function __construct(string $mensagem)
    {
        parent::__construct($mensagem, 200);
    }
}
