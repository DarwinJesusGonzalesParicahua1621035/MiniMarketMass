<?php
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fd  = fn($d) => (int)substr($d, 8, 2) . ' ' . $meses[(int)substr($d, 5, 2) - 1] . ' ' . substr($d, 0, 4);
$fdt = fn($s) => (int)substr($s, 8, 2) . ' ' . $meses[(int)substr($s, 5, 2) - 1] . ', ' . substr($s, 11, 5);

$base = [
    'p' => $f['p'], 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'sucursal' => $f['sucursal'],
    'resp' => $f['resp'], 'estado' => $f['estado'], 'dif' => $f['dif'], 'q' => $f['q'],
    'orden' => $f['orden'], 'dir' => $f['dir'], 'ps' => $f['ps'],
];
$limpio = fn(array $a) => array_filter($a, fn($v) => $v !== '' && $v !== null && $v !== 0);
$link   = fn(array $o = []) => Controller::url('caja/index', $limpio(array_merge($base, $o)));
$flecha = fn($c) => $f['orden'] === $c ? ($f['dir'] === 'desc' ? ' ▾' : ' ▴') : '';
$dirDe  = fn($c) => ($f['orden'] === $c && $f['dir'] === 'desc') ? 'asc' : 'desc';

if ($f['desde'] === '' && $f['hasta'] === '') {
    $periodo = 'todo el historial';
} elseif ($f['desde'] === $f['hasta']) {
    $periodo = $fd($f['desde']);
} else {
    $periodo = ($f['desde'] ? $fd($f['desde']) : 'el inicio') . ' – ' . ($f['hasta'] ? $fd($f['hasta']) : 'hoy');
}

$difTag = function ($t) {
    if ($t['dif'] === null) {
        return '<span class="cj-mut">—</span>';
    }
    $d = round((float)$t['dif'], 2);
    if ($d == 0) {
        return '<span class="cj-dif ok">Cuadra</span>';
    }
    return '<span class="cj-dif ' . ($d < 0 ? 'bad' : 'sob') . '">' . ($d < 0 ? 'Falta ' : 'Sobra ') . money(abs($d)) . '</span>';
};
$neto = round((float)$rep['neto'], 2);
$presets = ['hoy' => 'Hoy', '7' => '7 días', '30' => '30 días', 'mes' => 'Este mes', 'all' => 'Todo'];
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/caja.css">

<div class="cj-page">
  <div class="cj-head">
    <p class="cj-mut">Cada turno abre con un monto inicial y se cierra contando el efectivo de la caja.</p>
    <?php if ($esAdmin): ?><button type="button" class="btn g" id="cjNueva">+ Nueva caja</button><?php endif; ?>
  </div>

  <section class="cj-sec">
    <h2>Cajas ahora</h2>
    <div class="cj-live">
      <?php foreach ($cajas as $c): $ab = !empty($c['historia_id']); $esp = (float)$c['monto_inicial'] + (float)$c['ef']; ?>
        <div class="card cj-card <?= $ab ? 'on' : '' ?>">
          <div class="cj-ct">
            <div><b><?= e($c['nombre']) ?></b><span class="cj-mut"><?= e($c['sucursal']) ?></span></div>
            <span class="cj-st <?= $ab ? 'on' : '' ?>"><?= $ab ? 'Abierta' : 'Cerrada' ?></span>
          </div>
          <?php if ($ab): ?>
            <div class="cj-kv"><span>Responsable</span><b><?= e($c['responsable']) ?></b></div>
            <div class="cj-kv"><span>Desde</span><b><?= e($fdt($c['apertura'])) ?></b></div>
            <div class="cj-kv"><span>Ventas del turno</span><b><?= money($c['tot']) ?> · <?= (int)$c['n'] ?></b></div>
            <div class="cj-kv"><span>Efectivo esperado</span><b><?= money($esp) ?></b></div>
            <div class="cj-ca">
              <a class="btn g" href="<?= Controller::url('ventas/index', ['turno' => (int)$c['historia_id']]) ?>">Ver ventas</a>
              <button type="button" class="btn y" data-cerrar
                data-hid="<?= (int)$c['historia_id'] ?>" data-suc="<?= e($c['sucursal']) ?>" data-caja="<?= e($c['nombre']) ?>"
                data-ap="<?= e($fdt($c['apertura'])) ?>" data-ini="<?= e($c['monto_inicial']) ?>" data-ef="<?= e($c['ef']) ?>">Cerrar caja</button>
            </div>
          <?php else: ?>
            <p class="cj-mut cj-free">Sin turno abierto.</p>
            <div class="cj-ca">
              <button type="button" class="btn y" data-abrir data-id="<?= (int)$c['id'] ?>" data-suc="<?= e($c['sucursal']) ?>" data-nombre="<?= e($c['nombre']) ?>">Abrir caja</button>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$cajas): ?><div class="empty">Aún no hay cajas registradas.</div><?php endif; ?>
    </div>
  </section>

  <section class="cj-sec">
    <div class="cj-rh">
      <h2>Reporte de turnos</h2>
      <div class="cj-presets">
        <?php foreach ($presets as $k => $n): ?>
          <a class="cj-chip <?= (string)$f['p'] === (string)$k ? 'on' : '' ?>" href="<?= $link(['p' => $k, 'desde' => '', 'hasta' => '', 'pag' => 1]) ?>"><?= $n ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="cj-report">
      <div class="cj-hero">
        <span>Ventas en turnos</span>
        <span class="cj-big"><?= money($rep['tot']) ?></span>
        <span><?= (int)$rep['n'] ?> <?= (int)$rep['n'] === 1 ? 'turno' : 'turnos' ?> en <?= (int)$rep['dias'] ?> <?= (int)$rep['dias'] === 1 ? 'día' : 'días' ?> · <?= e($periodo) ?></span>
      </div>
      <div class="cj-kpis">
        <div class="card cj-kpi"><span class="cj-mut">Turnos abiertos</span><b><?= (int)$rep['abiertos'] ?></b></div>
        <div class="card cj-kpi"><span class="cj-mut">Con diferencia</span><b><?= (int)$rep['condif'] ?></b></div>
        <div class="card cj-kpi"><span class="cj-mut">Diferencia neta</span>
          <b class="<?= $neto < 0 ? 'cj-neg' : ($neto > 0 ? 'cj-pos' : '') ?>"><?= $neto > 0 ? '+' : '' ?><?= money($neto) ?></b></div>
      </div>
    </div>
  </section>

  <div class="tw">
    <form method="get" id="cjFiltros" class="cj-filters">
      <input type="hidden" name="r" value="caja/index">
      <input type="hidden" name="p" id="cjP" value="<?= e($f['p']) ?>">
      <input type="hidden" name="orden" value="<?= e($f['orden']) ?>">
      <input type="hidden" name="dir" value="<?= e($f['dir']) ?>">
      <label class="wide">Buscar
        <input class="cj-fld" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="Turno, caja o sucursal">
      </label>
      <label>Desde<input class="cj-fld" type="date" name="desde" id="cjDesde" value="<?= e($f['desde']) ?>"></label>
      <label>Hasta<input class="cj-fld" type="date" name="hasta" id="cjHasta" value="<?= e($f['hasta']) ?>"></label>
      <label>Sucursal
        <select class="cj-fld" name="sucursal"><option value="0">Todas</option>
          <?php foreach ($sucursales as $id => $n): ?><option value="<?= (int)$id ?>" <?= $f['sucursal'] == $id ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Responsable
        <select class="cj-fld" name="resp"><option value="0">Todos</option>
          <?php foreach ($responsables as $id => $n): ?><option value="<?= (int)$id ?>" <?= $f['resp'] == $id ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label>Estado
        <select class="cj-fld" name="estado"><option value="">Todos</option>
          <option value="ABIERTA" <?= $f['estado'] === 'ABIERTA' ? 'selected' : '' ?>>Abierto</option>
          <option value="CERRADA" <?= $f['estado'] === 'CERRADA' ? 'selected' : '' ?>>Cerrado</option>
        </select>
      </label>
      <label>Diferencia
        <select class="cj-fld" name="dif"><option value="">Todas</option>
          <option value="cuadra" <?= $f['dif'] === 'cuadra' ? 'selected' : '' ?>>Cuadra</option>
          <option value="falta" <?= $f['dif'] === 'falta' ? 'selected' : '' ?>>Falta</option>
          <option value="sobra" <?= $f['dif'] === 'sobra' ? 'selected' : '' ?>>Sobra</option>
        </select>
      </label>
      <button class="btn y" type="submit">Filtrar</button>
      <a class="cj-link" href="<?= Controller::url('caja/index') ?>">Limpiar filtros</a>
    </form>

    <div class="scroll">
      <table>
        <thead><tr>
          <th>Turno</th><th>Sucursal</th><th>Caja</th><th>Responsable</th>
          <th><a href="<?= $link(['orden' => 'apertura', 'dir' => $dirDe('apertura'), 'pag' => 1]) ?>">Apertura<?= $flecha('apertura') ?></a></th>
          <th>Cierre</th>
          <th class="cj-num"><a href="<?= $link(['orden' => 'tot', 'dir' => $dirDe('tot'), 'pag' => 1]) ?>">Ventas<?= $flecha('tot') ?></a></th>
          <th class="cj-num"><a href="<?= $link(['orden' => 'dif', 'dir' => $dirDe('dif'), 'pag' => 1]) ?>">Diferencia<?= $flecha('dif') ?></a></th>
          <th>Estado</th>
        </tr></thead>
        <tbody>
        <?php foreach ($filas as $t):
            $d = [
                'id' => (int)$t['id'], 'suc' => $t['sucursal'], 'caja' => $t['caja'], 'resp' => $t['resp'],
                'ap' => $t['apertura'], 'ci' => $t['cierre'], 'ini' => $t['ini'], 'n' => (int)$t['n'], 'tot' => $t['tot'],
                'ef' => $t['ef'], 'ya' => $t['ya'], 'pl' => $t['pl'], 'ta' => $t['ta'],
                'mf' => $t['monto_final'], 'dif' => $t['dif'], 'st' => $t['estado'],
            ]; ?>
          <tr class="cj-tr" tabindex="0" data-t="<?= e(json_encode($d, JSON_UNESCAPED_UNICODE)) ?>">
            <td><b>#<?= (int)$t['id'] ?></b></td>
            <td><?= e($t['sucursal']) ?></td>
            <td><?= e($t['caja']) ?></td>
            <td><?= e($t['resp']) ?></td>
            <td><?= $fdt($t['apertura']) ?></td>
            <td><?= $t['cierre'] ? $fdt($t['cierre']) : '<span class="cj-mut">—</span>' ?></td>
            <td class="cj-num"><b><?= money($t['tot']) ?></b></td>
            <td class="cj-num"><?= $difTag($t) ?></td>
            <td><span class="cj-st <?= $t['estado'] === 'ABIERTA' ? 'on' : '' ?>"><?= $t['estado'] === 'ABIERTA' ? 'Abierto' : 'Cerrado' ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">No hay turnos con estos filtros. Prueba otro periodo o limpia los filtros.</div><?php endif; ?>
    </div>

    <div class="cj-foot">
      <span><?= (int)$rep['n'] ?> <?= (int)$rep['n'] === 1 ? 'turno' : 'turnos' ?></span>
      <div class="cj-pager">
        <label class="cj-mut">Filas
          <select class="cj-fld" name="ps" form="cjFiltros">
            <?php foreach ([25, 50, 100] as $n): ?><option value="<?= $n ?>" <?= $f['ps'] === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
          </select>
        </label>
        <?php if ($f['pag'] > 1): ?><a class="cj-ib" href="<?= $link(['pag' => $f['pag'] - 1]) ?>" aria-label="Anterior">‹</a><?php else: ?><span class="cj-ib off">‹</span><?php endif; ?>
        <span>Página <?= (int)$f['pag'] ?> de <?= (int)$paginas ?></span>
        <?php if ($f['pag'] < $paginas): ?><a class="cj-ib" href="<?= $link(['pag' => $f['pag'] + 1]) ?>" aria-label="Siguiente">›</a><?php else: ?><span class="cj-ib off">›</span><?php endif; ?>
        <a class="btn g" href="<?= Controller::url('caja/exportar', $limpio($base)) ?>">Exportar CSV</a>
      </div>
    </div>
  </div>
</div>

<div id="cjOv"></div>
<div id="cjDrawer" role="dialog" aria-modal="true" aria-labelledby="cjTitle" aria-hidden="true"
     data-ventas="<?= Controller::url('ventas/index') ?>">
  <div class="cj-dh">
    <div><h2 id="cjTitle"></h2><span id="cjChip"></span></div>
    <button type="button" class="cj-ib" id="cjClose" aria-label="Cerrar detalle">✕</button>
  </div>
  <div class="cj-db" id="cjBody"></div>
  <div class="cj-dfoot" id="cjFoot"></div>
</div>

<dialog class="cj-d" id="cjDOpen">
  <form method="post" action="<?= Controller::url('caja/abrir') ?>">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="caja_id" id="cjOpCaja">
    <h3>Abrir caja</h3>
    <p class="cj-mut" id="cjOpTitle"></p>
    <?php if ($esAdmin): ?>
      <label>Responsable
        <select class="cj-fld" name="empleado_id" required>
          <?php foreach ($responsables as $id => $n): ?><option value="<?= (int)$id ?>" <?= (int)$id === $yoId ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <label>Monto inicial (S/)
      <input class="cj-fld" type="number" name="monto_inicial" step="0.01" min="0" placeholder="Ej: 100.00" required>
    </label>
    <div class="cj-da">
      <button type="button" class="btn g" data-x>Cancelar</button>
      <button type="submit" class="btn y">Abrir caja</button>
    </div>
  </form>
</dialog>

<dialog class="cj-d" id="cjDClose">
  <form method="post" action="<?= Controller::url('caja/cerrar') ?>">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="historia_id" id="cjClHid">
    <h3>Cerrar caja</h3>
    <p class="cj-mut" id="cjClTitle"></p>
    <div class="cj-sum" id="cjClSum"></div>
    <label>Efectivo contado (S/)
      <input class="cj-fld" type="number" name="contado" id="cjClContado" step="0.01" min="0" placeholder="Cuenta el efectivo de la caja" required>
    </label>
    <p class="cj-diffline" id="cjClDif"></p>
    <div class="cj-da">
      <button type="button" class="btn g" data-x>Cancelar</button>
      <button type="submit" class="btn y">Cerrar caja</button>
    </div>
  </form>
</dialog>

<?php if ($esAdmin): ?>
<dialog class="cj-d" id="cjDNew">
  <form method="post" action="<?= Controller::url('caja/nueva') ?>">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <h3>Nueva caja</h3>
    <p class="cj-mut">El nombre no se puede repetir dentro de la misma sucursal.</p>
    <label>Sucursal
      <select class="cj-fld" name="sucursal_id" required>
        <?php foreach ($sucursales as $id => $n): ?><option value="<?= (int)$id ?>"><?= e($n) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Nombre
      <input class="cj-fld" type="text" name="nombre" maxlength="40" placeholder="Ej: Caja 3" required>
    </label>
    <div class="cj-da">
      <button type="button" class="btn g" data-x>Cancelar</button>
      <button type="submit" class="btn y">Guardar</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

<script src="<?= Controller::base() ?>/js/caja.js"></script>