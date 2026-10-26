<?php
/** @var string|null $mensaje */
/** @var string|null $error */
$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar incidente — Minimarket Mass</title>
</head>
<body>
    <h1>Registrar incidente</h1>
    <!-- FERNANDEZ CHAUCAYANQUI JEAN MICHEL -->

    <?php if ($mensaje !== null): ?>
        <p><?= $e($mensaje) ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p><strong>Error:</strong> <?= $e($error) ?></p>
    <?php endif; ?>
</body>
</html>
