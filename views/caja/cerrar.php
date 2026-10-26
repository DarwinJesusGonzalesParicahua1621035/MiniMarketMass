<?php
/** @var string|null $mensaje */
/** @var string|null $error */
/** @var HistoriaCaja|null $historia */
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cerrar caja — Minimarket Mass</title>
</head>
<body>
    <h1>Cerrar caja</h1>
    <!-- FERNANDEZ CHAUCAYANQUI JEAN MICHEL -->

    <?php if ($mensaje !== null): ?>
        <p><?= $e($mensaje) ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p><strong>Error:</strong> <?= $e($error) ?></p>
    <?php endif; ?>

    <?php if (isset($historia) && $historia !== null): ?>
        <ul>
            <li>Historia #<?= $historia->getId() ?></li>
            <li>Estado: <?= $e($historia->getEstado()) ?></li>
            <li>Monto inicial: S/ <?= number_format($historia->getMontoInicial(), 2) ?></li>
            <li>Monto final: S/ <?= number_format((float) $historia->getMontoFinal(), 2) ?></li>
            <li>Diferencia: S/ <?= number_format((float) $historia->diferencia(), 2) ?></li>
        </ul>
    <?php endif; ?>
</body>
</html>
