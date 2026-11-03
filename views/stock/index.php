<?php
$total = count($filas);
$sin = 0; $bajo = 0; $ok = 0;
foreach ($filas as $f) {
    $c = (float)$f['cantidad'];
    if ($c <= 0)      { $sin++; }
    elseif ($c < 10)  { $bajo++; }
    else              { $ok++; }
}
?>
<div class="stk">

  <div class="stk-head">
    <div>
      <h2>Inventario por sucursal</h2>
      <p>Revisa cuánto hay de cada producto y registra entradas, salidas, ajustes o mermas.</p>
    </div>
    <div class="stk-actions">
      <a class="btn g" href="<?= Controller::url('stock/movimientos') ?>">Ver historial</a>
      <a class="btn y" href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
    </div>
  </div>

  <div class="stk-kpis">
    <div class="stk-kpi b"><i>📦</i><div><p>Productos</p><b><?= $total ?></b></div></div>
    <div class="stk-kpi r"><i>⛔</i><div><p>Sin stock</p><b><?= $sin ?></b></div></div>
    <div class="stk-kpi y"><i>⚠️</i><div><p>Stock bajo</p><b><?= $bajo ?></b></div></div>
    <div class="stk-kpi g"><i>✅</i><div><p>Stock OK</p><b><?= $ok ?></b></div></div>
  </div>

  <div class="tw">
    <form class="stk-filtros" method="get" action="<?= Controller::base() ?>/index.php">
      <input type="hidden" name="r" value="stock/index">
      <h3>Stock actual <span class="tag b"><?= $total ?></span></h3>
      <select class="search" name="sucursal" onchange="this.form.submit()">
        <option value="">Todas las sucursales</option>
        <?php foreach ($sucursales as $id => $n): ?>
          <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="search" placeholder="Buscar producto o código…" data-filtro="tabla">
    </form>

    <div class="scroll">
      <table id="tabla">
        <thead>
          <tr>
            <th>Sucursal</th><th>Código</th><th>Producto</th><th>Unidad</th>
            <th style="text-align:right">Cantidad</th><th>Estado</th><th style="text-align:right">Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $f):
            $cant = (float)$f['cantidad'];
            if ($cant <= 0)      { $clase = 'bad'; $txt = 'Sin stock'; }
            elseif ($cant < 10)  { $clase = 'y';   $txt = 'Stock bajo'; }
            else                 { $clase = 'ok';  $txt = 'OK'; }
            $editar = Controller::url('stock/registrar')
                    . '&suc=' . urlencode($f['sucursal'])
                    . '&cod=' . urlencode($f['codigo_barras']); ?>
          <tr>
            <td><?= e($f['sucursal']) ?></td>
            <td><?= e($f['codigo_barras']) ?></td>
            <td><b><?= e($f['producto']) ?></b></td>
            <td><?= e($f['unidad']) ?></td>
            <td style="text-align:right"><b><?= e($f['cantidad']) ?></b></td>
            <td><span class="tag <?= $clase ?>"><?= $txt ?></span></td>
            <td style="text-align:right"><a class="stk-edit" href="<?= e($editar) ?>">✏️ Editar</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">Sin registros de stock</div><?php endif; ?>
    </div>
  </div>

  <?php if ($faltantes):
      $fSuc = [];
      $fData = [];
      foreach ($faltantes as $f) {
          $fSuc[$f['sucursal_id']] = $f['sucursal'];
          $fData[] = ['s' => (int)$f['sucursal_id'], 'p' => (int)$f['producto_id'], 'n' => $f['producto']];
      } ?>
  <form class="stk-aviso" method="post" action="<?= Controller::url('stock/inicializar') ?>">
    <h3>⚠️ Productos sin stock en una sucursal</h3>
    <small>Elige la sucursal y el producto para crear su fila de stock en 0. Así podrás registrar movimientos.</small>
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="faltante" id="falVal" value="">
    <div class="fila">
      <label>Sucursal
        <select id="falSuc" required>
          <option value="">Elige una sucursal</option>
          <?php foreach ($fSuc as $id => $nombre): ?>
            <option value="<?= (int)$id ?>"><?= e($nombre) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Producto
        <select id="falProd" required disabled>
          <option value="">Primero elige la sucursal</option>
        </select>
      </label>
      <button class="btn" type="submit" id="falBtn" disabled>Inicializar en 0</button>
    </div>
  </form>
  <script>
  (function () {
    var D = <?= json_encode($fData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var suc = document.getElementById('falSuc'), prod = document.getElementById('falProd');
    var val = document.getElementById('falVal'), btn = document.getElementById('falBtn');
    suc.addEventListener('change', function () {
      prod.innerHTML = '';
      var o = document.createElement('option');
      o.value = '';
      o.textContent = suc.value ? 'Elige un producto' : 'Primero elige la sucursal';
      prod.appendChild(o);
      D.filter(function (r) { return String(r.s) === suc.value; }).forEach(function (r) {
        var x = document.createElement('option');
        x.value = r.p;
        x.textContent = r.n;
        prod.appendChild(x);
      });
      prod.disabled = !suc.value;
      val.value = '';
      btn.disabled = true;
    });
    prod.addEventListener('change', function () {
      val.value = prod.value ? suc.value + '-' + prod.value : '';
      btn.disabled = !prod.value;
    });
  })();
  </script>
  <?php endif; ?>

</div>