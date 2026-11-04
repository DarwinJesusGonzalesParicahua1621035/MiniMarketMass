<?php
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fdt = fn($s) => (int)substr($s, 8, 2) . ' ' . $meses[(int)substr($s, 5, 2) - 1] . ', ' . substr($s, 11, 5);

$base = [
    'f' => 1, 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'sucursal' => $f['sucursal'],
    'dif' => $f['dif'], 'q' => $f['q'],
    'orden' => $f['orden'], 'dir' => $f['dir'], 'ps' => $f['ps'],
];
$limpio = fn(array $a) => array_filter($a, fn($v) => $v !== '' && $v !== null && $v !== 0);
$link   = fn(array $o = []) => Controller::url('caja/index', $limpio(array_merge($base, $o)));
$flecha = fn($c) => $f['orden'] === $c ? ($f['dir'] === 'desc' ? ' ▾' : ' ▴') : '';
$dirDe  = fn($c) => ($f['orden'] === $c && $f['dir'] === 'desc') ? 'asc' : 'desc';

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
$chips = function (string $name, array $ops, $actual): string {
    $h = '';
    foreach ($ops as $v => $txt) {
        $h .= '<label class="cj-opt"><input type="radio" name="' . $name . '" value="' . e($v) . '"'
            . ((string)$actual === (string)$v ? ' checked' : '') . '><span>' . e($txt) . '</span></label>';
    }
    return $h;
};
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/caja.css">

<div class="cj-page">
  <div class="cj-head">
    <?php if ($esAdmin): ?>
      <p class="cj-mut">Puedes crear cajas y ver sus ventas. Abrir y cerrar una caja le toca al cajero.</p>
      <button type="button" class="btn g" id="cjNueva">+ Nueva caja</button>
    <?php else: ?>
      <p class="cj-mut">Cada caja se abre con un monto inicial y se cierra contando el efectivo.</p>
    <?php endif; ?>
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
            <div class="cj-kv"><span>Ventas de la caja</span><b><?= money($c['tot']) ?> · <?= (int)$c['n'] ?></b></div>
            <div class="cj-kv"><span>Efectivo esperado</span><b><?= money($esp) ?></b></div>
            <div class="cj-ca">
              <button type="button" class="btn g" data-ventas
                data-hid="<?= (int)$c['historia_id'] ?>" data-titulo="<?= e($c['sucursal'] . ' · ' . $c['nombre']) ?>">Ver ventas</button>
<?php if (!$esAdmin): ?>
              <button type="button" class="btn y" data-cerrar
                data-hid="<?= (int)$c['historia_id'] ?>" data-suc="<?= e($c['sucursal']) ?>" data-caja="<?= e($c['nombre']) ?>"
                data-ap="<?= e($fdt($c['apertura'])) ?>" data-ini="<?= e($c['monto_inicial']) ?>" data-ef="<?= e($c['ef']) ?>">Cerrar caja</button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p class="cj-mut cj-free">Sin caja abierta.</p>
            <?php if (!$esAdmin): ?>
              <div class="cj-ca">
                <button type="button" class="btn y" data-abrir data-id="<?= (int)$c['id'] ?>" data-suc="<?= e($c['sucursal']) ?>" data-nombre="<?= e($c['nombre']) ?>">Abrir caja</button>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (!$cajas): ?><div class="empty">Aún no hay cajas registradas.</div><?php endif; ?>
    </div>
  </section>

  <section class="cj-sec">
    <h2>Historial de cajas</h2>

    <form method="get" id="cjFiltros" class="cj-filters">
      <input type="hidden" name="r" value="caja/index">
      <input type="hidden" name="f" value="1">
      <input type="hidden" name="orden" value="<?= e($f['orden']) ?>">
      <input type="hidden" name="dir" value="<?= e($f['dir']) ?>">

      <div class="cj-grp">
        <span class="cj-lb">Buscar</span>
        <input class="cj-fld cj-q" name="q" type="search" value="<?= e($f['q']) ?>" placeholder="N°, caja o sucursal">
      </div>
      <div class="cj-grp">
        <span class="cj-lb">Fecha</span>
        <input class="cj-fld" type="date" name="desde" id="cjDesde" value="<?= e($f['desde']) ?>" aria-label="Desde">
        <input class="cj-fld" type="date" name="hasta" id="cjHasta" value="<?= e($f['hasta']) ?>" aria-label="Hasta">
      </div>
      <div class="cj-grp" role="radiogroup" aria-label="Sucursal">
        <span class="cj-lb">Sucursal</span>
        <?= $chips('sucursal', [0 => 'Todas'] + $sucursales, $f['sucursal']) ?>
      </div>
      <div class="cj-grp" role="radiogroup" aria-label="Diferencia">
        <span class="cj-lb">Diferencia</span>
        <?= $chips('dif', ['' => 'Todas', 'cuadra' => 'Cuadra', 'falta' => 'Falta', 'sobra' => 'Sobra'], $f['dif']) ?>
      </div>
      <div class="cj-act">
        <button class="cj-go" type="submit">Filtrar</button>
        <a class="cj-link" href="<?= Controller::url('caja/index') ?>">Limpiar filtros</a>
      </div>
    </form>

    <div class="tw">
      <div class="scroll">
        <table>
          <thead><tr>
            <th>N°</th><th>Sucursal</th><th>Caja</th><th>Responsable</th>
            <th><a href="<?= $link(['orden' => 'apertura', 'dir' => $dirDe('apertura'), 'pag' => 1]) ?>">Apertura<?= $flecha('apertura') ?></a></th>
            <th>Cierre</th>
            <th class="cj-num"><a href="<?= $link(['orden' => 'tot', 'dir' => $dirDe('tot'), 'pag' => 1]) ?>">Ventas<?= $flecha('tot') ?></a></th>
            <th class="cj-num"><a href="<?= $link(['orden' => 'dif', 'dir' => $dirDe('dif'), 'pag' => 1]) ?>">Diferencia<?= $flecha('dif') ?></a></th>
            <th>Estado</th>
          </tr></thead>
          <tbody>
          <?php foreach ($filas as $t): ?>
            <tr class="cj-tr" tabindex="0" data-hid="<?= (int)$t['id'] ?>" data-titulo="<?= e($t['sucursal'] . ' · ' . $t['caja']) ?>">
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
        <?php if (!$filas): ?><div class="empty">No hay cajas con estos filtros. Prueba otro periodo o limpia los filtros.</div><?php endif; ?>
      </div>

      <div class="cj-foot">
        <span><?= (int)$rep['n'] ?> <?= (int)$rep['n'] === 1 ? 'caja' : 'cajas' ?></span>
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
  </section>
</div>

<div id="cjOv"></div>
<div id="cjDrawer" role="dialog" aria-modal="true" aria-labelledby="cjTitle" aria-hidden="true"
     data-url="<?= Controller::url('caja/ventas') ?>">
  <div class="cj-dh">
    <div><h2 id="cjTitle"></h2><span class="cj-mut">Ventas de esta caja</span></div>
    <button type="button" class="cj-ib" id="cjClose" aria-label="Cerrar ventas">✕</button>
  </div>
  <div class="cj-dsum">
    <div class="s1"><small>Total vendido</small><b id="cjSumT">S/ 0.00</b></div>
    <div class="s2"><small>Ventas</small><b id="cjSumN">0</b></div>
  </div>
  <div class="cj-dfil">
    <div class="cj-grp" role="radiogroup" aria-label="Tipo">
      <span class="cj-lb">Tipo</span>
      <?= $chips('dt', ['' => 'Todos', 'BOLETA' => 'Boleta', 'FACTURA' => 'Factura'], '') ?>
    </div>
    <div class="cj-grp" role="radiogroup" aria-label="Pago">
      <span class="cj-lb">Pago</span>
      <?= $chips('dp', ['' => 'Todos', 'EFECTIVO' => 'Efectivo', 'TARJETA' => 'Tarjeta', 'YAPE' => 'Yape', 'PLIN' => 'Plin'], '') ?>
    </div>
  </div>
  <div class="cj-db" id="cjBody"></div>
  <div class="cj-dfoot"><span>Total filtrado</span><span id="cjSumF">S/ 0.00</span></div>
</div>

<?php if (!$esAdmin): ?>
<dialog class="cj-d" id="cjDOpen">
  <form method="post" action="<?= Controller::url('caja/abrir') ?>">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="caja_id" id="cjOpCaja">
    <h3>Abrir caja</h3>
    <p class="cj-mut" id="cjOpTitle"></p>
    <label>Monto inicial (S/)
      <input class="cj-fld" type="number" name="monto_inicial" step="0.01" min="0" placeholder="Ej: 100.00" required>
    </label>
    <div class="cj-da">
      <button type="button" class="btn g" data-x>Cancelar</button>
      <button type="submit" class="btn y">Abrir caja</button>
    </div>
  </form>
</dialog>
<?php endif; ?>

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