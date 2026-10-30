<?php
// SOLO PRUEBAS LOCALES. No hacer commit.
session_start();

$db = new PDO(
    'mysql:host=127.0.0.1;port=3307;dbname=minimarket_mass;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

$cargar = [
    'controllers/Ayuda.php',
    'models/repositories/SucursalRepository.php',
    'controllers/SucursalController.php',
    'models/repositories/ClienteRepository.php',
    'controllers/ClienteController.php',
    'models/repositories/CapacitacionRepository.php',
    'controllers/CapacitacionController.php',
    'models/repositories/AsistenciaRepository.php',
    'controllers/AsistenciaController.php',
];
foreach ($cargar as $f) {
    $ruta = __DIR__ . '/../' . $f;
    if (is_file($ruta)) {
        require_once $ruta;
    }
}

$mapa = [
    'sucursales'     => 'SucursalController',
    'clientes'       => 'ClienteController',
    'capacitaciones' => 'CapacitacionController',
    'asistencia'     => 'AsistenciaController',
];
$m = $_GET['m'] ?? 'sucursales';
$a = $_GET['a'] ?? 'index';

if (!isset($mapa[$m]) || !class_exists($mapa[$m]) || !in_array($a, ['index', 'guardar', 'eliminar'], true)) {
    http_response_code(404);
    exit('No encontrado');
}

ob_start();
(new $mapa[$m]($db))->$a();
$contenido = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8">
<link rel="stylesheet" href="_probar.css"></head>
<body style="display:block;padding:24px"><?= $contenido ?></body></html>