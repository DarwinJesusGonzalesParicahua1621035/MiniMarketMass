<?php
require __DIR__ . '/../core/errores.php';
define('ROOT', dirname(__DIR__));
date_default_timezone_set('America/Lima');
session_start();
require ROOT . '/core/helpers.php';

spl_autoload_register(function ($c) {
    foreach (['core', 'controllers', 'models/repositories', 'models'] as $d) {
        $f = ROOT . "/$d/$c.php";
        if (is_file($f)) {
            require $f;
            return;
        }
    }
});

try {
    Router::run();
} catch (PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] PDOException: ' . $e->getMessage()
        . ' en ' . $e->getFile() . ':' . $e->getLine());
    mostrarPaginaError();
}