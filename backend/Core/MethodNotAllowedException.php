<?php

namespace App\Core;

use Exception;

class MethodNotAllowedException extends Exception
{
    public function __construct($message = "Método não permitido", $code = 405)
    {
        parent::__construct($message, $code);
    }
}
