<?php
class DatabaseException extends Exception 
{
    public function __construct($message = "Erro no banco de dados", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class ValidationException extends Exception 
{
    private $errors = [];
    
    public function __construct($message = "Erro de validação", $errors = [], $code = 0) 
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }
    
    public function getErrors(): array 
    {
        return $this->errors;
    }
}
class AuthenticationException extends Exception 
{
    public function __construct($message = "Erro de autenticação", $code = 401) 
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
class NotFoundException extends Exception 
{
    public function __construct($message = "Recurso não encontrado", $code = 404) 
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
class ConflictException extends Exception 
{
    public function __construct($message = "Conflito de dados", $code = 409) 
    {
        parent::__construct($message, $code);
    }
}
class UnprocessableEntityException extends Exception 
{
    public function __construct($message = "Dados não processáveis", $code = 422) 
    {
        parent::__construct($message, $code);
    }
}
class FileException extends Exception 
{
    public function __construct($message = "Erro no arquivo", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class ConfigurationException extends Exception 
{
    public function __construct($message = "Erro de configuração", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class NetworkException extends Exception 
{
    public function __construct($message = "Erro de rede", $code = 0) 
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
class CsrfException extends SecurityException 
{
    public function __construct($message = "Token CSRF inválido", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class RateLimitException extends Exception 
{
    public function __construct($message = "Muitas tentativas", $code = 429) 
    {
        parent::__construct($message, $code);
    }
}
class MaintenanceException extends Exception 
{
    public function __construct($message = "Sistema em manutenção", $code = 503) 
    {
        parent::__construct($message, $code);
    }
}
class BusinessLogicException extends Exception 
{
    public function __construct($message = "Erro de regra de negócio", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class StockException extends BusinessLogicException 
{
    public function __construct($message = "Erro de estoque", $code = 0) 
    {
        parent::__construct($message, $code);
    }
}
class DuplicateException extends ConflictException 
{
    public function __construct($message = "Registro duplicado", $code = 409) 
    {
        parent::__construct($message, $code);
    }
}