<?php
// Opciones del menú del cajero. Por ahora son solo botones sin acción.
$menu = [
    'Operación' => [['🛒', 'Nueva venta'], ['🧾', 'Mis ventas'], ['💵', 'Mi caja']],
    'Consulta'  => [['👥', 'Clientes']],
];
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Caja · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="cajero">
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <nav>
    <?php foreach ($menu as $grupo => $items): ?>
      <h6><?= e($grupo) ?></h6>
      <?php foreach ($items as $m): ?>
        <button type="button"><span><?= $m[0] ?></span><?= e($m[1]) ?></button>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small>
      <a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></div></div>
</aside>
<main>
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Inicio<small>Bienvenido, <?= e($user['nombre']) ?></small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>
  <div class="card vacio">Selecciona una opción del menú.</div>
</main>
<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);
})();
</script>
</body>
</html>