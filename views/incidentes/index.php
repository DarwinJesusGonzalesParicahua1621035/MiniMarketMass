<?php
/** @var Incidente[] $lista */
/** @var string|null $mensaje */
/** @var string|null $error */
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Incidentes — Minimarket Mass</title>
</head>
<body>
    <h1>Incidentes de la sucursal</h1>
    <!-- FERNANDEZ CHAUCAYANQUI JEAN MICHEL -->

    <?php if (!empty($mensaje)): ?>
        <p><?= $e($mensaje) ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p><strong>Error:</strong> <?= $e($error) ?></p>
    <?php endif; ?>

    <?php if ($lista === []): ?>
        <p>No hay incidentes registrados.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $inc): ?>
                    <tr>
                        <td><?= $inc->getId() ?></td>
                        <td><?= $e($inc->getTipo()) ?></td>
                        <td><?= $e($inc->getDescripcion()) ?></td>
                        <td><?= $e($inc->getFecha()) ?></td>
                        <td><?= $e($inc->getEstado()) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
