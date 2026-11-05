<?php
$menu = [
    'General'       => [['dashboard', '📊', 'Resumen']],
    'Operaciones'   => [['ventas', '🧾', 'Ventas'], ['caja', '💵', 'Cajas']],
    'Inventario'    => [['productos', '📦', 'Productos'], ['categorias', '🏷️', 'Categorías'], ['proveedores', '🏭', 'Proveedores'], ['stock', '🗃️', 'Stock']],
    'Personas'      => [['clientes', '👥', 'Clientes'], ['empleados', '🧑‍💼', 'Empleados'], ['asistencia', '🕘', 'Asistencia']],
    'Configuración' => [['sucursales', '🏬', 'Sucursales'], ['incidentes', '⚠️', 'Incidentes'], ['seguridad', '🔐', 'Seguridad']],
];
$actual = explode('/', $_GET['r'] ?? 'dashboard/index')[0];

$seg2fa = false;
try {
    $q2 = Database::pdo()->prepare('SELECT totp_activo FROM empleado WHERE id = ?');
    $q2->execute([$user['id'] ?? 0]);
    $seg2fa = (bool)$q2->fetchColumn();
} catch (Throwable $e) {}
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
  <div class="logo"><i>M</i><div>Tiendas Mass</div></div>
  <div class="rol-top"><?= e(mb_strtoupper($user['rol'])) ?></div>
  <nav>
    <?php foreach ($menu as $grupo => $items): ?>
      <h6><?= e($grupo) ?></h6>
      <?php foreach ($items as $m): ?>
        <a href="<?= Controller::url($m[0] . '/index') ?>" class="<?= $actual === $m[0] ? 'on' : '' ?>"><span><?= $m[1] ?></span><?= e($m[2]) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
   <div class="me"><b><?= e(mb_substr($user['nombre'], 0, 1)) ?></b>
    <div><?= e($user['nombre']) ?><small>
      <form method="post" action="<?= Controller::url('auth/salir') ?>" class="salir-form">
        <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
        <button type="submit">Cerrar sesión</button>
      </form></small></div></div>
</aside>
<main>
  <header><button id="menu">☰</button><h1><?= e($titulo ?? '') ?></h1></header>
  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>
  <?= $contenido ?>
</main>
<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function(){
  var a=document.querySelector('a[href*="r=seguridad"]'); if(!a) return;
  if(getComputedStyle(a).display.indexOf('flex')<0){a.style.display='flex';a.style.alignItems='center';}
  var on=<?= $seg2fa ? 'true' : 'false' ?>, b=document.createElement('span');
  b.textContent=on?'Activado':'Recomendado';
  b.style.cssText='margin-left:auto;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;'+(on?'background:#12b76a;color:#fff':'background:#111;color:#ffc400');
  a.appendChild(b);
})();
</script>
</body>
</html>