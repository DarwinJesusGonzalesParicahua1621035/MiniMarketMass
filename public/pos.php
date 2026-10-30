<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/VentaController.php';

$controller = new VentaController($pdo);
$controller->index();