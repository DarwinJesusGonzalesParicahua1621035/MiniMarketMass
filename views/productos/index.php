<?php
/* Ícono según categoría (si no coincide, usa 📦) */
function prdIcono(string $cat): string
{
    $mapa = [
        'abarrotes' => '🛒', 'limpieza' => '🧼', 'bebidas' => '🥤',
        'lácteos'   => '🥛', 'lacteos'  => '🥛', 'snacks'  => '🍿',
        'carnes'    => '🥩', 'frutas'   => '🍎', 'verduras' => '🥬',
        'panadería' => '🥖', 'panaderia' => '🥖', 'licores' => '🍷',
        'cuidado personal' => '🧴', 'congelados' => '🧊',
    ];
    return $mapa[mb_strtolower(trim($cat))] ?? '📦';
}

/* Contadores para los chips */
$totalCat = [];
foreach ($filas as $f) {
    $totalCat[$f['categoria']] = ($totalCat[$f['categoria']] ?? 0) + 1;
}
ksort($totalCat);
?>
<div class="tw">

  <div class="tools prd-tools">
    <h3>Productos <span class="tag b" id="prd-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="prd-q" placeholder="Buscar por nombre o código…" autocomplete="off">
    </label>
    <a class="btn y" href="<?= Controller::url('productos/nuevo') ?>">+ Nuevo producto</a>
  </div>

  <div class="prd-filtros">
    <div class="prd-fila" id="prd-f-cat">
      <small>Categoría</small>
      <button type="button" class="prd-chip on" data-v="">Todas <em><?= count($filas) ?></em></button>
      <?php foreach ($totalCat as $nombre => $n): ?>
        <button type="button" class="prd-chip" data-v="<?= e($nombre) ?>"><?= e($nombre) ?> <em><?= (int)$n ?></em></button>
      <?php endforeach; ?>
    </div>
    <div class="prd-fila" id="prd-f-est">
      <small>Estado</small>
      <button type="button" class="prd-chip on" data-v="">Todos</button>
      <button type="button" class="prd-chip" data-v="1">Activos</button>
      <button type="button" class="prd-chip" data-v="0">Inactivos</button>
    </div>
  </div>

  <div class="prd-grid" id="prd-grid">
    <?php foreach ($filas as $f): ?>
      <article class="prd-item <?= $f['activo'] ? '' : 'off' ?>"
               data-cat="<?= e($f['categoria']) ?>"
               data-act="<?= $f['activo'] ? '1' : '0' ?>"
               data-txt="<?= e(mb_strtolower($f['nombre'] . ' ' . $f['codigo_barras'])) ?>">
        <div class="prd-top">
          <div class="prd-ico"><?= prdIcono($f['categoria']) ?></div>
          <div>
            <h4><?= e($f['nombre']) ?></h4>
            <small><?= e($f['codigo_barras']) ?></small>
          </div>
        </div>
        <div class="prd-meta">
          <span class="tag b"><?= e($f['categoria']) ?></span>
          <span class="tag"><?= e($f['unidad']) ?></span>
          <span class="tag <?= $f['activo'] ? 'ok' : 'bad' ?>"><?= $f['activo'] ? 'Activo' : 'Inactivo' ?></span>
        </div>
        <div class="prd-foot">
          <div class="prd-precio"><small>Precio</small><b><?= money($f['precio']) ?></b></div>
          <div class="prd-acts">
            <a class="act" title="Ver precios" href="<?= Controller::url('productos/ver', ['id' => $f['id']]) ?>">👁️</a>
            <a class="act" title="Editar" href="<?= Controller::url('productos/editar', ['id' => $f['id']]) ?>">✏️</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="prd-vacio">Sin productos que coincidan</div>
</div>

<script>
(function () {
  var cat = '', est = '';
  var q = document.getElementById('prd-q');
  var cards = [].slice.call(document.querySelectorAll('.prd-item'));

  function aplicar() {
    var t = q.value.trim().toLowerCase(), n = 0;
    cards.forEach(function (c) {
      var ok = (!cat || c.dataset.cat === cat) &&
               (!est || c.dataset.act === est) &&
               (!t || c.dataset.txt.indexOf(t) > -1);
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    document.getElementById('prd-total').textContent = n;
    document.getElementById('prd-vacio').classList.toggle('show', n === 0);
  }

  function chips(id, set) {
    var btns = document.querySelectorAll('#' + id + ' .prd-chip');
    btns.forEach(function (b) {
      b.addEventListener('click', function () {
        btns.forEach(function (x) { x.classList.remove('on'); });
        b.classList.add('on');
        set(b.dataset.v);
        aplicar();
      });
    });
  }
  chips('prd-f-cat', function (v) { cat = v; });
  chips('prd-f-est', function (v) { est = v; });
  q.addEventListener('input', aplicar);
})();
</script>