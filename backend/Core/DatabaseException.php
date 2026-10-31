<?php

namespace App\Core;

use Exception;

class DatabaseException extends Exception
{
    public function __construct($message = "Erro no banco de dados", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
