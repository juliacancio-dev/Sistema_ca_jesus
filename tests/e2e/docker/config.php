<?php
// Configuração da aplicação nos testes E2E. 'development' mostra avisos do PHP
// na página, e os testes falham se encontrarem algum.
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'development');

if (!defined('DB_HOST')) define('DB_HOST', 'db');
if (!defined('DB_NAME')) define('DB_NAME', 'bd_sistema');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'e2e');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
