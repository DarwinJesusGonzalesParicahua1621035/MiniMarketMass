<?php
// views/incidentes/index.php
$ahora = time();
$sucs  = ($c['campos']['sucursal_id']['opciones'])();   // id => nombre

$hace = function (string $f) use ($ahora): string {
    $m = max(0, (int)round(($ahora - strtotime($f)) / 60));
    if ($m < 1)    return 'hace un momento';
    if ($m < 60)   return 'hace ' . $m . ' min';
    if ($m < 1440) return 'hace ' . intdiv($m, 60) . ' h';
    $d = intdiv($m, 1440);
    return 'hace ' . $d . ($d === 1 ? ' día' : ' días');
};
$iconos = ['equipos' => '🖨️', 'seguridad' => '🛡️', 'stock' => '📦', 'otro' => '📌'];
$icono  = fn(string $t): string => $iconos[mb_strtolower($t)] ?? '📌';

// Tipos que se ofrecen al reportar: los base + cualquier otro que ya exista en los datos.
$tipos = IncidentesController::TIPOS;
foreach ($filas as $f) {
    if (!in_array($f['tipo'], $tipos, true)) $tipos[] = $f['tipo'];
}

$nAb = $nUrg = $nRes = 0;
foreach ($filas as &$f) {
    $f['_ab']  = $f['estado'] === 'ABIERTO';
    $f['_urg'] = $f['_ab'] && ($ahora - strtotime($f['fecha'])) > 86400;
    if ($f['_ab']) $nAb++; else $nRes++;
    if ($f['_urg']) $nUrg++;
}
unset($f);
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/incidentes.css">

<div id="imRoot" data-ahora="<?= e(date('Y-m-d\TH:i')) ?>">

  <!-- 1. Qué hay pendiente: pestañas con contador -->
  <div class="im-tabs" role="tablist" aria-label="Estado de los incidentes">
    <button type="button" class="im-tab" data-vista="abiertos"  role="tab"><i class="d warn"></i><b data-n="abiertos"><?= $nAb ?></b><span>Por atender</span></button>
    <button type="button" class="im-tab" data-vista="urgentes"  role="tab"><i class="d bad"></i><b data-n="urgentes"><?= $nUrg ?></b><span>Urgentes <small>+24 h sin resolver</small></span></button>
    <button type="button" class="im-tab" data-vista="resueltos" role="tab"><i class="d ok"></i><b data-n="resueltos"><?= $nRes ?></b><span>Resueltos</span></button>
    <button type="button" class="im-tab" data-vista="todos"     role="tab"><i class="d"></i><b data-n="todos"><?= count($filas) ?></b><span>Todos</span></button>
  </div>

  <!-- 2. Buscar / filtrar / reportar -->
  <div class="im-bar">
    <input class="im-search" id="imBuscar" type="search" placeholder="🔍 Buscar incidente…" autocomplete="off">
    <select id="imSuc" aria-label="Filtrar por sucursal">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucs as $k => $n): ?><option value="<?= (int)$k ?>"><?= e($n) ?></option><?php endforeach; ?>
    </select>
    <select id="imTipo" aria-label="Filtrar por tipo">
      <option value="">Todos los tipos</option>
      <?php foreach ($tipos as $t): ?><option value="<?= e($t) ?>"><?= e($icono($t) . ' ' . $t) ?></option><?php endforeach; ?>
    </select>
    <button type="button" class="btn y im-nuevo" id="imNuevo">＋ Reportar incidente</button>
  </div>

  <!-- 3. Lista -->
  <div class="im-list" id="imList">
    <?php foreach ($filas as $f):
        $cls = $f['_urg'] ? 'urg' : ($f['_ab'] ? 'ab' : 'res');
        $fechaIso = str_replace(' ', 'T', substr($f['fecha'], 0, 16));
    ?>
      <article class="im-item <?= $cls ?>" tabindex="-1"
               data-id="<?= (int)$f['id'] ?>"
               data-sucid="<?= (int)$f['sucursal_id'] ?>"
               data-tipo="<?= e($f['tipo']) ?>"
               data-desc="<?= e($f['descripcion']) ?>"
               data-fecha="<?= e($fechaIso) ?>"
               data-est="<?= e($f['estado']) ?>"
               data-urg="<?= $f['_urg'] ? '1' : '0' ?>"
               data-texto="<?= e(mb_strtolower($f['tipo'] . ' ' . $f['sucursal'] . ' ' . $f['descripcion'])) ?>">
        <div class="im-ic"><?= $icono($f['tipo']) ?></div>
        <div class="im-main">
          <div class="im-meta">
            <b><?= e($f['tipo']) ?></b>
            <span>· <?= e($f['sucursal']) ?></span>
            <?php if ($f['_urg']): ?><span class="im-badge bad">Urgente</span>
            <?php elseif ($f['_ab']): ?><span class="im-badge warn">Por atender</span>
            <?php else: ?><span class="im-badge ok">Resuelto</span><?php endif; ?>
          </div>
          <p class="im-desc"><?= e($f['descripcion']) ?></p>
          <div class="im-when">
            <span title="<?= e(date('d/m/Y H:i', strtotime($f['fecha']))) ?>">🕒 <?= e(date('d/m/Y H:i', strtotime($f['fecha']))) ?></span>
            <span class="<?= $f['_urg'] ? 'late' : '' ?>"><?= e($hace($f['fecha'])) ?></span>
          </div>
        </div>
        <div class="im-acts">
          <form method="post" action="<?= Controller::url('incidentes/cambiarestado') ?>">
            <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
            <input type="hidden" name="estado" value="<?= $f['_ab'] ? 'RESUELTO' : 'ABIERTO' ?>">
            <?php if ($f['_ab']): ?>
              <button class="btn im-ok">✓ Marcar resuelto</button>
            <?php else: ?>
              <button class="btn g">↺ Reabrir</button>
            <?php endif; ?>
          </form>
          <button type="button" class="btn g im-edit">✎ Editar</button>
        </div>
      </article>
    <?php endforeach; ?>

    <div class="im-vacio" id="imVacio" hidden>
      <div class="im-vacio-ic" id="imVacioIc">🎉</div>
      <p id="imVacioTx"></p>
      <button type="button" class="btn g" id="imLimpiar" hidden>Quitar filtros</button>
    </div>
  </div>
</div>

<!-- Ventana para reportar / editar -->
<dialog class="im-dlg" id="imDlg" aria-labelledby="imTitulo">
  <form method="post" id="imForm" action="<?= Controller::url('incidentes/guardar') ?>">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="fId" value="0">
    <input type="hidden" name="estado" id="fEstado" value="ABIERTO">

    <div class="im-dh">
      <h2 id="imTitulo">Reportar incidente</h2>
      <button type="button" class="im-x" id="imCerrar" aria-label="Cerrar">✕</button>
    </div>

    <div class="im-db">
      <fieldset class="im-campo">
        <legend>1. ¿Qué tipo de problema es?</legend>
        <div class="im-tipos">
          <?php foreach ($tipos as $i => $t): ?>
            <label><input type="radio" name="tipo" value="<?= e($t) ?>" required><span><?= e($icono($t)) ?> <?= e($t) ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <label class="im-campo">2. ¿En qué sucursal?
        <select name="sucursal_id" id="fSuc" required>
          <option value="" disabled>Elige una sucursal…</option>
          <?php foreach ($sucs as $k => $n): ?><option value="<?= (int)$k ?>"><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>

      <label class="im-campo">3. ¿Qué pasó?
        <textarea name="descripcion" id="fDesc" maxlength="250" rows="4" required placeholder="Ej: La impresora de tickets de la caja 2 se atasca al imprimir"></textarea>
        <small><span id="fCnt">0</span>/250</small>
      </label>

      <label class="im-campo">Fecha y hora
        <input type="datetime-local" name="fecha" id="fFecha">
      </label>

      <div class="im-campo" id="fEstadoBox" hidden>
        <span class="im-lbl">Estado</span>
        <div class="im-seg">
          <label><input type="radio" name="estado_vis" value="ABIERTO"><span>⏳ Por atender</span></label>
          <label><input type="radio" name="estado_vis" value="RESUELTO"><span>✓ Resuelto</span></label>
        </div>
      </div>
    </div>

    <div class="im-df">
      <button type="submit" class="btn im-del" id="fEliminar" form="imDel" hidden>🗑 Eliminar</button>
      <span class="im-sp"></span>
      <button type="button" class="btn g" id="fCancelar">Cancelar</button>
      <button type="submit" class="btn y" id="fGuardar">Reportar incidente</button>
    </div>
  </form>

  <form method="post" id="imDel" action="<?= Controller::url('incidentes/eliminar') ?>" data-confirm="¿Eliminar este incidente definitivamente?">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="delId" value="0">
  </form>
</dialog>

<script src="<?= Controller::base() ?>/js/incidentes.js"></script>
