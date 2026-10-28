<form method="get" action="<?= Controller::base() ?>/index.php">
  <input type="hidden" name="r" value="stock/index">
  <select name="sucursal" onchange="this.form.submit()">
    <option value="">Todas las sucursales</option>
    <?php foreach ($sucursales as $id => $n): ?>
      <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
    <?php endforeach; ?>
  </select>
  <a href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
  <a href="<?= Controller::url('stock/movimientos') ?>">Ver historial</a>
</form>

<table>
  <thead><tr><th>Sucursal</th><th>Código</th><th>Producto</th><th>Unidad</th><th>Cantidad</th><th>Estado</th></tr></thead>
  <tbody>
  <?php foreach ($filas as $f): ?>
    <tr>
      <td><?= e($f['sucursal']) ?></td>
      <td><?= e($f['codigo_barras']) ?></td>
      <td><?= e($f['producto']) ?></td>
      <td><?= e($f['unidad']) ?></td>
      <td><?= e($f['cantidad']) ?></td>
      <td><?= (float)$f['cantidad'] < 10 ? 'Stock bajo' : 'OK' ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$filas): ?><tr><td colspan="6">Sin registros de stock.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php if ($faltantes): ?>
<form method="post" action="<?= Controller::url('stock/inicializar') ?>">
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <label>Producto sin stock en una sucursal:
    <select name="faltante">
      <?php foreach ($faltantes as $f): ?>
        <option value="<?= (int)$f['sucursal_id'] ?>-<?= (int)$f['producto_id'] ?>"><?= e($f['texto']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit">Inicializar en 0</button>
</form>
<?php endif; ?>