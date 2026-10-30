<?php
/** @var array $clientes @var string $q @var ?array $aviso */
?>
<?php if ($aviso): ?>
  <p class="tag <?= $aviso[0] === 'ok' ? 'ok' : 'bad' ?>" style="margin-bottom:14px"><?= Ayuda::e($aviso[1]) ?></p>
<?php endif; ?>

<div class="tw">
  <div class="tools">
    <h3>Clientes <span class="tag b"><?= count($clientes) ?></span></h3>
    <form method="get" style="display:contents">
      <input type="hidden" name="m" value="clientes">
      <input class="search" name="q" placeholder="Buscar…" value="<?= Ayuda::e($q) ?>">
    </form>
    <button type="button" class="btn y" onclick="abrirCli()">+ Nuevo</button>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr><th>#</th><th>Tipo doc.</th><th>N.º documento</th><th>Nombre</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($clientes as $c): ?>
        <tr>
          <td><?= (int) $c['id'] ?></td>
          <td><?= Ayuda::e($c['tipo_documento']) ?></td>
          <td><?= Ayuda::e($c['numero_documento']) ?></td>
          <td><?= Ayuda::e($c['nombre']) ?></td>
          <td>
            <button type="button" class="act" title="Editar"
              onclick='editarCli(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'>✏️</button>
            <form method="post" action="?m=clientes&a=eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar este cliente?')">
              <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="act" title="Eliminar">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$clientes): ?><div class="empty">Sin resultados</div><?php endif; ?>
  </div>
</div>

<div class="modal" id="modal-cli">
  <form method="post" action="?m=clientes&a=guardar" id="form-cli">
    <h3 id="cli-titulo">Nuevo registro · Clientes</h3>
    <input type="hidden" name="csrf" value="<?= Ayuda::e(Ayuda::token()) ?>">
    <input type="hidden" name="id" id="cli-id">
    <label>Tipo doc.</label>
    <select name="tipo_documento" id="cli-tipo" class="sel" style="width:100%" required onchange="ajustarDoc()">
      <option value="DNI">DNI</option>
      <option value="RUC">RUC</option>
    </select>
    <label>N.º documento</label>
    <input name="numero_documento" id="cli-num" inputmode="numeric" pattern="[0-9]*" maxlength="8" required>
    <label>Nombre</label>
    <input name="nombre" id="cli-nombre" maxlength="120" required>
    <div class="row">
      <button type="button" class="btn g" onclick="cerrarCli()">Cancelar</button>
      <button class="btn">Guardar</button>
    </div>
  </form>
</div>

<script>
const modalCli = document.getElementById('modal-cli');
function cerrarCli() { modalCli.classList.remove('on'); }
function ajustarDoc() {
  const largo = document.getElementById('cli-tipo').value === 'RUC' ? 11 : 8;
  const n = document.getElementById('cli-num');
  n.maxLength = largo;
  n.minLength = largo;
}
function abrirCli() {
  document.getElementById('form-cli').reset();
  document.getElementById('cli-id').value = '';
  document.getElementById('cli-titulo').textContent = 'Nuevo registro · Clientes';
  ajustarDoc();
  modalCli.classList.add('on');
}
function editarCli(c) {
  abrirCli();
  document.getElementById('cli-titulo').textContent = 'Editar registro · Clientes';
  document.getElementById('cli-id').value = c.id;
  document.getElementById('cli-tipo').value = c.tipo_documento;
  ajustarDoc();
  document.getElementById('cli-num').value = c.numero_documento;
  document.getElementById('cli-nombre').value = c.nombre;
}
modalCli.addEventListener('click', e => { if (e.target === modalCli) cerrarCli(); });
</script>