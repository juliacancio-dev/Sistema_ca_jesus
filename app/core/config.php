<?php
ini_set('session.cookie_lifetime', 86400); 
ini_set('session.gc_maxlifetime', 86400); 
session_set_cookie_params(86400);

$sessionDir = realpath(__DIR__ . '/../../armazenamento/sessions');
if (!file_exists($sessionDir) && !mkdir($sessionDir, 0755, true)) {
    error_log("ERRO: Falha ao criar diretório de sessões!");
}

ini_set('session.save_path', $sessionDir);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'bd_sistema'); 
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', '/sistema_ca_jesus');
define('BASE_PATH', realpath(__DIR__ . '/../../'));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

class Config {
    const APP_NAME = 'Sistema de Gestão de Estoque - C.A de Jesus';
    const APP_VERSION = '1.0.0';
    const APP_URL = 'http://localhost/sistema_ca_jesus/public';

    const SESSION_NAME = 'CA_JESUS_SESSION';
    const SESSION_TIMEOUT = 7200; // 2 horas

    const ITEMS_PER_PAGE = 20;
    
    const ESTOQUE_MINIMO_PADRAO = 10;
    const ESTOQUE_ALERTA_NIVEL = 5;
}
