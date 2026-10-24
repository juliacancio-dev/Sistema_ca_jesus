<?php
class DatabaseException extends Exception
{
    public function __construct($message = "Erro no banco de dados", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
class AuthorizationException extends Exception
{
    public function __construct($message = "Acesso negado", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
class ForbiddenException extends AuthorizationException
{
    public function __construct($message = "Acesso proibido", $code = 403)
    {
        parent::__construct($message, $code);
    }
}
class MethodNotAllowedException extends Exception
{
    public function __construct($message = "Método não permitido", $code = 405)
    {
        parent::__construct($message, $code);
    }
}
class SecurityException extends Exception
{
    public function __construct($message = "Erro de segurança", $code = 0)
    {
        parent::__construct($message, $code);
    }
}
