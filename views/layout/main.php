<?php
$menu = [
    'General'       => [['dashboard', '📊', 'Resumen']],
    'Operaciones'   => [['ventas', '🧾', 'Ventas'], ['caja', '💵', 'Cajas']],
    'Inventario'    => [['productos', '📦', 'Productos'], ['categorias', '🏷️', 'Categorías'], ['stock', '🗃️', 'Stock']],
    'Personas'      => [['clientes', '👥', 'Clientes'], ['empleados', '🧑‍💼', 'Empleados'], ['asistencia', '🕘', 'Asistencia']],
    'Configuración' => [['sucursales', '🏬', 'Sucursales'], ['incidentes', '⚠️', 'Incidentes']],
];
$actual = explode('/', $_GET['r'] ?? 'dashboard/index')[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Panel') ?> · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PANEL ADMIN</small></div></div>
  <nav>
    <?php foreach ($menu as $grupo => $items): ?>
      <h6><?= e($grupo) ?></h6>
      <?php foreach ($items as $m): ?>
        <a href="<?= Controller::url($m[0] . '/index') ?>" class="<?= $actual === $m[0] ? 'on' : '' ?>"><span><?= $m[1] ?></span><?= e($m[2]) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="me"><b><?= e(mb_substr($user['nombre'], 0, 1)) ?></b>
    <div><?= e($user['nombre']) ?><small><a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></small></div></div>
</aside>
<main>
  <header><button id="menu">☰</button><h1><?= e($titulo ?? '') ?></h1></header>
  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>
  <?= $contenido ?>
</main>
<script src="<?= Controller::base() ?>/js/app.js"></script>
</body>
</html>