<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO(
                $dsn, 
                DB_USER, 
                DB_PASS, 
                $options
            );
        } catch (PDOException $e) {
            require_once __DIR__ . '/../helpers/ErrorHelper.php';
            ErrorHelper::handle($e, 'Erro de conexão com o banco de dados.');
            ErrorHelper::displayToast();
            throw new Exception('Erro de conexão com o banco de dados.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }
        return false;
    }
    
    public function inTransaction() {
        return $this->connection->inTransaction();
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    public function exec($sql) {
        return $this->connection->exec($sql);
    }

    public function executeTransaction(callable $callback) {
        $this->beginTransaction();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }
}