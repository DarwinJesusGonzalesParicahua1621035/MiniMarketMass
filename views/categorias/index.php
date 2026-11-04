<?php
/* Imagen de la categoría: public/img/categorias/{id}.jpg (si no existe, queda vacía) */
$fotos = [];
foreach ($filas as $f) {
    $archivo = ROOT . '/public/img/categorias/' . (int)$f['id'] . '.jpg';
    $fotos[$f['id']] = file_exists($archivo)
        ? Controller::base() . '/img/categorias/' . (int)$f['id'] . '.jpg'
        : '';
}
?>
<div class="tw">

  <div class="tools suc-tools">
    <h3>Categorías <span class="tag b" id="cat-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="cat-q" placeholder="Buscar categoría…" autocomplete="off">
    </label>
    <a class="btn y" href="<?= Controller::url('categorias/nuevo') ?>">+ Nuevo</a>
  </div>

  <div class="cat-grid">
    <?php foreach ($filas as $f): $foto = $fotos[$f['id']]; $n = (int)$f['productos']; ?>
      <button type="button" class="cat-item"
              data-id="<?= (int)$f['id'] ?>"
              data-nombre="<?= e($f['nombre']) ?>"
              data-prod="<?= $n ?>"
              data-img="<?= e($foto) ?>"
              data-ver="<?= e(Controller::url('productos/index', ['categoria' => $f['id']])) ?>"
              data-txt="<?= e(mb_strtolower($f['nombre'])) ?>">
        <?php if ($foto): ?>
          <div class="cat-foto"><img src="<?= e($foto) ?>" alt="<?= e($f['nombre']) ?>"></div>
        <?php else: ?>
          <div class="cat-foto sin">🏷️</div>
        <?php endif; ?>
        <div class="cat-info">
          <div>
            <h4><?= e($f['nombre']) ?></h4>
            <small><?= $n ?> <?= $n === 1 ? 'producto' : 'productos' ?></small>
          </div>
          <span class="cat-flecha">›</span>
        </div>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="cat-vacio">Sin categorías que coincidan</div>
</div>

<!-- ============ DRAWER ============ -->
<div class="dr-fondo" id="dr-fondo"></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="dr-nombre" aria-hidden="true">

  <div class="dr-foto cat" id="dr-foto">
    <img id="dr-img" src="" alt="">
    <span id="dr-emoji" style="display:none">🏷️</span>
    <button type="button" class="dr-cerrar" id="dr-cerrar" aria-label="Cerrar">✕</button>
  </div>

  <!-- Vista normal -->
  <div class="dr-vista">
    <div class="dr-cuerpo">
      <h2 id="dr-nombre"></h2>
      <small id="dr-sub"></small>
      <div class="dr-datos">
        <div><span>Productos en esta categoría</span><span class="tag b" id="dr-prod"></span></div>
      </div>
    </div>
    <div class="dr-acciones">
      <a class="btn g flex full" href="#" id="dr-ver">📦 Ver productos de esta categoría</a>
      <button type="button" class="btn y flex" id="dr-editar">✏️ Editar</button>
      <form method="post" action="<?= Controller::url('categorias/eliminar') ?>" data-confirm="¿Eliminar esta categoría?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="dr-id" value="">
        <button class="btn r flex">🗑️ Eliminar</button>
      </form>
    </div>
  </div>

  <!-- Modo edición -->
  <form class="dr-form" method="post" action="<?= Controller::url('categorias/guardar') ?>">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="dr-id-edit" value="">
    <div class="dr-cuerpo">
      <h2>Editar categoría</h2>
      <label for="dr-nombre-edit">Nombre</label>
      <input class="dr-input" type="text" name="nombre" id="dr-nombre-edit" maxlength="60" required autocomplete="off">
    </div>
    <div class="dr-acciones">
      <button type="button" class="btn g flex" id="dr-cancelar">Cancelar</button>
      <button class="btn y flex">Guardar</button>
    </div>
  </form>

</aside>

<script>
(function () {
  var fondo = document.getElementById('dr-fondo'),
      dr = document.getElementById('drawer'),
      cards = [].slice.call(document.querySelectorAll('.cat-item')),
      q = document.getElementById('cat-q'),
      inputNombre = document.getElementById('dr-nombre-edit'),
      ultimo = null;

  function editar(si) {
    dr.classList.toggle('editando', si);
    if (si) { inputNombre.focus(); inputNombre.select(); }
    else { document.getElementById('dr-editar').focus(); }
  }

  function abrir(c) {
    ultimo = c;
    var img = document.getElementById('dr-img'), foto = document.getElementById('dr-foto');
    if (c.dataset.img) {
      img.src = c.dataset.img; img.alt = c.dataset.nombre;
      foto.classList.remove('sin'); document.getElementById('dr-emoji').style.display = 'none';
    } else {
      img.removeAttribute('src');
      foto.classList.add('sin'); document.getElementById('dr-emoji').style.display = '';
    }
    document.getElementById('dr-nombre').textContent = c.dataset.nombre;
    document.getElementById('dr-sub').textContent = 'Categoría N.º ' + c.dataset.id;
    document.getElementById('dr-prod').textContent = c.dataset.prod;
    document.getElementById('dr-ver').href = c.dataset.ver;
    document.getElementById('dr-id').value = c.dataset.id;
    document.getElementById('dr-id-edit').value = c.dataset.id;
    inputNombre.value = c.dataset.nombre;
    dr.classList.remove('editando');
    fondo.classList.add('abierto'); dr.classList.add('abierto');
    dr.setAttribute('aria-hidden', 'false');
    document.getElementById('dr-cerrar').focus();
  }

  function cerrar() {
    fondo.classList.remove('abierto'); dr.classList.remove('abierto');
    dr.classList.remove('editando');
    dr.setAttribute('aria-hidden', 'true');
    if (ultimo) ultimo.focus();
  }

  cards.forEach(function (c) { c.addEventListener('click', function () { abrir(c); }); });
  fondo.addEventListener('click', cerrar);
  document.getElementById('dr-cerrar').addEventListener('click', cerrar);
  document.getElementById('dr-editar').addEventListener('click', function () { editar(true); });
  document.getElementById('dr-cancelar').addEventListener('click', function () {
    inputNombre.value = ultimo ? ultimo.dataset.nombre : '';
    editar(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    if (dr.classList.contains('editando')) { editar(false); } else { cerrar(); }
  });

  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), n = 0;
    cards.forEach(function (c) {
      var ok = !t || c.dataset.txt.indexOf(t) > -1;
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    document.getElementById('cat-total').textContent = n;
    document.getElementById('cat-vacio').classList.toggle('show', n === 0);
  });
})();
</script>