<?php
declare(strict_types=1);
require __DIR__ . '/../models/Producto.php';

$normal = new Producto(1, 1, '7750000000011', 'Yogurt Gloria fresa 1 L', 'UNIDAD', 7.90, true);
$limite = new Producto(2, 1, '7750000000042', 'Inca Kola 1.5 L', 'UNIDAD', 8.50, false);

$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>POO · Producto</title></head>
<body>
    <h1>Caso normal</h1>
    <p>Nombre: <?= $e($normal->getNombre()) ?></p>
    <p>Código de barras: <?= $e($normal->getCodigoBarras()) ?></p>
    <p>Precio (IGV incluido): S/ <?= number_format($normal->getPrecio(), 2) ?></p>
    <p>Subtotal: S/ <?= number_format($normal->subtotal(), 2) ?></p>
    <p>IGV 18%: S/ <?= number_format($normal->igv(), 2) ?></p>
    <p>¿Está activo?: <?= $normal->estaActivo() ? 'Sí' : 'No' ?></p>

    <h1>Caso límite (producto inactivo)</h1>
    <p>Nombre: <?= $e($limite->getNombre()) ?></p>
    <p>Precio (IGV incluido): S/ <?= number_format($limite->getPrecio(), 2) ?></p>
    <p>Subtotal: S/ <?= number_format($limite->subtotal(), 2) ?></p>
    <p>IGV 18%: S/ <?= number_format($limite->igv(), 2) ?></p>
    <p>¿Está activo?: <?= $limite->estaActivo() ? 'Sí' : 'No' ?></p>
</body>
</html>