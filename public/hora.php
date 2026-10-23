<?php
date_default_timezone_set("America/Lima");
$hoy = date("d/m/Y H:i");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reloj Mass</title>
</head>
<body>
    <h1>Minimarket Mass</h1>
    <p>Hora del servidor: <?= $hoy ?></p>
</body>
</html>