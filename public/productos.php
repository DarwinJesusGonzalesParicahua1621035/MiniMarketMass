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

// Si en esta PC MySQL usa el puerto 3306, cambia port=3307 por port=3306.
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

$controlador = $modulo === 'categorias' ? new CategoriaController($db) : new ProductoController($db);

if (!in_array($accion, $controlador::ACCIONES, true)) {
    http_response_code(404);
    exit('Acción no encontrada');
}
$controlador->$accion();