<?php
// Copie este arquivo para backend/config/config.php e ajuste os valores do seu ambiente.
// backend/config/config.php não é versionado.

// 'development' mostra erros na tela; em qualquer outro valor eles ficam ocultos
// (e continuam sendo gravados em backend/storage/logs/php-erros.log).
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'bd_sistema');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
