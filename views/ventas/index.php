<?php
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fd  = fn($d) => (int)substr($d, 8, 2) . ' ' . $meses[(int)substr($d, 5, 2) - 1] . ' ' . substr($d, 0, 4);
$fdt = fn($s) => (int)substr($s, 8, 2) . ' ' . $meses[(int)substr($s, 5, 2) - 1] . ', ' . substr($s, 11, 5);

$dia  = $f['dia'];
$prev = date('Y-m-d', strtotime("$dia -1 day"));
$next = date('Y-m-d', strtotime("$dia +1 day"));
$base = [
    'fecha' => $dia, 'q' => $f['q'], 'sucursal' => $f['sucursal'], 'tipo' => $f['tipo'], 'medio' => $f['medio'],
    'caja' => $f['caja'], 'min' => $f['min'], 'max' => $f['max'], 'todos' => $f['todos'] ? 1 : null,
    'orden' => $f['orden'], 'dir' => $f['dir'],
];
$link = fn(array $o = []) => Controller::url('ventas/index',
    array_filter(array_merge($base, $o), fn($v) => $v !== '' && $v !== null && $v !== 0));
$flecha = fn($c) => $f['orden'] === $c ? ($f['dir'] === 'desc' ? ' ▾' : ' ▴') : '';
$dirDe  = fn($c) => ($f['orden'] === $c && $f['dir'] === 'desc') ? 'asc' : 'desc';

$t  = (float)$rep['hoy']['total'];
$pt = (float)$rep['ayer']['total'];
$delta = $pt > 0 ? ($t - $pt) / $pt * 100 : null;

$barras = function (array $filas, bool $cap = false) {
    if (!$filas) {
        return '<div class="vt-mut">Sin ventas este día.</div>';
    }
    $max = max(1, (float)$filas[0]['t']);
    $h = '';
    foreach ($filas as $r) {
        $n = $cap ? ucfirst(strtolower($r['n'])) : $r['n'];
        $h .= '<div class="vt-row"><span>' . e($n) . '</span><div class="vt-bar"><i style="width:'
            . round($r['t'] / $max * 100, 1) . '%"></i></div><span class="vt-num">' . money($r['t']) . '</span></div>';
    }
    return $h;
};
$sumaLista = array_sum(array_map(fn($r) => $r['estado'] === 'EMITIDA' ? (float)$r['total'] : 0, $filas));
?>
<div class="vt-page">
  <div class="vt-head">
    <p class="vt-mut">Reporte del <?= $fd($dia) ?> · todas las sucursales</p>
    <div class="vt-daynav">
      <a class="vt-ib" href="<?= $link(['fecha' => $prev]) ?>" aria-label="Día anterior">‹</a>
      <input class="vt-fld" type="date" name="fecha" id="vtDia" form="vtFiltros" value="<?= e($dia) ?>" aria-label="Día del reporte">
      <a class="vt-ib" href="<?= $link(['fecha' => $next]) ?>" aria-label="Día siguiente">›</a>
    </div>
  </div>

  <section class="vt-report">
    <div class="vt-hero">
      <span>Total vendido</span>
      <span class="vt-big"><?= money($t) ?></span>
      <?php if ($delta !== null): ?>
        <span><b class="vt-delta <?= $delta >= 0 ? 'up' : 'dn' ?>"><?= $delta >= 0 ? '▲ ' : '▼ ' ?><?= number_format(abs($delta), 1) ?>%</b> frente al día anterior (<?= money($pt) ?>)</span>
      <?php else: ?>
        <span>Sin ventas el día anterior para comparar</span>
      <?php endif; ?>
    </div>
    <div class="vt-kpis">
      <div class="card vt-kpi"><span class="vt-mut">Ventas del día</span><b><?= (int)$rep['hoy']['n'] ?></b></div>
      <div class="card vt-kpi"><span class="vt-mut">Ticket promedio</span><b><?= $rep['hoy']['n'] ? money($t / $rep['hoy']['n']) : '—' ?></b></div>
      <div class="card vt-kpi"><span class="vt-mut">Boletas / Facturas</span><b><?= (int)$rep['hoy']['boletas'] ?> / <?= (int)$rep['hoy']['facturas'] ?></b></div>
    </div>
    <div class="vt-break">
      <div class="card vt-bk"><h3>Por sucursal</h3><?= $barras($rep['suc']) ?></div>
      <div class="card vt-bk"><h3>Por método de pago</h3><?= $barras($rep['pago'], true) ?></div>
    </div>
  </section>

  <div class="tw">
    <form method="get" id="vtFiltros" class="vt-filters">
      <input type="hidden" name="r" value="ventas/index">
      <input type="hidden" name="orden" value="<?= e($f['orden']) ?>">
      <input type="hidden" name="dir" value="<?= e($f['dir']) ?>">
      <label class="wide">Buscar
        <input class="vt-fld" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Comprobante, cliente o producto">
      </label>
      <label>Sucursal
        <select class="vt-fld" name="sucursal"><option value="0">Todas</option>
          <?php foreach ($op['sucursales'] as $id => $n): ?><option value="<?= (int)$id ?>" <?= $f['sucursal'] == $id ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Tipo
        <select class="vt-fld" name="tipo"><option value="">Boleta y factura</option>
          <option value="BOLETA" <?= $f['tipo'] === 'BOLETA' ? 'selected' : '' ?>>Boleta</option>
          <option value="FACTURA" <?= $f['tipo'] === 'FACTURA' ? 'selected' : '' ?>>Factura</option>
        </select>
      </label>
      <label>Método de pago
        <select class="vt-fld" name="medio"><option value="">Todos</option>
          <?php foreach (['EFECTIVO', 'PLIN', 'TARJETA', 'YAPE'] as $m): ?><option value="<?= $m ?>" <?= $f['medio'] === $m ? 'selected' : '' ?>><?= ucfirst(strtolower($m)) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Caja
        <select class="vt-fld" name="caja"><option value="0">Todas</option>
          <?php foreach ($op['cajas'] as $id => $n): ?><option value="<?= (int)$id ?>" <?= $f['caja'] == $id ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Total mínimo (S/)
        <input class="vt-fld" type="number" name="min" min="0" step="1" value="<?= $f['min'] !== null ? e($f['min']) : '' ?>" placeholder="0">
      </label>
      <label>Total máximo (S/)
        <input class="vt-fld" type="number" name="max" min="0" step="1" value="<?= $f['max'] !== null ? e($f['max']) : '' ?>" placeholder="Sin límite">
      </label>
      <label class="vt-chk"><input type="checkbox" name="todos" value="1" <?= $f['todos'] ? 'checked' : '' ?>> Todos los días</label>
      <button class="btn y" type="submit">Filtrar</button>
      <a class="vt-link" href="<?= Controller::url('ventas/index', ['fecha' => $dia]) ?>">Limpiar filtros</a>
    </form>

    <div class="scroll">
      <table>
        <thead><tr>
          <th>Comprobante</th><th>Sucursal</th><th>Tipo</th><th>Cliente</th><th>Pago</th>
          <th><a href="<?= $link(['orden' => 'fecha', 'dir' => $dirDe('fecha')]) ?>">Fecha<?= $flecha('fecha') ?></a></th>
          <th class="vt-num"><a href="<?= $link(['orden' => 'total', 'dir' => $dirDe('total')]) ?>">Total<?= $flecha('total') ?></a></th>
          <th class="vt-num">Detalle</th>
        </tr></thead>
        <tbody>
        <?php foreach ($filas as $r): $anu = $r['estado'] === 'ANULADA'; ?>
          <tr class="vt-tr <?= $anu ? 'vt-anulada' : '' ?>" data-id="<?= (int)$r['id'] ?>">
            <td><b><?= e($r['comprobante']) ?></b><?= $anu ? ' <span class="vt-chip anu">Anulada</span>' : '' ?></td>
            <td><?= e($r['sucursal']) ?></td>
            <td><span class="vt-chip <?= $r['tipo'] === 'FACTURA' ? 'fac' : 'bol' ?>"><?= ucfirst(strtolower($r['tipo'])) ?></span></td>
            <td class="<?= $r['cliente'] ? '' : 'vt-mut' ?>"><?= e($r['cliente'] ?: 'Cliente general') ?></td>
            <td><?= e(ucwords(strtolower($r['pagos'] ?? '—'), ', ')) ?></td>
            <td><?= $fdt($r['fecha']) ?></td>
            <td class="vt-num vt-total"><b><?= money($r['total']) ?></b></td>
            <td class="vt-num"><button type="button" class="vt-ib" data-open="<?= (int)$r['id'] ?>" aria-label="Ver detalle de <?= e($r['comprobante']) ?>">👁️</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">No hay ventas con estos filtros. Prueba otro día o limpia los filtros.</div><?php endif; ?>
    </div>
    <div class="vt-foot">
      <span><?= count($filas) ?> <?= count($filas) === 1 ? 'venta' : 'ventas' ?></span>
      <span>Total filtrado: <?= money($sumaLista) ?></span>
    </div>
  </div>
</div>

<div id="vtOv"></div>
<div id="vtDrawer" role="dialog" aria-modal="true" aria-labelledby="vtTitle" aria-hidden="true"
     data-url="<?= Controller::url('ventas/detalle') ?>" data-ver="<?= Controller::url('ventas/ver') ?>">
  <div class="vt-dh">
    <div><h2 id="vtTitle"></h2><span id="vtChip"></span></div>
    <button type="button" class="vt-ib" id="vtClose" aria-label="Cerrar detalle">✕</button>
  </div>
  <div class="vt-db" id="vtBody"></div>
  <div class="vt-df">
    <a class="btn g" id="vtVer" href="#">Ver / anular</a>
    <button type="button" class="btn y" id="vtPrint">Imprimir</button>
  </div>
</div>
<script src="<?= Controller::base() ?>/js/ventas.js"></script>