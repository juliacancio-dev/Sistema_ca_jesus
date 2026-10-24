<?php

namespace App\Core;

// Regra de negócio ou validação recusou a operação. A mensagem vai para o usuário,
// então nunca deve conter detalhe técnico.
// Estende DomainException para continuar caindo nos catch que já existem.
class ErroDeNegocio extends \DomainException
{
    public function __construct(string $mensagem, private int $status = 422)
    {
        parent::__construct($mensagem);
    }

    public function status(): int
    {
        return $this->status;
    }
}
