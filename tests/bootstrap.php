<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Os testes usam um banco próprio, criado a partir do schema. Como o config.php
// só define o que ainda não foi definido, estes valores têm prioridade.
define('ENVIRONMENT', 'testing');
define('DB_NAME', 'bd_sistema_teste');

$config = __DIR__ . '/../config/config.php';
require_once file_exists($config) ? $config : __DIR__ . '/../config/config.example.php';

// Classes antigas, sem namespace: carregadas à mão até a etapa 9 (slices).
require_once __DIR__ . '/../app/helpers/FormatHelper.php';
