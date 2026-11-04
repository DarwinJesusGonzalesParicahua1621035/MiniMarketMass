<?php
/* Iniciales para el cuadro amarillo (ej. "Alicorp Distribuciones SAC" → "AD") */
function pvIni(string $s): string
{
    $w = preg_split('/\s+/', trim($s));
    $a = mb_strtoupper(mb_substr($w[0] ?? '', 0, 1));
    $b = isset($w[1]) ? mb_strtoupper(mb_substr($w[1], 0, 1)) : '';
    return $a . $b;
}

/* Categorías que abastece cada proveedor + contador para los chips */
$catsDe = [];
$totalCat = [];
foreach ($filas as $f) {
    $c = [];
    foreach ($prods[$f['id']] ?? [] as $x) {
        $c[$x['categoria']] = true;
    }
    $catsDe[$f['id']] = array_keys($c);
    foreach ($catsDe[$f['id']] as $nombre) {
        $totalCat[$nombre] = ($totalCat[$nombre] ?? 0) + 1;
    }
}
ksort($totalCat);
?>
<div class="tw">

  <div class="tools prd-tools">
    <h3>Proveedores <span class="tag b" id="pv-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="pv-q" placeholder="Buscar por razón social, RUC o contacto…" autocomplete="off">
    </label>
    <button type="button" class="btn y" id="pv-nuevo">+ Nuevo proveedor</button>
  </div>

  <div class="prd-filtros">
    <div class="prd-fila" id="pv-f-est">
      <small>Estado</small>
      <button type="button" class="prd-chip on" data-v="">Todos</button>
      <button type="button" class="prd-chip" data-v="1">Activos</button>
      <button type="button" class="prd-chip" data-v="0">Inactivos</button>
    </div>
    <div class="prd-fila" id="pv-f-cat">
      <small>Abastece</small>
      <button type="button" class="prd-chip on" data-v="">Todas</button>
      <?php foreach ($totalCat as $nombre => $n): ?>
        <button type="button" class="prd-chip" data-v="<?= e($nombre) ?>"><?= e($nombre) ?> <em><?= (int)$n ?></em></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="pv-grid">
    <?php foreach ($filas as $f):
        $id    = (int)$f['id'];
        $lista = $prods[$id] ?? [];
        $n     = count($lista);
        $json  = array_map(fn($x) => [
            'n' => $x['nombre'], 'c' => $x['categoria'], 'p' => (float)$x['precio'], 'a' => (int)$x['activo'],
        ], $lista);
    ?>
      <button type="button" class="pv-item <?= $f['activo'] ? '' : 'off' ?>"
              data-id="<?= $id ?>"
              data-ruc="<?= e($f['ruc']) ?>"
              data-razon="<?= e($f['razon_social']) ?>"
              data-contacto="<?= e($f['contacto']) ?>"
              data-tel="<?= e($f['telefono']) ?>"
              data-correo="<?= e($f['correo'] ?? '') ?>"
              data-dir="<?= e($f['direccion'] ?? '') ?>"
              data-act="<?= $f['activo'] ? '1' : '0' ?>"
              data-cats="<?= e(implode('|', $catsDe[$id])) ?>"
              data-prods="<?= e(json_encode($json, JSON_UNESCAPED_UNICODE)) ?>"
              data-ver="<?= e(Controller::url('productos/index', ['proveedor' => $id])) ?>"
              data-txt="<?= e(mb_strtolower($f['razon_social'] . ' ' . $f['ruc'] . ' ' . $f['contacto'])) ?>">
        <div class="pv-top">
          <div class="pv-av"><?= e(pvIni($f['razon_social'])) ?></div>
          <div>
            <h4><?= e($f['razon_social']) ?></h4>
            <small>RUC <?= e($f['ruc']) ?></small>
          </div>
          <span class="tag <?= $f['activo'] ? 'ok' : 'bad' ?>"><?= $f['activo'] ? 'Activo' : 'Inactivo' ?></span>
        </div>
        <div class="pv-datos">
          <div><i>👤</i><span><?= e($f['contacto']) ?></span></div>
          <div><i>📞</i><span><?= e($f['telefono']) ?></span></div>
          <div><i>✉️</i>
            <?php if ($f['correo']): ?><span><?= e($f['correo']) ?></span>
            <?php else: ?><span class="vacio">Sin correo</span><?php endif; ?>
          </div>
        </div>
        <div class="pv-foot">
          <span class="n">📦 <?= $n ?> <?= $n === 1 ? 'producto' : 'productos' ?></span>
          <?php foreach ($catsDe[$id] as $c): ?><span class="tag y"><?= e($c) ?></span><?php endforeach; ?>
        </div>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="pv-vacio">Sin proveedores que coincidan</div>
</div>

<!-- ============ DRAWER ============ -->
<div class="dr-fondo" id="dr-fondo"></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-hidden="true">

  <!-- Vista -->
  <div class="dr-vista">
    <div class="dr-cab">
      <button type="button" class="dr-cerrar" id="dr-cerrar" aria-label="Cerrar">✕</button>
      <div class="pv-av" id="v-av"></div>
      <h2 id="v-razon"></h2>
      <small><span id="v-ruc"></span><span class="tag" id="v-est"></span></small>
    </div>
    <div class="dr-cuerpo">
      <p class="dr-sec">Contacto</p>
      <div class="dr-lista">
        <div class="dr-fila"><i>👤</i><div><small>Persona de contacto</small><b id="v-contacto"></b></div></div>
        <div class="dr-fila"><i>📞</i><div><small>Teléfono</small><b id="v-tel"></b></div><a class="dr-link" id="v-llamar" href="#">Llamar</a></div>
        <div class="dr-fila"><i>✉️</i><div><small>Correo</small><b id="v-correo"></b></div><a class="dr-link" id="v-escribir" href="#">Escribir</a></div>
        <div class="dr-fila"><i>📍</i><div><small>Dirección</small><b id="v-dir"></b></div></div>
      </div>
      <p class="dr-sec">Productos que abastece <span class="tag b" id="v-nprod"></span></p>
      <div class="dr-lista" id="v-prods"></div>
    </div>
    <div class="dr-acciones">
      <a class="btn g flex full" href="#" id="dr-ver">📦 Ver productos de este proveedor</a>
      <button type="button" class="btn y flex" id="dr-editar">✏️ Editar</button>
      <form method="post" action="<?= Controller::url('proveedores/eliminar') ?>" data-confirm="¿Eliminar este proveedor?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="dr-id" value="">
        <button class="btn r flex">🗑️ Eliminar</button>
      </form>
    </div>
  </div>

  <!-- Formulario (editar / nuevo) -->
  <form class="dr-form" id="pv-form" method="post" action="<?= Controller::url('proveedores/guardar') ?>">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="i-id" value="0">
    <div class="dr-cab">
      <button type="button" class="dr-cerrar" id="dr-cerrar2" aria-label="Cerrar">✕</button>
      <div class="pv-av">🏭</div>
      <h2 id="f-titulo">Editar proveedor</h2>
    </div>
    <div class="dr-cuerpo">
      <div class="pv-campos2">
        <div class="pv-campo"><label for="i-ruc">RUC <em>(11 dígitos)</em></label>
          <input class="dr-input" id="i-ruc" name="ruc" inputmode="numeric" maxlength="11" pattern="\d{11}" required></div>
        <div class="pv-campo"><label for="i-tel">Teléfono</label>
          <input class="dr-input" id="i-tel" name="telefono" maxlength="15" required></div>
      </div>
      <div class="pv-campo"><label for="i-razon">Razón social</label>
        <input class="dr-input" id="i-razon" name="razon_social" maxlength="120" required></div>
      <div class="pv-campo"><label for="i-contacto">Persona de contacto</label>
        <input class="dr-input" id="i-contacto" name="contacto" maxlength="100" required></div>
      <div class="pv-campo"><label for="i-correo">Correo <em>(opcional)</em></label>
        <input class="dr-input" id="i-correo" name="correo" type="email" maxlength="100"></div>
      <div class="pv-campo"><label for="i-dir">Dirección <em>(opcional)</em></label>
        <input class="dr-input" id="i-dir" name="direccion" maxlength="150"></div>
      <div class="pv-campo"><label for="i-act">Estado</label>
        <select class="dr-input" id="i-act" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
    </div>
    <div class="dr-acciones">
      <button type="button" class="btn g flex" id="dr-cancelar">Cancelar</button>
      <button class="btn y flex">Guardar</button>
    </div>
  </form>
</aside>

<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); };
  var ini = function (s) { var w = s.trim().split(/\s+/); return (w[0][0] + (w[1] ? w[1][0] : '')).toUpperCase(); };

  var fondo = $('dr-fondo'), dr = $('drawer'), q = $('pv-q'),
      cards = [].slice.call(document.querySelectorAll('.pv-item')),
      f = {est: '', cat: ''}, ultimo = null, actual = null;

  /* ---- Filtros ---- */
  function filtrar() {
    var t = q.value.trim().toLowerCase(), n = 0;
    cards.forEach(function (c) {
      var ok = (!f.est || c.dataset.act === f.est) &&
               (!f.cat || c.dataset.cats.split('|').indexOf(f.cat) > -1) &&
               (!t || c.dataset.txt.indexOf(t) > -1);
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    $('pv-total').textContent = n;
    $('pv-vacio').classList.toggle('show', n === 0);
  }
  function chips(id, key) {
    var b = document.querySelectorAll('#' + id + ' .prd-chip');
    [].forEach.call(b, function (x) {
      x.addEventListener('click', function () {
        [].forEach.call(b, function (y) { y.classList.remove('on'); });
        x.classList.add('on'); f[key] = x.dataset.v; filtrar();
      });
    });
  }
  chips('pv-f-est', 'est'); chips('pv-f-cat', 'cat');
  q.addEventListener('input', filtrar);

  /* ---- Drawer ---- */
  function abrirDrawer() {
    fondo.classList.add('abierto'); dr.classList.add('abierto'); dr.setAttribute('aria-hidden', 'false');
  }
  function cerrar() {
    fondo.classList.remove('abierto'); dr.classList.remove('abierto'); dr.classList.remove('editando');
    dr.setAttribute('aria-hidden', 'true'); if (ultimo) ultimo.focus();
  }

  function ver(c) {
    ultimo = c; actual = c;
    var d = c.dataset, act = d.act === '1', lista = [];
    try { lista = JSON.parse(d.prods || '[]'); } catch (e) {}
    $('v-av').textContent = ini(d.razon);
    $('v-razon').textContent = d.razon;
    $('v-ruc').textContent = 'RUC ' + d.ruc;
    $('v-est').textContent = act ? 'Activo' : 'Inactivo';
    $('v-est').className = 'tag ' + (act ? 'ok' : 'bad');
    $('v-contacto').textContent = d.contacto;
    $('v-tel').textContent = d.tel;
    $('v-llamar').href = 'tel:' + d.tel;
    $('v-correo').innerHTML = d.correo ? esc(d.correo) : '<span class="vacio">Sin correo registrado</span>';
    $('v-escribir').style.display = d.correo ? '' : 'none';
    $('v-escribir').href = 'mailto:' + d.correo;
    $('v-dir').innerHTML = d.dir ? esc(d.dir) : '<span class="vacio">Sin dirección registrada</span>';
    $('v-nprod').textContent = lista.length;
    $('v-prods').innerHTML = lista.length ? lista.map(function (x) {
      return '<div class="dr-prod"><span class="pv-punto ' + (x.a ? '' : 'off') + '"></span><div class="p"><b>' + esc(x.n) +
             '</b><small>' + esc(x.c) + (x.a ? '' : ' · Inactivo') + '</small></div><b>S/ ' + Number(x.p).toFixed(2) + '</b></div>';
    }).join('') : '<div class="dr-prod"><span style="color:var(--g3)">Aún no abastece productos</span></div>';
    $('dr-ver').href = d.ver;
    $('dr-id').value = d.id;
    dr.classList.remove('editando'); abrirDrawer(); $('dr-cerrar').focus();
  }

  function formulario(c) {
    var d = c ? c.dataset : {};
    $('f-titulo').textContent = c ? 'Editar proveedor' : 'Nuevo proveedor';
    $('i-id').value = c ? d.id : 0;
    $('i-ruc').value = c ? d.ruc : '';
    $('i-tel').value = c ? d.tel : '';
    $('i-razon').value = c ? d.razon : '';
    $('i-contacto').value = c ? d.contacto : '';
    $('i-correo').value = c ? d.correo : '';
    $('i-dir').value = c ? d.dir : '';
    $('i-act').value = c ? d.act : '1';
    dr.classList.add('editando');
    $(c ? 'i-razon' : 'i-ruc').focus();
  }

  cards.forEach(function (c) { c.addEventListener('click', function () { ver(c); }); });
  $('pv-nuevo').addEventListener('click', function () { ultimo = $('pv-nuevo'); actual = null; formulario(null); abrirDrawer(); });
  $('dr-editar').addEventListener('click', function () { formulario(actual); });
  $('dr-cancelar').addEventListener('click', function () {
    if (actual) { dr.classList.remove('editando'); $('dr-editar').focus(); } else { cerrar(); }
  });
  $('dr-cerrar').addEventListener('click', cerrar);
  $('dr-cerrar2').addEventListener('click', cerrar);
  fondo.addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !dr.classList.contains('abierto')) return;
    if (dr.classList.contains('editando') && actual) { dr.classList.remove('editando'); } else { cerrar(); }
  });
})();
</script>