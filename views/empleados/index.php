<?php
/* Clase de la etiqueta según el rol (igual que en la tabla anterior) */
function empTagRol(string $rol): string
{
    return ['ADMINISTRADOR' => 'b', 'CAJERO' => 'y'][$rol] ?? '';
}

/* Contadores para los chips */
$totalRol = ['ADMINISTRADOR' => 0, 'CAJERO' => 0, 'ALMACENERO' => 0];
$totalSuc = [];
foreach ($filas as $f) {
    $totalRol[$f['rol']] = ($totalRol[$f['rol']] ?? 0) + 1;
    $totalSuc[$f['sucursal']] = ($totalSuc[$f['sucursal']] ?? 0) + 1;
}
ksort($totalSuc);
?>
<div class="tw">

  <div class="tools prd-tools">
    <h3>Empleados <span class="tag b" id="emp-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="emp-q" placeholder="Buscar por nombre o usuario…" autocomplete="off">
    </label>
    <a class="btn y" href="<?= Controller::url('empleados/nuevo') ?>">+ Nuevo</a>
  </div>

  <div class="prd-filtros">
    <div class="prd-fila" id="emp-f-rol">
      <small>Rol</small>
      <button type="button" class="prd-chip on" data-v="">Todos <em><?= count($filas) ?></em></button>
      <?php foreach ($totalRol as $rol => $n): ?>
        <button type="button" class="prd-chip" data-v="<?= e($rol) ?>"><?= e(ucfirst(mb_strtolower($rol))) ?> <em><?= (int)$n ?></em></button>
      <?php endforeach; ?>
    </div>
    <div class="prd-fila" id="emp-f-suc">
      <small>Sucursal</small>
      <button type="button" class="prd-chip on" data-v="">Todas</button>
      <?php foreach ($totalSuc as $nombre => $n): ?>
        <button type="button" class="prd-chip" data-v="<?= e($nombre) ?>"><?= e($nombre) ?> <em><?= (int)$n ?></em></button>
      <?php endforeach; ?>
    </div>
    <div class="prd-fila" id="emp-f-est">
      <small>Estado</small>
      <button type="button" class="prd-chip on" data-v="">Todos</button>
      <button type="button" class="prd-chip" data-v="1">Activos</button>
      <button type="button" class="prd-chip" data-v="0">Inactivos</button>
    </div>
  </div>

  <div class="emp-grid">
    <?php foreach ($filas as $f): ?>
      <article class="emp-item <?= $f['activo'] ? '' : 'off' ?>"
               data-rol="<?= e($f['rol']) ?>"
               data-suc="<?= e($f['sucursal']) ?>"
               data-act="<?= $f['activo'] ? '1' : '0' ?>"
               data-txt="<?= e(mb_strtolower($f['nombre'] . ' ' . $f['usuario'])) ?>">
        <div class="emp-top">
          <div class="emp-av <?= e($f['rol']) ?>"><?= e(mb_strtoupper(mb_substr($f['nombre'], 0, 1))) ?></div>
          <div>
            <h4><?= e($f['nombre']) ?></h4>
            <small>@<?= e($f['usuario']) ?></small>
          </div>
          <span class="tag <?= empTagRol($f['rol']) ?>"><?= e($f['rol']) ?></span>
        </div>
        <div class="emp-datos">
          <div><span>Sucursal</span><b><?= e($f['sucursal']) ?></b></div>
          <div><span>Supervisor</span><b><?= $f['supervisor'] ? e($f['supervisor']) : '—' ?></b></div>
          <div><span>Estado</span><span class="tag <?= $f['activo'] ? 'ok' : 'bad' ?>"><?= $f['activo'] ? 'Activo' : 'Inactivo' ?></span></div>
        </div>
        <div class="emp-foot">
          <small>N.º <?= (int)$f['id'] ?></small>
          <div class="emp-acts">
            <a class="act" title="Editar" href="<?= Controller::url('empleados/editar', ['id' => $f['id']]) ?>">✏️</a>
            <form class="inline" method="post" action="<?= Controller::url('empleados/eliminar') ?>" data-confirm="¿Eliminar este registro?">
              <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
              <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button class="act" title="Eliminar">🗑️</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="emp-vacio">Sin empleados que coincidan</div>
</div>

<script>
(function () {
  var f = {rol: '', suc: '', act: ''};
  var q = document.getElementById('emp-q');
  var cards = [].slice.call(document.querySelectorAll('.emp-item'));

  function aplicar() {
    var t = q.value.trim().toLowerCase(), n = 0;
    cards.forEach(function (c) {
      var ok = (!f.rol || c.dataset.rol === f.rol) &&
               (!f.suc || c.dataset.suc === f.suc) &&
               (!f.act || c.dataset.act === f.act) &&
               (!t || c.dataset.txt.indexOf(t) > -1);
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    document.getElementById('emp-total').textContent = n;
    document.getElementById('emp-vacio').classList.toggle('show', n === 0);
  }

  function chips(id, key) {
    var btns = document.querySelectorAll('#' + id + ' .prd-chip');
    btns.forEach(function (b) {
      b.addEventListener('click', function () {
        btns.forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on');
        f[key] = b.dataset.v;
        aplicar();
      });
    });
  }
  chips('emp-f-rol', 'rol');
  chips('emp-f-suc', 'suc');
  chips('emp-f-est', 'act');
  q.addEventListener('input', aplicar);
})();
</script>