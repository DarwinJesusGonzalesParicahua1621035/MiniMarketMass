<?php
/** @var array $capacitaciones @var array $empleados @var string $q @var ?array $aviso */
?>
<?php if ($aviso): ?>
  <p class="tag <?= $aviso[0] === 'ok' ? 'ok' : 'bad' ?>" style="margin-bottom:14px"><?= Ayuda::e($aviso[1]) ?></p>
<?php endif; ?>

<div class="tw">
  <div class="tools">
    <h3>Capacitaciones <span class="tag b"><?= count($capacitaciones) ?></span></h3>
    <form method="get" style="display:contents">
      <input type="hidden" name="m" value="capacitaciones">
      <input class="search" name="q" placeholder="Buscar…" value="<?= Ayuda::e($q) ?>">
    </form>
    <button type="button" class="btn y" onclick="abrirCap()">+ Nuevo</button>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr><th>#</th><th>Empleado</th><th>Tema</th><th>Fecha</th><th>Horas</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($capacitaciones as $c): ?>
        <tr>
          <td><?= (int) $c['id'] ?></td>
          <td><?= Ayuda::e($c['empleado']) ?></td>
          <td><?= Ayuda::e($c['tema']) ?></td>
          <td><?= Ayuda::e($c['fecha']) ?></td>
          <td><?= number_format((float) $c['horas'], 1) ?></td>
          <td>
            <button type="button" class="act" title="Editar"
              onclick='editarCap(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>✏️</button>
            <form method="post" action="?m=capacitaciones&a=eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar esta capacitación?')">
              <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="act" title="Eliminar">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$capacitaciones): ?><div class="empty">Sin resultados</div><?php endif; ?>
  </div>
</div>

<div class="modal" id="modal-cap">
  <form method="post" action="?m=capacitaciones&a=guardar" id="form-cap">
    <h3 id="cap-titulo">Nuevo registro · Capacitaciones</h3>
    <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
    <input type="hidden" name="id" id="cap-id">
    <label>Empleado</label>
    <select name="empleado_id" id="cap-empleado" class="sel" style="width:100%" required>
      <option value="">Seleccione…</option>
      <?php foreach ($empleados as $e): ?>
        <option value="<?= (int) $e['id'] ?>"><?= Ayuda::e($e['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Tema</label>
    <input name="tema" id="cap-tema" maxlength="100" required>
    <label>Fecha</label>
    <input type="date" name="fecha" id="cap-fecha" required>
    <label>Horas</label>
    <input type="number" name="horas" id="cap-horas" min="0.1" max="9999.9" step="0.1" required>
    <div class="row">
      <button type="button" class="btn g" onclick="cerrarCap()">Cancelar</button>
      <button class="btn">Guardar</button>
    </div>
  </form>
</div>

<script>
const modalCap = document.getElementById('modal-cap');
function cerrarCap() { modalCap.classList.remove('on'); }
function abrirCap() {
  document.getElementById('form-cap').reset();
  document.getElementById('cap-id').value = '';
  document.getElementById('cap-titulo').textContent = 'Nuevo registro · Capacitaciones';
  modalCap.classList.add('on');
}
function editarCap(c) {
  abrirCap();
  document.getElementById('cap-titulo').textContent = 'Editar registro · Capacitaciones';
  document.getElementById('cap-id').value = c.id;
  document.getElementById('cap-empleado').value = c.empleado_id;
  document.getElementById('cap-tema').value = c.tema;
  document.getElementById('cap-fecha').value = c.fecha;
  document.getElementById('cap-horas').value = c.horas;
}
modalCap.addEventListener('click', e => { if (e.target === modalCap) cerrarCap(); });
</script>