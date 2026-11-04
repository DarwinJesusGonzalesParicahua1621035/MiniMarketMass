<?php
/* Foto de la sucursal: public/img/sucursales/{id}.jpg (si no existe, queda vacía) */
$fotos = [];
foreach ($filas as $f) {
    $archivo = ROOT . '/public/img/sucursales/' . (int)$f['id'] . '.jpg';
    $fotos[$f['id']] = file_exists($archivo)
        ? Controller::base() . '/img/sucursales/' . (int)$f['id'] . '.jpg'
        : '';
}
?>
<div class="tw">

  <div class="tools suc-tools">
    <h3>Sucursales <span class="tag b" id="suc-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="suc-q" placeholder="Buscar sucursal o distrito…" autocomplete="off">
    </label>
    <a class="btn y" href="<?= Controller::url('sucursales/nuevo') ?>">+ Nuevo</a>
  </div>

  <div class="suc-grid">
    <?php foreach ($filas as $f): $foto = $fotos[$f['id']]; ?>
      <button type="button" class="suc-item"
              data-id="<?= (int)$f['id'] ?>"
              data-nombre="<?= e($f['nombre']) ?>"
              data-dir="<?= e($f['direccion']) ?>"
              data-dist="<?= e($f['distrito']) ?>"
              data-sb="<?= e($f['serie_boleta']) ?>"
              data-sf="<?= e($f['serie_factura']) ?>"
              data-cajas="<?= (int)$f['cajas'] ?>"
              data-img="<?= e($foto) ?>"
              data-editar="<?= e(Controller::url('sucursales/editar', ['id' => $f['id']])) ?>"
              data-txt="<?= e(mb_strtolower($f['nombre'] . ' ' . $f['distrito'] . ' ' . $f['direccion'])) ?>">
        <?php if ($foto): ?>
          <div class="suc-foto"><img src="<?= e($foto) ?>" alt="<?= e($f['nombre']) ?>"></div>
        <?php else: ?>
          <div class="suc-foto sin">🏪</div>
        <?php endif; ?>
        <div class="suc-info">
          <div>
            <h4><?= e($f['nombre']) ?></h4>
            <small><?= e($f['distrito']) ?></small>
          </div>
          <span class="suc-flecha">›</span>
        </div>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="suc-vacio">Sin sucursales que coincidan</div>
</div>

<!-- ============ DRAWER ============ -->
<div class="dr-fondo" id="dr-fondo"></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="dr-nombre" aria-hidden="true">
  <div class="dr-foto" id="dr-foto">
    <img id="dr-img" src="" alt="">
    <span id="dr-emoji" style="display:none">🏪</span>
    <button type="button" class="dr-cerrar" id="dr-cerrar" aria-label="Cerrar">✕</button>
  </div>
  <div class="dr-cuerpo">
    <h2 id="dr-nombre"></h2>
    <small id="dr-sub"></small>
    <div class="dr-datos">
      <div><span>Dirección</span><b id="dr-dir"></b></div>
      <div><span>Distrito</span><b id="dr-dist"></b></div>
      <div><span>Serie boleta</span><b id="dr-sb"></b></div>
      <div><span>Serie factura</span><b id="dr-sf"></b></div>
      <div><span>Cajas</span><b id="dr-cajas"></b></div>
    </div>
  </div>
  <div class="dr-acciones">
    <a class="btn y flex" href="#" id="dr-editar">✏️ Editar</a>
    <form method="post" action="<?= Controller::url('sucursales/eliminar') ?>" data-confirm="¿Eliminar esta sucursal?">
      <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
      <input type="hidden" name="id" id="dr-id" value="">
      <button class="btn r flex">🗑️ Eliminar</button>
    </form>
  </div>
</aside>

<script>
(function () {
  var fondo = document.getElementById('dr-fondo'),
      dr = document.getElementById('drawer'),
      cards = [].slice.call(document.querySelectorAll('.suc-item')),
      q = document.getElementById('suc-q'), ultimo = null;

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
    document.getElementById('dr-sub').textContent = 'Sucursal N.º ' + c.dataset.id;
    document.getElementById('dr-dir').textContent = c.dataset.dir;
    document.getElementById('dr-dist').textContent = c.dataset.dist;
    document.getElementById('dr-sb').textContent = c.dataset.sb;
    document.getElementById('dr-sf').textContent = c.dataset.sf;
    document.getElementById('dr-cajas').textContent = c.dataset.cajas;
    document.getElementById('dr-editar').href = c.dataset.editar;
    document.getElementById('dr-id').value = c.dataset.id;
    fondo.classList.add('abierto'); dr.classList.add('abierto');
    dr.setAttribute('aria-hidden', 'false');
    document.getElementById('dr-cerrar').focus();
  }

  function cerrar() {
    fondo.classList.remove('abierto'); dr.classList.remove('abierto');
    dr.setAttribute('aria-hidden', 'true');
    if (ultimo) ultimo.focus();
  }

  cards.forEach(function (c) { c.addEventListener('click', function () { abrir(c); }); });
  fondo.addEventListener('click', cerrar);
  document.getElementById('dr-cerrar').addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });

  q.addEventListener('input', function () {
    var t = q.value.trim().toLowerCase(), n = 0;
    cards.forEach(function (c) {
      var ok = !t || c.dataset.txt.indexOf(t) > -1;
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    document.getElementById('suc-total').textContent = n;
    document.getElementById('suc-vacio').classList.toggle('show', n === 0);
  });
})();
</script>