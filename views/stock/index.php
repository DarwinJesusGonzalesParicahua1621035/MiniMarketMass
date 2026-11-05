<?php
// Umbral de "stock bajo" (el mismo que usa el Resumen).
const STK_BAJO = 10;

$estado = function (float $c): array {
    if ($c <= 0)         return ['sin',  'Sin stock'];
    if ($c < STK_BAJO)   return ['bajo', 'Stock bajo'];
    return ['ok', 'OK'];
};
$fechaCorta = function (?string $f): string {
    return $f ? date('d/m/Y', strtotime($f)) : '—';
};

/* ---- Resumen por sucursal y total ---- */
$resumen = [];
foreach ($sucursales as $id => $nombre) {
    $resumen[$id] = ['nombre' => $nombre, 'productos' => 0, 'unidades' => 0.0, 'valor' => 0.0, 'sin' => 0, 'bajo' => 0, 'ok' => 0];
}
$porSucursal = [];   // sucursal_id => filas
$matriz = [];        // producto_id => ['n','cod','cat','u','cel' => [sucursal_id => cantidad]]
foreach ($inventario as $f) {
    $sid = (int)$f['sucursal_id'];
    $c = (float)$f['cantidad'];
    $resumen[$sid]['productos']++;
    $resumen[$sid]['unidades'] += $c;
    $resumen[$sid]['valor']    += $c * (float)$f['precio'];
    $resumen[$sid][$estado($c)[0]]++;
    $porSucursal[$sid][] = $f;
    $pid = (int)$f['producto_id'];
    $matriz[$pid] ??= ['n' => $f['producto'], 'cod' => $f['codigo_barras'], 'cat' => $f['categoria'], 'u' => $f['unidad'], 'cel' => []];
    $matriz[$pid]['cel'][$sid] = $c;
}
$total = ['productos' => count($matriz), 'unidades' => 0.0, 'valor' => 0.0, 'sin' => 0, 'bajo' => 0, 'ok' => 0];
foreach ($resumen as $r) {
    foreach (['unidades', 'valor', 'sin', 'bajo', 'ok'] as $k) { $total[$k] += $r[$k]; }
}
$sel = $sucursalSel ? (int)$sucursalSel : 0;
$link = fn($id) => Controller::url('stock/index', $id ? ['sucursal' => $id] : []);

$tarjeta = function (string $titulo, array $r, string $href, bool $on, bool $todas = false) {
    $n = max(1, $r['sin'] + $r['bajo'] + $r['ok']);
    $pct = fn($v) => round($v / $n * 100, 1);
    ?>
    <a class="sk-card<?= $on ? ' on' : '' ?><?= $todas ? ' todas' : '' ?>" href="<?= e($href) ?>">
      <span class="sk-card-t"><?= e($titulo) ?></span>
      <b class="sk-card-n"><?= e(num($r['unidades'])) ?> <small>unidades</small></b>
      <span class="sk-card-v"><?= money($r['valor']) ?> en inventario</span>
      <span class="sk-salud" title="<?= (int)$r['ok'] ?> OK · <?= (int)$r['bajo'] ?> bajo · <?= (int)$r['sin'] ?> sin stock">
        <i class="ok" style="width:<?= $pct($r['ok']) ?>%"></i><i class="bajo" style="width:<?= $pct($r['bajo']) ?>%"></i><i class="sin" style="width:<?= $pct($r['sin']) ?>%"></i>
      </span>
      <span class="sk-card-f">
        <em><?= (int)$r['productos'] ?> prod.</em>
        <em class="b"><?= (int)$r['bajo'] ?> bajo</em>
        <em class="s"><?= (int)$r['sin'] ?> sin stock</em>
      </span>
    </a>
    <?php
};
?>
<div class="stk sk">

  <div class="stk-head">
    <div>
      <h2>Inventario por sucursal</h2>
      <p>Cada sucursal maneja su propio stock. Elige una para ver su reporte detallado.</p>
    </div>
    <div class="stk-actions">
      <a class="btn g" href="<?= Controller::url('stock/movimientos') ?>">Ver historial</a>
      <a class="btn y" href="<?= Controller::url('stock/registrar') ?>">+ Registrar movimiento</a>
    </div>
  </div>

  <div class="sk-cards">
    <?php $tarjeta('Todas las sucursales', $total, $link(0), $sel === 0, true); ?>
    <?php foreach ($resumen as $id => $r): $tarjeta($r['nombre'], $r, $link($id), $sel === (int)$id); endforeach; ?>
  </div>

  <?php if ($sel === 0): ?>
  <!-- ===== Vista general: producto x sucursal ===== -->
  <div class="tw sk-panel">
    <div class="sk-tools">
      <h3>Stock por sucursal <span class="tag b" id="sk-n"><?= count($matriz) ?></span></h3>
      <div class="sk-chips" id="sk-chips">
        <button type="button" class="sk-chip on" data-v="">Todos</button>
        <button type="button" class="sk-chip" data-v="alerta">Con alertas</button>
        <button type="button" class="sk-chip" data-v="ok">Sin alertas</button>
      </div>
      <input class="search" id="sk-q" placeholder="Buscar producto o código…" autocomplete="off">
    </div>
    <div class="scroll">
      <table id="sk-tabla">
        <thead><tr>
          <th>Producto</th><th>Categoría</th>
          <?php foreach ($sucursales as $nombre): ?><th class="sk-c"><?= e($nombre) ?></th><?php endforeach; ?>
          <th class="sk-c">Total</th>
        </tr></thead>
        <tbody>
        <?php foreach ($matriz as $pid => $m):
            $alerta = false; $suma = 0.0;
            foreach ($m['cel'] as $c) { $suma += $c; if ($c < STK_BAJO) { $alerta = true; } } ?>
          <tr data-est="<?= $alerta ? 'alerta' : 'ok' ?>" data-txt="<?= e(mb_strtolower($m['n'] . ' ' . $m['cod'] . ' ' . $m['cat'])) ?>">
            <td><b><?= e($m['n']) ?></b><small class="sk-sub"><?= e($m['cod']) ?></small></td>
            <td><span class="tag"><?= e($m['cat']) ?></span></td>
            <?php foreach ($sucursales as $sid => $nombre): ?>
              <td class="sk-c">
              <?php if (isset($m['cel'][$sid])): $c = $m['cel'][$sid]; ?>
                <span class="sk-pill <?= $estado($c)[0] ?>"><?= e(num($c)) ?></span>
              <?php else: ?><span class="sk-no" title="No se maneja en esta sucursal">—</span><?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="sk-c"><b><?= e(num($suma)) ?></b> <small class="sk-u"><?= e($m['u']) ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$matriz): ?><div class="empty">Aún no hay productos asignados a sucursales. Asígnalos desde Productos → Editar.</div><?php endif; ?>
    </div>
    <div class="sk-leyenda">
      <span><i class="sk-pill ok">12</i> OK</span>
      <span><i class="sk-pill bajo">5</i> Stock bajo (menos de <?= STK_BAJO ?>)</span>
      <span><i class="sk-pill sin">0</i> Sin stock</span>
      <span><i class="sk-no">—</i> No se maneja en la sucursal</span>
    </div>
  </div>

  <?php else:
    $filas = $porSucursal[$sel] ?? [];
    $maxC = 1.0;
    foreach ($filas as $f) { $maxC = max($maxC, (float)$f['cantidad']); }
    $r = $resumen[$sel];
  ?>
  <!-- ===== Reporte de una sucursal ===== -->
  <div class="sk-kpis">
    <div class="sk-kpi"><p>Productos</p><b><?= (int)$r['productos'] ?></b></div>
    <div class="sk-kpi"><p>Unidades en stock</p><b><?= e(num($r['unidades'])) ?></b></div>
    <div class="sk-kpi"><p>Valor del inventario</p><b><?= money($r['valor']) ?></b></div>
    <div class="sk-kpi r"><p>Sin stock</p><b><?= (int)$r['sin'] ?></b></div>
    <div class="sk-kpi y"><p>Stock bajo</p><b><?= (int)$r['bajo'] ?></b></div>
  </div>

  <div class="tw sk-panel">
    <div class="sk-tools">
      <h3><?= e($r['nombre']) ?> <span class="tag b" id="sk-n"><?= count($filas) ?></span></h3>
      <div class="sk-chips" id="sk-chips">
        <button type="button" class="sk-chip on" data-v="">Todos <em><?= (int)$r['productos'] ?></em></button>
        <button type="button" class="sk-chip" data-v="sin">Sin stock <em><?= (int)$r['sin'] ?></em></button>
        <button type="button" class="sk-chip" data-v="bajo">Bajo <em><?= (int)$r['bajo'] ?></em></button>
        <button type="button" class="sk-chip" data-v="ok">OK <em><?= (int)$r['ok'] ?></em></button>
      </div>
      <input class="search" id="sk-q" placeholder="Buscar producto o código…" autocomplete="off">
    </div>
    <div class="scroll">
      <table id="sk-tabla">
        <thead><tr>
          <th>Producto</th><th>Categoría</th><th style="min-width:200px">Stock</th>
          <th>Estado</th><th class="sk-r">Valor</th><th>Último mov.</th><th class="sk-r">Acción</th>
        </tr></thead>
        <tbody>
        <?php foreach ($filas as $f):
            $c = (float)$f['cantidad'];
            [$k, $txt] = $estado($c);
            $mov = Controller::url('stock/registrar') . '&suc=' . urlencode($f['sucursal']) . '&cod=' . urlencode($f['codigo_barras']); ?>
          <tr data-est="<?= $k ?>" data-txt="<?= e(mb_strtolower($f['producto'] . ' ' . $f['codigo_barras'] . ' ' . $f['categoria'])) ?>">
            <td><b><?= e($f['producto']) ?></b><small class="sk-sub"><?= e($f['codigo_barras']) ?></small></td>
            <td><span class="tag"><?= e($f['categoria']) ?></span></td>
            <td>
              <div class="sk-stock">
                <b><?= e(num($c)) ?></b> <small class="sk-u"><?= e($f['unidad']) ?></small>
                <span class="sk-bar"><i class="<?= $k ?>" style="width:<?= $c <= 0 ? 0 : max(4, round($c / $maxC * 100, 1)) ?>%"></i></span>
              </div>
            </td>
            <td><span class="sk-est <?= $k ?>"><?= $txt ?></span></td>
            <td class="sk-r"><?= money($c * (float)$f['precio']) ?></td>
            <td><?= $fechaCorta($f['ultimo']) ?></td>
            <td class="sk-r"><a class="sk-mov" href="<?= e($mov) ?>">± Movimiento</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">Esta sucursal aún no tiene productos. Asígnalos desde Productos → Editar.</div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
(function () {
  var rows = [].slice.call(document.querySelectorAll('#sk-tabla tbody tr'));
  var q = document.getElementById('sk-q'), n = document.getElementById('sk-n'), est = '';
  function aplicar() {
    var t = q.value.trim().toLowerCase(), c = 0;
    rows.forEach(function (r) {
      var ok = (!est || r.dataset.est === est) && (!t || r.dataset.txt.indexOf(t) > -1);
      r.style.display = ok ? '' : 'none';
      if (ok) c++;
    });
    n.textContent = c;
  }
  [].slice.call(document.querySelectorAll('#sk-chips .sk-chip')).forEach(function (b) {
    b.addEventListener('click', function () {
      [].slice.call(document.querySelectorAll('#sk-chips .sk-chip')).forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on'); est = b.dataset.v; aplicar();
    });
  });
  q.addEventListener('input', aplicar);
})();
</script>
