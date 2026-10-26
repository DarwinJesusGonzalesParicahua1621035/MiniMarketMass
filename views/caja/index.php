<?php
/** @var Caja[] $cajas */
/** @var array<int, HistoriaCaja|null> $estado */
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cajas — Minimarket Mass</title>
</head>
<body>
    <h1>Cajas de la sucursal</h1>
    <!-- FERNANDEZ CHAUCAYANQUI JEAN MICHEL -->

    <?php if ($cajas === []): ?>
        <p>No hay cajas registradas.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Monto inicial</th>
                    <th>Apertura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cajas as $caja): ?>
                    <?php $hist = $estado[$caja->getId()] ?? null; ?>
                    <tr>
                        <td><?= $caja->getId() ?></td>
                        <td><?= $e($caja->getNombre()) ?></td>
                        <td><?= $hist ? 'ABIERTA' : 'CERRADA / sin turno' ?></td>
                        <td><?= $hist ? number_format($hist->getMontoInicial(), 2) : '—' ?></td>
                        <td><?= $hist ? $e($hist->getApertura()) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
