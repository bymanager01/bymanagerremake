<?php
// Configurações básicas
session_start();
date_default_timezone_set('America/Sao_Paulo');

// Carregar variáveis de ambiente (simulando .env)
$config = parse_ini_file(__DIR__ . '/../.env');

// Constantes do sistema
define('DB_HOST', $config['DB_HOST'] ?? 'localhost');
define('DB_NAME', $config['DB_NAME'] ?? 'bymanager');
define('DB_USER', $config['DB_USER'] ?? 'root');
define('DB_PASS', $config['DB_PASS'] ?? '');
define('BASE_URL', 'http://localhost/projeto-tcc/public');
define('SITE_NAME', 'ByManager');

// Auto-carregamento de classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../includes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Função para debug (remover em produção)
function debug($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?>