<form method="get" action="<?= Controller::base() ?>/index.php">
  <input type="hidden" name="r" value="stock/movimientos">
  <select name="sucursal" onchange="this.form.submit()">
    <option value="">Todas las sucursales</option>
    <?php foreach ($sucursales as $id => $n): ?>
      <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="tipo" onchange="this.form.submit()">
    <option value="">Todos los tipos</option>
    <?php foreach (['ENTRADA', 'SALIDA', 'AJUSTE', 'MERMA'] as $t): ?>
      <option value="<?= $t ?>" <?= $t === $tipoSel ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <a href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
  <a href="<?= Controller::url('stock/index') ?>">Ver stock</a>
</form>

<table>
  <thead><tr><th>Fecha</th><th>Sucursal</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Empleado</th><th>Venta</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?= e($f['fecha']) ?></td>
      <td><?= e($f['sucursal']) ?></td>
      <td><?= e($f['producto']) ?></td>
      <td><?= e($f['tipo']) ?></td>
      <td><?= e($f['cantidad']) ?></td>
      <td><?= e($f['motivo'] ?? '—') ?></td>
      <td><?= e($f['empleado']) ?></td>
      <td><?= e($f['venta_id'] ?? '—') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$filas): ?><tr><td colspan="8">Sin movimientos.</td></tr><?php endif; ?>
  </tbody>
</table>