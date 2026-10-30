<?php
/** @var array $asistencias @var array $empleados @var string $q @var ?array $aviso */
$asistencias = $asistencias ?? [];
$empleados   = $empleados ?? [];
$q           = $q ?? '';
$aviso       = $aviso ?? null;
 
$etiquetas = ['PRESENTE' => 'ok', 'TARDANZA' => 'y', 'FALTA' => 'bad']; // mismos colores del HTML
$paraInput = fn(?string $f) => $f ? str_replace(' ', 'T', substr($f, 0, 16)) : '';
?>
<?php if (!empty($aviso)): ?>
  <p class="tag <?= $aviso[0] === 'ok' ? 'ok' : 'bad' ?>" style="margin-bottom:14px"><?= Ayuda::e($aviso[1]) ?></p>
<?php endif; ?>
 
<div class="tw">
  <div class="tools">
    <h3>Asistencia <span class="tag b"><?= count($asistencias) ?></span></h3>
    <form method="get" style="display:contents">
      <input type="hidden" name="m" value="asistencia">
      <input class="search" name="q" placeholder="Buscar…" value="<?= Ayuda::e($q) ?>">
    </form>
    <button type="button" class="btn y" onclick="abrirAsi()">+ Nuevo</button>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr><th>#</th><th>Empleado</th><th>Entrada</th><th>Salida</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($asistencias as $a):
          $datos = [
              'id'          => $a['id'],
              'empleado_id' => $a['empleado_id'],
              'entrada'     => $paraInput($a['entrada']),
              'salida'      => $paraInput($a['salida']),
              'estado'      => $a['estado'],
          ];
      ?>
        <tr>
          <td><?= (int) $a['id'] ?></td>
          <td><?= Ayuda::e($a['empleado']) ?></td>
          <td><?= $a['entrada'] ? Ayuda::e(substr($a['entrada'], 0, 16)) : '—' ?></td>
          <td><?= $a['salida'] ? Ayuda::e(substr($a['salida'], 0, 16)) : '—' ?></td>
          <td><span class="tag <?= $etiquetas[$a['estado']] ?? '' ?>"><?= Ayuda::e($a['estado']) ?></span></td>
          <td>
            <button type="button" class="act" title="Editar"
              onclick='editarAsi(<?= json_encode($datos, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>✏️</button>
            <form method="post" action="?m=asistencia&a=eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar este registro de asistencia?')">
              <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button class="act" title="Eliminar">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$asistencias): ?><div class="empty">Sin resultados</div><?php endif; ?>
  </div>
</div>
 
<div class="modal" id="modal-asi">
  <form method="post" action="?m=asistencia&a=guardar" id="form-asi">
    <h3 id="asi-titulo">Nuevo registro · Asistencia</h3>
    <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
    <input type="hidden" name="id" id="asi-id">
    <label>Empleado</label>
    <select name="empleado_id" id="asi-empleado" class="sel" style="width:100%" required>
      <option value="">Seleccione…</option>
      <?php foreach ($empleados as $e): ?>
        <option value="<?= (int) $e['id'] ?>"><?= Ayuda::e($e['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Entrada</label>
    <input type="datetime-local" name="entrada" id="asi-entrada" required>
    <label>Salida (opcional)</label>
    <input type="datetime-local" name="salida" id="asi-salida">
    <label>Estado</label>
    <select name="estado" id="asi-estado" class="sel" style="width:100%" required>
      <option value="PRESENTE">PRESENTE</option>
      <option value="TARDANZA">TARDANZA</option>
      <option value="FALTA">FALTA</option>
    </select>
    <div class="row">
      <button type="button" class="btn g" onclick="cerrarAsi()">Cancelar</button>
      <button class="btn">Guardar</button>
    </div>
  </form>
</div>
 
<script>
const modalAsi = document.getElementById('modal-asi');
function cerrarAsi() { modalAsi.classList.remove('on'); }
function abrirAsi() {
  document.getElementById('form-asi').reset();
  document.getElementById('asi-id').value = '';
  document.getElementById('asi-titulo').textContent = 'Nuevo registro · Asistencia';
  modalAsi.classList.add('on');
}
function editarAsi(a) {
  abrirAsi();
  document.getElementById('asi-titulo').textContent = 'Editar registro · Asistencia';
  document.getElementById('asi-id').value = a.id;
  document.getElementById('asi-empleado').value = a.empleado_id;
  document.getElementById('asi-entrada').value = a.entrada;
  document.getElementById('asi-salida').value = a.salida;
  document.getElementById('asi-estado').value = a.estado;
}
modalAsi.addEventListener('click', e => { if (e.target === modalAsi) cerrarAsi(); });
</script>
 