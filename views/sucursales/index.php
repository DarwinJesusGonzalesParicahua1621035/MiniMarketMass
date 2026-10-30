<?php
/** 
 * @var array $sucursales 
 * @var array $distritos 
 * @var string $q 
 * @var ?array $aviso 
 * @var Ayuda $ayudaAlias 
 */
?>
<?php if ($aviso): ?>
  <p class="tag <?= $aviso[0] === 'ok' ? 'ok' : 'bad' ?>" style="margin-bottom:14px"><?= \Ayuda::e($aviso[1]) ?></p>
<?php endif; ?>

<div class="tw">
  <div class="tools">
    <h3>Sucursales <span class="tag b"><?= count($sucursales) ?></span></h3>
    <form method="get" style="display:contents">
      <input type="hidden" name="m" value="sucursales">
      <input class="search" name="q" placeholder="Buscar…" value="<?= \Ayuda::e($q) ?>">
    </form>
    <button type="button" class="btn y" onclick="abrirSuc()">+ Nuevo</button>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr><th>#</th><th>Nombre</th><th>Dirección</th><th>Distrito</th><th>Serie boleta</th><th>Serie factura</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($sucursales as $s): ?>
        <tr>
          <td><?= (int) $s['id'] ?></td>
          <td><?= \Ayuda::e($s['nombre']) ?></td>
          <td><?= \Ayuda::e($s['direccion']) ?></td>
          <td><?= \Ayuda::e($s['distrito']) ?></td>
          <td><?= \Ayuda::e($s['serie_boleta']) ?></td>
          <td><?= \Ayuda::e($s['serie_factura']) ?></td>
          <td>
            <button type="button" class="act" title="Editar"
              onclick='editarSuc(<?= \Ayuda::e(json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>)'>✏️</button>
            <form method="post" action="?m=sucursales&a=eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar esta sucursal?')">
              <input type="hidden" name="csrf" value="<?= \Ayuda::e(\Ayuda::token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="act" title="Eliminar">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$sucursales): ?><div class="empty">Sin resultados</div><?php endif; ?>
  </div>
</div>

<div class="modal" id="modal-suc">
  <form method="post" action="?m=sucursales&a=guardar" id="form-suc">
    <h3 id="suc-titulo">Nuevo registro · Sucursales</h3>
    <input type="hidden" name="csrf" value="<?= \Ayuda::e(\Ayuda::token()) ?>">
    <input type="hidden" name="id" id="suc-id">
    <label>Nombre</label>
    <input name="nombre" id="suc-nombre" maxlength="80" required>
    <label>Dirección</label>
    <input name="direccion" id="suc-direccion" maxlength="150" required>
    <label>Distrito</label>
    <select name="distrito_id" id="suc-distrito" class="sel" style="width:100%" required>
      <option value="">Seleccione…</option>
      <?php foreach ($distritos as $d): ?>
        <option value="<?= (int) $d['id'] ?>"><?= \Ayuda::e($d['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <label>Serie boleta</label>
    <input name="serie_boleta" id="suc-sb" maxlength="4" minlength="4" required>
    <label>Serie factura</label>
    <input name="serie_factura" id="suc-sf" maxlength="4" minlength="4" required>
    <div class="row">
      <button type="button" class="btn g" onclick="cerrarSuc()">Cancelar</button>
      <button class="btn">Guardar</button>
    </div>
  </form>
</div>

<script>
const modalSuc = document.getElementById('modal-suc');
function cerrarSuc() { modalSuc.classList.remove('on'); }
function abrirSuc() {
  document.getElementById('form-suc').reset();
  document.getElementById('suc-id').value = '';
  document.getElementById('suc-titulo').textContent = 'Nuevo registro · Sucursales';
  modalSuc.classList.add('on');
}
function editarSuc(s) {
  abrirSuc();
  document.getElementById('suc-titulo').textContent = 'Editar registro · Sucursales';
  document.getElementById('suc-id').value = s.id;
  document.getElementById('suc-nombre').value = s.nombre;
  document.getElementById('suc-direccion').value = s.direccion;
  document.getElementById('suc-distrito').value = s.distrito_id;
  document.getElementById('suc-sb').value = s.serie_boleta;
  document.getElementById('suc-sf').value = s.serie_factura;
}
modalSuc.addEventListener('click', e => { if (e.target === modalSuc) cerrarSuc(); });
</script>