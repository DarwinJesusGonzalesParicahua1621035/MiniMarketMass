<?php
$clases = ['ENTRADA' => 'ok', 'SALIDA' => 'bad', 'AJUSTE' => 'b', 'MERMA' => 'y'];
$suman  = ['ENTRADA', 'AJUSTE'];
?>
<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="stock/movimientos">
    <h3>Historial <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="sucursal" onchange="this.form.submit()">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $id => $n): ?>
        <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="search" name="tipo" onchange="this.form.submit()">
      <option value="">Todos los tipos</option>
      <?php foreach (array_keys($clases) as $t): ?>
        <option value="<?= $t ?>" <?= $t === $tipoSel ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <a class="btn g" href="<?= Controller::url('stock/index') ?>">Ver stock</a>
    <a class="btn y" href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>Fecha</th><th>Sucursal</th><th>Producto</th><th>Tipo</th>
          <th style="text-align:right">Cantidad</th><th>Motivo</th><th>Empleado</th><th>Venta</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f):
          $suma = in_array($f['tipo'], $suman, true); ?>
        <tr>
          <td><?= e($f['fecha']) ?></td>
          <td><?= e($f['sucursal']) ?></td>
          <td><?= e($f['producto']) ?></td>
          <td><span class="tag <?= $clases[$f['tipo']] ?? '' ?>"><?= e($f['tipo']) ?></span></td>
          <td style="text-align:right;font-weight:700;color:var(--<?= $suma ? 'ok' : 'bad' ?>)"><?= $suma ? '+' : '−' ?><?= e($f['cantidad']) ?></td>
          <td><?= e($f['motivo'] ?? '—') ?></td>
          <td><?= e($f['empleado']) ?></td>
          <td><?= !empty($f['venta_id']) ? '<span class="tag b">#' . (int)$f['venta_id'] . '</span>' : '—' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin movimientos</div><?php endif; ?>
  </div>
</div>