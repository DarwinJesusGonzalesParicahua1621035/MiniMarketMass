<?php
// views/asistencia/index.php  ·  $filas viene de AsistenciaRepository::todos(), $c de AsistenciaController::config()
$emps = ($c['campos']['empleado_id']['opciones'])();   // id => nombre

// Un registro por fila. La salida vacía queda como '' (en la base es NULL).
$reg = [];
foreach ($filas as $f) {
    $salida = (!empty($f['salida']) && $f['salida'] !== '—') ? (string)$f['salida'] : '';
    $reg[] = [
        'id'    => (int)$f['id'],
        'empid' => (int)($f['empleado_id'] ?? 0),
        'emp'   => (string)$f['empleado'],
        'in'    => str_replace(' ', 'T', substr((string)$f['entrada'], 0, 16)),
        'out'   => $salida !== '' ? str_replace(' ', 'T', substr($salida, 0, 16)) : '',
        'est'   => (string)$f['estado'],
    ];
}
$asi = [
    'hoy'   => date('Y-m-d'),
    'ahora' => date('Y-m-d\TH:i:s'),
    'reg'   => $reg,
];
$json = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/asistencia.css">

<div id="asRoot">

  <div class="as-head">
    <p id="asHoy"></p>
    <div class="as-fecha-box">
      <div class="as-seg" id="asSegFecha">
        <button type="button" data-f="hoy" class="as-on">Hoy</button>
        <button type="button" data-f="ayer">Ayer</button>
        <button type="button" data-f="7d">7 días</button>
        <button type="button" data-f="todas">Todas</button>
      </div>
      <input type="date" class="as-fdate" id="asFecha" title="Elegir otra fecha">
    </div>
  </div>

  <div class="as-stats" id="asStats"></div>

  <section class="as-panel">
    <div class="as-tools">
      <h3>Registros <span class="as-badge" id="asCuenta"><?= count($reg) ?></span></h3>
      <div class="as-chips" id="asChips"></div>
      <span class="as-grow"></span>
      <div class="as-buscar"><span>🔍</span><input id="asQ" type="search" placeholder="Buscar por empleado" autocomplete="off"></div>
      <select class="as-sel" id="asOrden" aria-label="Ordenar">
        <option value="rec">Más recientes</option>
        <option value="nom">Nombre A–Z</option>
        <option value="hor">Más horas</option>
      </select>
      <div class="as-vista" id="asVista">
        <button type="button" data-v="card" class="as-on" title="Tarjetas" aria-label="Tarjetas">▦</button>
        <button type="button" data-v="list" title="Lista" aria-label="Lista">☰</button>
      </div>
    </div>
    <div class="as-body" id="asBody"></div>
  </section>

  <!-- Panel lateral: detalle y edición -->
  <div class="as-scrim" id="asScrim"></div>
  <aside class="as-drawer" id="asDrawer" role="dialog" aria-modal="true" aria-labelledby="asTit">
    <div class="as-dh"><h2 id="asTit">Detalle de asistencia</h2><button type="button" class="as-x" id="asCerrar" aria-label="Cerrar">✕</button></div>

    <div class="as-pane" id="asVer">
      <div class="as-db">
        <div class="as-big"><span class="as-av" id="asVAv"></span><div><b id="asVEmp"></b><small id="asVSub"></small></div></div>
        <div class="as-dl">
          <div><span>Estado</span><b id="asVEst"></b></div>
          <div><span>Entrada</span><b id="asVIn"></b></div>
          <div><span>Salida</span><b id="asVOut"></b></div>
          <div><span>Horas trabajadas</span><b id="asVHoras"></b></div>
        </div>
        <form id="asMarcar" method="post" action="<?= Controller::url('asistencia/marcarsalida') ?>" hidden>
          <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
          <input type="hidden" name="id" id="asMarcarId">
          <button class="as-quick">⏱ Marcar salida ahora</button>
        </form>
      </div>
      <div class="as-df">
        <form method="post" action="<?= Controller::url('asistencia/eliminar') ?>" data-confirm="¿Eliminar este registro?" style="flex:1;display:flex">
          <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
          <input type="hidden" name="id" id="asDelId">
          <button class="as-btn as-del" style="width:100%">Eliminar</button>
        </form>
        <button type="button" class="as-btn as-k" id="asBEditar">Editar</button>
      </div>
    </div>

    <form class="as-pane" id="asEdit" method="post" action="<?= Controller::url('asistencia/guardar') ?>" hidden>
      <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
      <input type="hidden" name="id" id="asEId">
      <div class="as-db">
        <label class="as-campo">Empleado
          <select name="empleado_id" id="asEEmp" required>
            <?php foreach ($emps as $k => $n): ?><option value="<?= (int)$k ?>"><?= e($n) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label class="as-campo">Entrada
          <input type="datetime-local" name="entrada" id="asEIn" required>
        </label>
        <label class="as-campo">Salida (opcional)
          <input type="datetime-local" name="salida" id="asEOut">
        </label>
        <div class="as-err" id="asEErr"></div>
        <label class="as-campo">Estado
          <select name="estado" id="asEEst" required>
            <option value="AUTO">Automático (según la hora de entrada)</option>
            <option value="FALTA">Falta (no marcó asistencia)</option>
          </select>
        </label>
        <p class="as-regla">Temprano: hasta las <?= AsistenciaRepository::LIMITE_TARDE ?> · Tarde: después de las <?= AsistenciaRepository::LIMITE_TARDE ?> · Falta: solo si se registra manualmente</p>
      </div>
      <div class="as-df">
        <button type="button" class="as-btn as-g" id="asBCancelar">Cancelar</button>
        <button class="as-btn as-y">Guardar cambios</button>
      </div>
    </form>
  </aside>

</div>

<script>window.ASI = <?= json_encode($asi, $json) ?>;</script>
<script src="<?= Controller::base() ?>/js/asistencia.js"></script>
