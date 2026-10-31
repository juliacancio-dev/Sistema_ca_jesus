<?php

namespace App\Core;

use Exception;

class ForbiddenException extends Exception
{
    public function __construct($message = "Acesso proibido", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
