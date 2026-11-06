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
    <button type="button" class="btn y" id="pr-nuevo">+ Nuevo producto</button>
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
               data-id="<?= (int)$f['id'] ?>"
               data-catid="<?= (int)$f['categoria_id'] ?>"
               data-provid="<?= (int)$f['proveedor_id'] ?>"
               data-codigo="<?= e($f['codigo_barras']) ?>"
               data-nombre="<?= e($f['nombre']) ?>"
               data-unidad="<?= e($f['unidad']) ?>"
               data-precio="<?= e($f['precio']) ?>"
               data-asig="<?= e(json_encode((object)($asignaciones[(int)$f['id']] ?? []))) ?>"
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
            <button type="button" class="act pr-editar" title="Editar">✏️</button>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="prd-vacio">Sin productos que coincidan</div>
</div>

<!-- ============ DRAWER: nuevo / editar producto ============ -->
<div class="dr-fondo" id="dr-fondo"></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-hidden="true">
  <form class="dr-form" id="pr-form" method="post" action="<?= Controller::url('productos/guardar') ?>" style="display:flex">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="i-id" value="0">
    <div class="dr-cab">
      <button type="button" class="dr-cerrar" id="dr-cerrar" aria-label="Cerrar">✕</button>
      <div class="pv-av" id="f-ico">🛒</div>
      <h2 id="f-titulo">Nuevo producto</h2>
    </div>
    <div class="dr-cuerpo">
      <div class="pv-campo"><label for="i-nombre">Nombre</label>
        <input class="dr-input" id="i-nombre" name="nombre" maxlength="120" required></div>
      <div class="pv-campo"><label for="i-codigo">Código de barras <em>(13 caracteres)</em></label>
        <input class="dr-input" id="i-codigo" name="codigo_barras" maxlength="13" minlength="13" pattern=".{13}" inputmode="numeric" required></div>
      <div class="pv-campos2">
        <div class="pv-campo"><label for="i-cat">Categoría</label>
          <select class="dr-input" id="i-cat" name="categoria_id" required>
            <option value="">— Elige —</option>
            <?php foreach ($categorias as $k => $t): ?><option value="<?= (int)$k ?>"><?= e($t) ?></option><?php endforeach; ?>
          </select></div>
        <div class="pv-campo"><label for="i-prov">Proveedor</label>
          <select class="dr-input" id="i-prov" name="proveedor_id" required>
            <option value="">— Elige —</option>
            <?php foreach ($proveedores as $k => $t): ?><option value="<?= (int)$k ?>"><?= e($t) ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <div class="pv-campos2">
        <div class="pv-campo"><label for="i-unidad">Unidad</label>
          <input class="dr-input" id="i-unidad" name="unidad" maxlength="20" placeholder="unidad, kg, litro" list="pr-unidades" required>
          <datalist id="pr-unidades"><option value="unidad"><option value="kg"><option value="litro"><option value="paquete"></datalist></div>
        <div class="pv-campo"><label for="i-precio">Precio (S/)</label>
          <input class="dr-input" id="i-precio" name="precio" type="number" step="0.01" min="0" max="99999999.99" required></div>
      </div>
      <div class="pv-campo"><label for="i-act">Estado</label>
        <select class="dr-input" id="i-act" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>

      <div class="pv-campo">
        <label>Sucursales donde se vende <em>(cada una con su propio stock)</em></label>
        <div class="ps-lista" id="ps-lista">
          <?php foreach ($sucursales as $sid => $sn): ?>
            <div class="ps-fila" data-suc="<?= (int)$sid ?>">
              <label class="ps-chk">
                <input type="checkbox" name="suc[<?= (int)$sid ?>][on]" value="1">
                <span><?= e($sn) ?></span>
              </label>
              <input class="dr-input ps-ini" type="text" inputmode="numeric" pattern="[0-9]{1,7}" maxlength="7"
                     name="suc[<?= (int)$sid ?>][stock]" placeholder="Stock inicial" autocomplete="off" disabled>
              <span class="ps-act"></span>
            </div>
          <?php endforeach; ?>
        </div>
        <small class="ps-ayuda">El stock inicial solo se pide al agregar la sucursal; después se ajusta con movimientos.</small>
      </div>
    </div>
    <div class="dr-acciones">
      <button type="button" class="btn g flex" id="dr-cancelar">Cancelar</button>
      <button class="btn y flex">Guardar</button>
    </div>
  </form>
</aside>

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

  /* ---- Drawer nuevo / editar ---- */
  var $ = function (id) { return document.getElementById(id); };
  var fondo = $('dr-fondo'), dr = $('drawer'), ultimo = null;
  function abrir() { fondo.classList.add('abierto'); dr.classList.add('abierto'); dr.setAttribute('aria-hidden', 'false'); }
  function cerrar() {
    fondo.classList.remove('abierto'); dr.classList.remove('abierto'); dr.setAttribute('aria-hidden', 'true');
    if (ultimo) ultimo.focus();
  }
  function formulario(c) {
    var d = c ? c.dataset : {};
    $('f-titulo').textContent = c ? 'Editar producto' : 'Nuevo producto';
    $('f-ico').textContent = c ? c.querySelector('.prd-ico').textContent : '🛒';
    $('i-id').value = c ? d.id : 0;
    $('i-nombre').value = c ? d.nombre : '';
    $('i-codigo').value = c ? d.codigo : '';
    $('i-cat').value = c ? d.catid : '';
    $('i-prov').value = c ? d.provid : '';
    $('i-unidad').value = c ? d.unidad : '';
    $('i-precio').value = c ? d.precio : '';
    $('i-act').value = c ? d.act : '1';
    var asig = {};
    try { asig = c ? JSON.parse(d.asig || '{}') : {}; } catch (e) {}
    [].slice.call(document.querySelectorAll('#ps-lista .ps-fila')).forEach(function (f) {
      var chk = f.querySelector('input[type=checkbox]'), ini = f.querySelector('.ps-ini'), act = f.querySelector('.ps-act');
      var tiene = Object.prototype.hasOwnProperty.call(asig, f.dataset.suc);
      chk.checked = tiene;
      ini.value = '';
      ini.style.display = tiene ? 'none' : '';
      ini.disabled = tiene || !chk.checked;
      act.textContent = tiene ? 'Stock actual: ' + (Math.round(asig[f.dataset.suc] * 1000) / 1000) : '';
      f.classList.toggle('on', chk.checked);
    });
    abrir(); $('i-nombre').focus();
  }
  [].slice.call(document.querySelectorAll('#ps-lista .ps-fila')).forEach(function (f) {
    var chk = f.querySelector('input[type=checkbox]'), ini = f.querySelector('.ps-ini');
    chk.addEventListener('change', function () {
      f.classList.toggle('on', chk.checked);
      if (ini.style.display !== 'none') { ini.disabled = !chk.checked; if (chk.checked) ini.focus(); else ini.value = ''; }
    });
    ini.addEventListener('input', function () { ini.value = ini.value.replace(/[^0-9]/g, ''); });
  });
  $('pr-nuevo').addEventListener('click', function () { ultimo = this; formulario(null); });
  cards.forEach(function (c) {
    c.querySelector('.pr-editar').addEventListener('click', function () { ultimo = this; formulario(c); });
  });
  $('dr-cerrar').addEventListener('click', cerrar);
  $('dr-cancelar').addEventListener('click', cerrar);
  fondo.addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dr.classList.contains('abierto')) cerrar(); });
})();
</script>