<?php
/**
 * Interfaz de Incidentes (Configuración · admin).
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
$ahora = time();
$hoy   = date('Y-m-d');
$sucs  = ($c['campos']['sucursal_id']['opciones'])();   // id => nombre

$hace = function (string $f) use ($ahora): string {
    $m = max(0, (int)round(($ahora - strtotime($f)) / 60));
    if ($m < 60) return $m . ' min';
    if ($m < 1440) {
        $h = intdiv($m, 60); $r = $m % 60;
        return $h . ' h' . ($r ? ' ' . str_pad((string)$r, 2, '0', STR_PAD_LEFT) . ' min' : '');
    }
    $d = intdiv($m, 1440);
    return $d . ($d === 1 ? ' día' : ' días');
};
$icono = fn(string $t): string => ['equipos' => '🖨️', 'seguridad' => '🛡️', 'stock' => '📦'][mb_strtolower($t)] ?? '📌';

$nAb    = count(array_filter($filas, fn($f) => $f['estado'] === 'ABIERTO'));
$nRes   = count($filas) - $nAb;
$nTarde = count(array_filter($filas, fn($f) => $f['estado'] === 'ABIERTO' && ($ahora - strtotime($f['fecha'])) > 86400));
$nHoy   = count(array_filter($filas, fn($f) => substr($f['fecha'], 0, 10) === $hoy));

$tipos = []; $porSuc = [];
foreach ($filas as $f) {
    $tipos[$f['tipo']]      = ($tipos[$f['tipo']] ?? 0) + 1;
    $porSuc[$f['sucursal']] = ($porSuc[$f['sucursal']] ?? 0) + 1;
}
ksort($tipos);
?>
<div id="incRoot">

  <section class="cfg-hero">
    <div>
      <p class="cfg-eyebrow">Configuración</p>
      <h2>Incidentes</h2>
      <p>Seguimiento operativo de reportes por sucursal, tipo y estado.</p>
    </div>
    <span><?= (int)$nAb ?> abierto<?= $nAb === 1 ? '' : 's' ?></span>
  </section>

  <div class="inc-stats">
    <div class="inc-stat warn"><i></i><div><b><?= $nAb ?></b><span>Abiertos</span></div></div>
    <div class="inc-stat bad"><i></i><div><b><?= $nTarde ?></b><span>Abiertos hace más de 24 h</span></div></div>
    <div class="inc-stat on"><i></i><div><b><?= $nHoy ?></b><span>Reportados hoy</span></div></div>
    <div class="inc-stat ok"><i></i><div><b><?= $nRes ?></b><span>Resueltos</span></div></div>
  </div>

  <div class="inc-panel cfg-panel">
    <div class="inc-head">
      <div class="inc-row">
        <h3>Listado <span class="tag b" id="incCount"><?= count($filas) ?></span></h3>
        <input class="inc-search" id="incBuscar" type="search" placeholder="Buscar en la descripción">
        <button type="button" class="btn y" id="incNuevo">+ Reportar incidente</button>
      </div>
      <div class="inc-filtros">
        <div class="inc-row">
          <span class="inc-lbl">Estado</span>
          <button type="button" class="inc-chip on" data-grupo="estado" data-v="">Todos <i><?= count($filas) ?></i></button>
          <button type="button" class="inc-chip" data-grupo="estado" data-v="ABIERTO">Abiertos <i><?= $nAb ?></i></button>
          <button type="button" class="inc-chip" data-grupo="estado" data-v="RESUELTO">Resueltos <i><?= $nRes ?></i></button>
        </div>
        <div class="inc-row">
          <span class="inc-lbl">Tipo</span>
          <button type="button" class="inc-chip on" data-grupo="tipo" data-v="">Todos <i><?= count($filas) ?></i></button>
          <?php foreach ($tipos as $t => $n): ?>
            <button type="button" class="inc-chip" data-grupo="tipo" data-v="<?= e($t) ?>"><?= e($t) ?> <i><?= $n ?></i></button>
          <?php endforeach; ?>
        </div>
        <div class="inc-row">
          <span class="inc-lbl">Sucursal</span>
          <button type="button" class="inc-chip on" data-grupo="suc" data-v="">Todas <i><?= count($filas) ?></i></button>
          <?php foreach ($sucs as $nombre): ?>
            <button type="button" class="inc-chip" data-grupo="suc" data-v="<?= e($nombre) ?>"><?= e($nombre) ?> <i><?= $porSuc[$nombre] ?? 0 ?></i></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="inc-grid">
      <?php foreach ($filas as $f):
          $ab    = $f['estado'] === 'ABIERTO';
          $tarde = $ab && ($ahora - strtotime($f['fecha'])) > 86400;
          $fecha = str_replace(' ', 'T', substr($f['fecha'], 0, 16));
          $tiempo = $hace($f['fecha']);
      ?>
        <button type="button" class="inc-item <?= e($f['estado']) ?>"
                data-id="<?= (int)$f['id'] ?>"
                data-sucid="<?= (int)$f['sucursal_id'] ?>"
                data-suc="<?= e($f['sucursal']) ?>"
                data-tipo="<?= e($f['tipo']) ?>"
                data-desc="<?= e($f['descripcion']) ?>"
                data-fecha="<?= e($fecha) ?>"
                data-est="<?= e($f['estado']) ?>"
                data-hace="<?= e($tiempo) ?>"
                data-tarde="<?= $tarde ? '1' : '0' ?>"
                data-icono="<?= e($icono($f['tipo'])) ?>">
          <span class="inc-top">
            <span class="inc-ic"><?= $icono($f['tipo']) ?></span>
            <span><span class="inc-tp"><?= e($f['tipo']) ?></span><span class="inc-sub"><?= e($f['sucursal']) ?></span></span>
            <span class="inc-tag <?= e($f['estado']) ?>"><?= e(ucfirst(strtolower($f['estado']))) ?></span>
          </span>
          <span class="inc-desc"><?= e($f['descripcion']) ?></span>
          <span class="inc-foot">
            <span><?= e(str_replace('T', ' ', $fecha)) ?></span>
            <span class="inc-age <?= $tarde ? 'late' : '' ?>"><?= $ab ? 'hace ' . e($tiempo) : 'Cerrado' ?></span>
          </span>
        </button>
      <?php endforeach; ?>
      <div class="empty" id="incVacio" <?= $filas ? 'hidden' : '' ?>>No hay incidentes con esos filtros.</div>
    </div>
  </div>
</div>

<!-- Drawer: detalle y edición en el mismo panel -->
<div class="inc-scrim" id="incScrim"></div>
<aside class="inc-drawer" id="incDrawer" role="dialog" aria-modal="true" aria-labelledby="incTitulo">
  <div class="inc-dh">
    <h2 id="incTitulo">Detalle del incidente</h2>
    <button type="button" class="inc-x" id="incCerrar" aria-label="Cerrar">✕</button>
  </div>

  <!-- Vista de detalle -->
  <div class="inc-pane" id="incVer">
    <div class="inc-db">
      <div class="inc-big">
        <span class="inc-ic" id="vIcono"></span>
        <div><div class="inc-tp" id="vTipo" style="font-size:19px"></div><span class="inc-sub" id="vSub"></span></div>
      </div>
      <div class="inc-quote" id="vDesc"></div>
      <div class="inc-dl">
        <div><span>Estado</span><b id="vEst"></b></div>
        <div><span>Sucursal</span><b id="vSuc"></b></div>
        <div><span>Fecha</span><b id="vFecha"></b></div>
        <div id="rowHace"><span>Tiempo abierto</span><b id="vHace"></b></div>
      </div>
      <form method="post" action="<?= Controller::url('incidentes/cambiarestado') ?>">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="camId">
        <button class="btn inc-quick" id="camBtn"></button>
      </form>
    </div>
    <div class="inc-df">
      <form method="post" action="<?= Controller::url('incidentes/eliminar') ?>" data-confirm="¿Eliminar este incidente?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="delId">
        <button class="btn inc-btn-del">Eliminar</button>
      </form>
      <button type="button" class="btn inc-btn-k" id="btnEditar">Editar</button>
    </div>
  </div>

  <!-- Formulario de edición / nuevo -->
  <form class="inc-pane" id="incEdit" method="post" action="<?= Controller::url('incidentes/guardar') ?>" hidden>
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="eId" value="0">
    <div class="inc-db">
      <label class="inc-campo">Sucursal
        <select name="sucursal_id" id="eSuc" required>
          <?php foreach ($sucs as $k => $n): ?><option value="<?= (int)$k ?>"><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="inc-campo">Tipo
        <input type="text" name="tipo" id="eTipo" maxlength="20" list="incTipos" placeholder="Ej: Seguridad, Equipos, Stock" required>
      </label>
      <datalist id="incTipos"><option>Seguridad</option><option>Equipos</option><option>Stock</option></datalist>
      <label class="inc-campo">Descripción
        <textarea name="descripcion" id="eDesc" maxlength="250" required></textarea>
        <small id="cnt">0/250</small>
      </label>
      <label class="inc-campo">Fecha (vacío = ahora)
        <input type="datetime-local" name="fecha" id="eFecha">
      </label>
      <label class="inc-campo">Estado
        <select name="estado" id="eEst" required>
          <option value="ABIERTO">ABIERTO</option>
          <option value="RESUELTO">RESUELTO</option>
        </select>
      </label>
    </div>
    <div class="inc-df">
      <button type="button" class="btn g" id="btnCancelar">Cancelar</button>
      <button class="btn y">Guardar cambios</button>
    </div>
  </form>
</aside>

<script src="<?= Controller::base() ?>/js/incidentes.js"></script>