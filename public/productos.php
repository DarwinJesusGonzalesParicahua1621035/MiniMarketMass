<?php
// TEMPORAL: sirve para probar el módulo mientras Darwin termina core/ y public/index.php.
// Cuando exista su router y su conexión, se reemplaza $db por la suya y se registran
// ProductoController y CategoriaController allí.
session_start();

require __DIR__ . '/../controllers/ayudas_productos.php';
require __DIR__ . '/../models/Categoria.php';
require __DIR__ . '/../models/Producto.php';
require __DIR__ . '/../controllers/CategoriaController.php';
require __DIR__ . '/../controllers/ProductoController.php';
require __DIR__ . '/../models/Caja.php';
require __DIR__ . '/../models/HistoriaCaja.php';
require __DIR__ . '/../models/Incidente.php';
require __DIR__ . '/../controllers/CajaController.php';
require __DIR__ . '/../controllers/HistoriaCajaController.php';
require __DIR__ . '/../controllers/IncidenteController.php';

$db = new PDO(
    'mysql:host=127.0.0.1;port=3307;dbname=minimarket_mass;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$modulo = $_GET['m'] ?? 'productos';
$accion = $_GET['a'] ?? 'index';

$controladores = [
    'productos' => new ProductoController($db),
    'categorias' => new CategoriaController($db),
    'caja' => new CajaController($db),
    'historia_caja' => new HistoriaCajaController($db),
    'incidentes' => new IncidenteController($db),
];
if (!isset($controladores[$modulo])) {
    http_response_code(404);
    exit('Módulo no encontrado');
}
$controlador = $controladores[$modulo];

if (!in_array($accion, $controlador::ACCIONES, true)) {
    http_response_code(404);
    exit('Acción no encontrada');
}
$controlador->$accion();