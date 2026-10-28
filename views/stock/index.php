<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="stock/index">
    <h3>Stock actual <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="sucursal" onchange="this.form.submit()">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $id => $n): ?>
        <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <a class="btn g" href="<?= Controller::url('stock/movimientos') ?>">Ver historial</a>
    <a class="btn y" href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>Sucursal</th><th>Código</th><th>Producto</th><th>Unidad</th>
          <th style="text-align:right">Cantidad</th><th>Estado</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f):
          $cant = (float)$f['cantidad'];
          if ($cant <= 0)      { $clase = 'bad'; $txt = 'Sin stock'; }
          elseif ($cant < 10)  { $clase = 'y';   $txt = 'Stock bajo'; }
          else                 { $clase = 'ok';  $txt = 'OK'; } ?>
        <tr>
          <td><?= e($f['sucursal']) ?></td>
          <td><?= e($f['codigo_barras']) ?></td>
          <td><?= e($f['producto']) ?></td>
          <td><?= e($f['unidad']) ?></td>
          <td style="text-align:right"><b><?= e($f['cantidad']) ?></b></td>
          <td><span class="tag <?= $clase ?>"><?= $txt ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin registros de stock</div><?php endif; ?>
  </div>
</div>

<?php if ($faltantes): ?>
<form class="card" method="post" action="<?= Controller::url('stock/inicializar') ?>" style="margin-bottom:20px">
  <h3>Productos sin stock en una sucursal</h3>
  <small style="color:var(--g4)">Crea la fila de stock en 0 para poder registrar movimientos.</small>
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <div class="tools" style="border:0;padding:14px 0 0">
    <select class="search" name="faltante" style="flex:1;min-width:220px">
      <?php foreach ($faltantes as $f): ?>
        <option value="<?= (int)$f['sucursal_id'] ?>-<?= (int)$f['producto_id'] ?>"><?= e($f['texto']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Inicializar en 0</button>
  </div>
</form>
<?php endif; ?>