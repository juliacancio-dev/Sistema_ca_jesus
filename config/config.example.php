<?php
// Copie este arquivo para config/config.php e ajuste os valores do seu ambiente.
// config/config.php não é versionado.

// 'development' mostra erros na tela; em qualquer outro valor eles ficam ocultos.
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');

if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . '/sistema_ca_jesus');
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'bd_sistema');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
