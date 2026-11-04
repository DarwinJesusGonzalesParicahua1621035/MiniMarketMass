<?php
// views/asistencia/index.php
$hoy   = date('Y-m-d');
$ayer  = date('Y-m-d', strtotime('-1 day'));
$ahora = date('Y-m-d\TH:i');
$emps  = ($c['campos']['empleado_id']['opciones'])();   // id => nombre

$ini = function (string $n): string {
    $s = '';
    foreach (array_slice(preg_split('/\s+/', trim($n)) ?: [], 0, 2) as $w) {
        $s .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $s;
};
?>
<div id="asiRoot" data-hoy="<?= e($hoy) ?>" data-ayer="<?= e($ayer) ?>">

  <div class="asi-stats">
    <div class="asi-stat ok"><i></i><div><b id="stP">0</b><span>Presentes</span></div></div>
    <div class="asi-stat warn"><i></i><div><b id="stT">0</b><span>Tardanzas</span></div></div>
    <div class="asi-stat bad"><i></i><div><b id="stF">0</b><span>Faltas</span></div></div>
    <div class="asi-stat on"><i></i><div><b id="stE">0</b><span>En turno ahora</span></div></div>
  </div>

  <div class="asi-panel">
    <div class="asi-head">
      <div class="asi-row">
        <h3>Registros <span class="tag b" id="asiCount"><?= count($filas) ?></span></h3>
        <input class="asi-search" id="asiBuscar" type="search" placeholder="Buscar por empleado">
      </div>
      <div class="asi-filtros">
        <div class="asi-row">
          <span class="asi-lbl">Fecha</span>
          <button type="button" class="asi-chip on" data-grupo="fecha" data-v="hoy">Hoy</button>
          <button type="button" class="asi-chip" data-grupo="fecha" data-v="ayer">Ayer</button>
          <button type="button" class="asi-chip" data-grupo="fecha" data-v="todas">Todas</button>
        </div>
        <div class="asi-row">
          <span class="asi-lbl">Estado</span>
          <button type="button" class="asi-chip on" data-grupo="estado" data-v="">Todos <i data-n=""></i></button>
          <button type="button" class="asi-chip" data-grupo="estado" data-v="PRESENTE">Presente <i data-n="PRESENTE"></i></button>
          <button type="button" class="asi-chip" data-grupo="estado" data-v="TARDANZA">Tardanza <i data-n="TARDANZA"></i></button>
          <button type="button" class="asi-chip" data-grupo="estado" data-v="FALTA">Falta <i data-n="FALTA"></i></button>
        </div>
      </div>
    </div>

    <div class="asi-grid">
      <?php foreach ($filas as $f):
          $in    = str_replace(' ', 'T', substr($f['entrada'], 0, 16));
          $out   = $f['salida'] ? str_replace(' ', 'T', substr($f['salida'], 0, 16)) : '';
          $dia   = substr($in, 0, 10);
          $falta = $f['estado'] === 'FALTA';
          $turno = !$falta && $out === '' && $dia === $hoy;
          $etq   = $dia === $hoy ? 'Hoy' : ($dia === $ayer ? 'Ayer' : $dia);
          $horas = '—';
          if (!$falta && ($out !== '' || $turno)) {
              $min   = max(0, (int)round((strtotime($out ?: $ahora) - strtotime($in)) / 60));
              $horas = intdiv($min, 60) . ' h ' . str_pad((string)($min % 60), 2, '0', STR_PAD_LEFT) . ' min';
          }
          $salidaTxt = $falta ? '—' : ($out !== '' ? substr($out, 11, 5) : ($turno ? 'En turno' : 'Sin marcar'));
          $estTxt = ucfirst(strtolower($f['estado']));
      ?>
        <button type="button" class="asi-item <?= e($f['estado']) ?>"
                data-id="<?= (int)$f['id'] ?>"
                data-empid="<?= (int)$f['empleado_id'] ?>"
                data-emp="<?= e($f['empleado']) ?>"
                data-in="<?= e($in) ?>"
                data-out="<?= e($out) ?>"
                data-est="<?= e($f['estado']) ?>"
                data-dia="<?= e($dia) ?>"
                data-etq="<?= e($etq) ?>"
                data-horas="<?= e($horas) ?>"
                data-turno="<?= $turno ? '1' : '0' ?>">
          <span class="asi-top">
            <span class="asi-av"><?= e($ini($f['empleado'])) ?></span>
            <span><span class="asi-nm"><?= e($f['empleado']) ?></span><span class="asi-sub"><?= e($etq) ?></span></span>
            <span class="asi-tag <?= e($f['estado']) ?>"><?= e($estTxt) ?></span>
          </span>
          <span class="asi-times">
            <span class="asi-t"><small>Entrada</small><b><?= $falta ? '—' : e(substr($in, 11, 5)) ?></b></span>
            <span class="asi-t"><small>Salida</small><b class="<?= $turno ? 'live' : '' ?>"><?= e($salidaTxt) ?></b></span>
            <span class="asi-t"><small>Horas</small><b><?= e($horas) ?></b></span>
          </span>
        </button>
      <?php endforeach; ?>
      <div class="empty" id="asiVacio" <?= $filas ? 'hidden' : '' ?>>No hay registros con esos filtros.</div>
    </div>
  </div>
</div>

<!-- Drawer: detalle y edición en el mismo panel -->
<div class="asi-scrim" id="asiScrim"></div>
<aside class="asi-drawer" id="asiDrawer" role="dialog" aria-modal="true" aria-labelledby="asiTitulo">
  <div class="asi-dh">
    <h2 id="asiTitulo">Detalle de asistencia</h2>
    <button type="button" class="asi-x" id="asiCerrar" aria-label="Cerrar">✕</button>
  </div>

  <!-- Vista de detalle -->
  <div class="asi-pane" id="asiVer">
    <div class="asi-db">
      <div class="asi-big">
        <span class="asi-av" id="vAv"></span>
        <div><div class="asi-nm" id="vEmp"></div><div class="asi-sub" id="vSub"></div></div>
      </div>
      <div class="asi-dl">
        <div><span>Estado</span><b id="vEst"></b></div>
        <div><span>Entrada</span><b id="vIn"></b></div>
        <div><span>Salida</span><b id="vOut"></b></div>
        <div><span>Horas trabajadas</span><b id="vHoras"></b></div>
      </div>
      <form id="asiMarcar" method="post" action="<?= Controller::url('asistencia/marcarsalida') ?>" hidden>
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="marcarId">
        <button class="btn asi-btn-b asi-quick">Marcar salida ahora</button>
      </form>
    </div>
    <div class="asi-df">
      <form method="post" action="<?= Controller::url('asistencia/eliminar') ?>" data-confirm="¿Eliminar este registro?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="delId">
        <button class="btn asi-btn-del">Eliminar</button>
      </form>
      <button type="button" class="btn asi-btn-k" id="btnEditar">Editar</button>
    </div>
  </div>

  <!-- Formulario de edición -->
  <form class="asi-pane" id="asiEdit" method="post" action="<?= Controller::url('asistencia/guardar') ?>" hidden>
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="eId">
    <div class="asi-db">
      <label class="asi-campo">Empleado
        <select name="empleado_id" id="eEmp" required>
          <?php foreach ($emps as $k => $n): ?><option value="<?= (int)$k ?>"><?= e($n) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="asi-campo">Entrada
        <input type="datetime-local" name="entrada" id="eIn" required>
      </label>
      <label class="asi-campo">Salida (opcional)
        <input type="datetime-local" name="salida" id="eOut">
      </label>
      <div class="asi-err" id="eErr"></div>
      <label class="asi-campo">Estado
        <select name="estado" id="eEst" required>
          <option value="PRESENTE">PRESENTE</option>
          <option value="TARDANZA">TARDANZA</option>
          <option value="FALTA">FALTA</option>
        </select>
      </label>
    </div>
    <div class="asi-df">
      <button type="button" class="btn g" id="btnCancelar">Cancelar</button>
      <button class="btn y">Guardar cambios</button>
    </div>
  </form>
</aside>

<script src="<?= Controller::base() ?>/js/asistencia.js"></script>