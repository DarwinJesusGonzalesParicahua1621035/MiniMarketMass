<?php
$etq    = ['ENTRADA' => 'Entrada', 'VENTA' => 'Venta', 'SALIDA' => 'Salida'];
$clases = ['ENTRADA' => 'ok', 'VENTA' => 'b', 'SALIDA' => 'bad'];
$suman  = ['ENTRADA'];
$esAdmin = (Auth::user()['rol'] ?? '') === 'ADMINISTRADOR';
$fmtFecha = fn($f) => date('d/m/Y', strtotime($f)) . ' <small class="sk-sub" style="display:inline;margin-left:6px">' . date('H:i', strtotime($f)) . '</small>';

// Totales del listado mostrado
$tot = ['ENTRADA' => 0.0, 'VENTA' => 0.0, 'SALIDA' => 0.0];
foreach ($filas as $f) { $tot[$f['tipo']] = ($tot[$f['tipo']] ?? 0) + (float)$f['cantidad']; }
$url = fn(array $o = []) => Controller::url('stock/movimientos', array_filter(
    array_merge(['sucursal' => $sucursalSel, 'tipo' => $tipoSel], $o), fn($v) => $v !== null && $v !== ''));
?>
<div class="stk sk">

  <div class="stk-head">
    <div>
      <h2>Historial de movimientos</h2>
      <p>Entradas de mercadería, ventas y salidas manuales de cada sucursal.</p>
    </div>
    <div class="stk-actions">
      <a class="btn g" href="<?= Controller::url('stock/index') ?>">Ver stock</a>
      <a class="btn y" href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
    </div>
  </div>

  <div class="sk-kpis sk-kpis3">
    <div class="sk-kpi g"><p>Entradas</p><b>+<?= e(num($tot['ENTRADA'])) ?></b><small>unidades que ingresaron</small></div>
    <div class="sk-kpi b"><p>Ventas</p><b>−<?= e(num($tot['VENTA'])) ?></b><small>unidades vendidas</small></div>
    <div class="sk-kpi r"><p>Salidas</p><b>−<?= e(num($tot['SALIDA'])) ?></b><small>retiros manuales</small></div>
  </div>

  <div class="tw sk-panel">
    <form class="sk-tools" method="get" action="<?= Controller::base() ?>/index.php">
      <input type="hidden" name="r" value="stock/movimientos">
      <?php if ($tipoSel): ?><input type="hidden" name="tipo" value="<?= e($tipoSel) ?>"><?php endif; ?>
      <h3>Movimientos <span class="tag b" id="sk-n"><?= count($filas) ?></span></h3>
      <div class="sk-chips">
        <a class="sk-chip<?= $tipoSel ? '' : ' on' ?>" href="<?= e($url(['tipo' => null])) ?>">Todos</a>
        <?php foreach ($etq as $k => $t): ?>
          <a class="sk-chip<?= $tipoSel === $k ? ' on' : '' ?>" href="<?= e($url(['tipo' => $k])) ?>"><?= $t ?></a>
        <?php endforeach; ?>
      </div>
      <select class="search" name="sucursal" onchange="this.form.submit()">
        <option value="">Todas las sucursales</option>
        <?php foreach ($sucursales as $id => $n): ?>
          <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="search" id="sk-q" type="search" placeholder="Buscar producto, motivo…" autocomplete="off">
    </form>

    <div class="scroll">
      <table id="sk-tabla">
        <thead>
          <tr>
            <th>Fecha</th><th>Sucursal</th><th>Producto</th><th>Tipo</th>
            <th class="sk-r">Cantidad</th><th>Motivo</th><th>Empleado</th><th>Venta</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $f):
            $suma = in_array($f['tipo'], $suman, true); ?>
          <tr data-txt="<?= e(mb_strtolower($f['producto'] . ' ' . ($f['motivo'] ?? '') . ' ' . $f['empleado'] . ' ' . $f['sucursal'])) ?>">
            <td><?= $fmtFecha($f['fecha']) ?></td>
            <td><?= e($f['sucursal']) ?></td>
            <td><b><?= e($f['producto']) ?></b></td>
            <td><span class="tag <?= $clases[$f['tipo']] ?? '' ?>"><?= e($etq[$f['tipo']] ?? $f['tipo']) ?></span></td>
            <td class="sk-r sk-qty <?= $suma ? 'mas' : 'menos' ?>"><?= $suma ? '+' : '−' ?><?= e(num($f['cantidad'])) ?></td>
            <td><?= e($f['motivo'] ?? '—') ?></td>
            <td><?= e($f['empleado']) ?></td>
            <td>
              <?php if (!empty($f['venta_id'])): ?>
                <?php if ($esAdmin): ?>
                  <a class="tag b" href="<?= Controller::url('ventas/ver', ['id' => (int)$f['venta_id']]) ?>">#<?= (int)$f['venta_id'] ?></a>
                <?php else: ?><span class="tag b">#<?= (int)$f['venta_id'] ?></span><?php endif; ?>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">Sin movimientos con estos filtros</div><?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var q = document.getElementById('sk-q'), n = document.getElementById('sk-n');
  var rows = [].slice.call(document.querySelectorAll('#sk-tabla tbody tr'));
  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), c = 0;
    rows.forEach(function (r) {
      var ok = !t || r.dataset.txt.indexOf(t) > -1;
      r.style.display = ok ? '' : 'none';
      if (ok) c++;
    });
    n.textContent = c;
  });
  q.form.addEventListener('submit', function (e) { if (document.activeElement === q) e.preventDefault(); });
})();
</script>
