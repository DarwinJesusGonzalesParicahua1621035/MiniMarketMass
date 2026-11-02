<?php
$activo = $activo ?? '';
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));
$items = [
    'Operación' => [
        ['venta',    'cajero/venta',    '🛒', 'Nueva venta'],
        ['ventas',   'cajero/ventas',   '🧾', 'Mis ventas'],
        ['caja',     'cajero/caja',     '💵', 'Mi caja'],
    ],
    'Consulta' => [
        ['clientes', 'cajero/clientes', '👥', 'Clientes'],
    ],
];
?>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <nav>
    <?php foreach ($items as $grupo => $lista): ?>
      <h6><?= e($grupo) ?></h6>
      <?php foreach ($lista as [$clave, $ruta, $ico, $texto]): ?>
        <a href="<?= Controller::url($ruta) ?>" class="<?= $activo === $clave ? 'on' : '' ?>"><span><?= $ico ?></span><?= e($texto) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small>
      <a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></div></div>
</aside>