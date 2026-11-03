<?php
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
    http_response_code(500);
    echo '<h2>Error de base de datos</h2><p>' . e($e->getMessage()) . '</p>'
       . '<p>Revisa <code>config/config.php</code>, que MySQL esté activo en Laragon y que hayas cargado schema.sql y datos.sql.</p>';
}