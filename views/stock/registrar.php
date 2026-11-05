<?php
// Datos que llegan desde StockController::registrar()
$old   = $old ?? [];
$motivos = $motivos ?? [];

$sucs = [];
$data = [];
foreach ($stocks as $r) {
    $sucs[$r['sucursal_id']] = $r['sucursal'];
    $data[] = [
        's'  => (int)$r['sucursal_id'],
        'p'  => (int)$r['producto_id'],
        'n'  => $r['producto'],
        'u'  => $r['unidad'],
        'c'  => (float)$r['cantidad'],
        'sn' => $r['sucursal'],
        'k'  => $r['codigo'],
        'g'  => $r['categoria'],
    ];
}
// Sucursales en el orden en que vienen (por nombre); un objeto JS reordenaría por id.
$sucsLista = [];
foreach ($sucs as $id => $nombre) { $sucsLista[] = ['id' => (string)$id, 'n' => $nombre]; }
$estado = [
    'sucursal' => (string)($old['sucursal'] ?? ''),
    'tipo'     => in_array($old['tipo'] ?? '', ['ENTRADA', 'SALIDA'], true) ? $old['tipo'] : 'ENTRADA',
    'items'    => (object)($old['items'] ?? []),
];
$json = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/stock-registrar.css">

<div class="stk">
  <div class="stk-head">
    <div>
      <h2>Nuevo movimiento de inventario</h2>
      <p>Elige la sucursal, marca todos los productos que necesites y ajusta sus cantidades en un solo registro. El stock se actualiza automáticamente y queda en el historial.</p>
    </div>
  </div>

  <form id="rgForm" method="post" action="<?= Controller::url('stock/guardar') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="sucursal" id="rgSucursal" value="">
    <input type="hidden" name="tipo" id="rgTipo" value="ENTRADA">
    <div id="rgHid"></div>

    <div class="rg">

      <!-- IZQUIERDA: dónde, qué tipo y qué productos -->
      <section class="rg-card">
        <div class="rg-sec">
          <div class="rg-lab"><i>1</i> Sucursal</div>
          <div class="rg-chips" id="rgSucs"></div>
        </div>

        <div class="rg-sec">
          <div class="rg-lab"><i>2</i> Tipo de movimiento <small>(aplica a todos los productos)</small></div>
          <div class="rg-seg" id="rgSeg">
            <button type="button" class="entrada on" data-t="ENTRADA">＋ Entrada</button>
            <button type="button" class="salida" data-t="SALIDA">－ Salida</button>
          </div>
        </div>

        <div class="rg-sec">
          <div class="rg-lab"><i>3</i> Productos <small id="rgCuenta"></small></div>
          <div class="rg-cats" id="rgCats"></div>
          <div class="rg-buscar"><span>🔍</span><input id="rgQ" placeholder="Buscar por nombre o código…" autocomplete="off"></div>
          <div class="rg-lista" id="rgLista"></div>
          <div class="rg-tip">Filtra por categoría o busca, y toca un producto para agregarlo o quitarlo. Con <b>Enter</b> agregas el primer resultado (sirve con lector de código). Solo salen los productos asignados a esa sucursal (Productos → Editar).</div>
        </div>
      </section>

      <!-- DERECHA: líneas del movimiento -->
      <section class="rg-card">
        <div class="rg-panel-h">
          <div style="display:flex;align-items:center;gap:10px"><h3>Productos del movimiento</h3><span class="rg-badge" id="rgN">0</span></div>
          <button type="button" class="rg-limpiar" id="rgLimpiar" hidden>Quitar todos</button>
        </div>

        <div class="rg-lines" id="rgLines"></div>

        <div class="rg-sec" style="margin-top:18px">
          <div class="rg-lab"><i>4</i> Motivo <small>(opcional)</small></div>
          <div class="rg-mot">
            <div class="rg-sug" id="rgSug"></div>
            <input class="rg-txt" id="rgMotivo" name="motivo" maxlength="100" autocomplete="off"
                   value="<?= e($old['motivo'] ?? '') ?>" placeholder="Escribe el motivo…">
          </div>
        </div>

        <div class="rg-total"><span id="rgTotTxt">Sin productos</span><b id="rgTotNum"></b></div>
        <div class="rg-btns">
          <a class="btn g" href="<?= Controller::url('stock/index') ?>">Cancelar</a>
          <button class="btn y" type="submit" id="rgOk" disabled>Registrar movimiento</button>
        </div>
        <div class="rg-hint" id="rgHint"></div>
      </section>

    </div>
  </form>
</div>

<script>
(function () {
  var D      = <?= json_encode($data, $json) ?>;                 // productos por sucursal
  var SUCS   = <?= json_encode((object)$sucs, $json) ?>;          // id => nombre
  var SUCS_LISTA = <?= json_encode($sucsLista, $json) ?>;         // [{id, n}] en orden alfabético
  var MOTIVOS = <?= json_encode(array_values($motivos), $json) ?>; // motivos usados antes (más reciente primero)
  var OLD    = <?= json_encode($estado, $json) ?>;
  var PRE_SUC = <?= json_encode((string)($_GET['suc'] ?? ''), $json) ?>;   // enlaces desde Stock: ?suc=Nombre&cod=Código
  var PRE_COD = <?= json_encode((string)($_GET['cod'] ?? ''), $json) ?>;
  var BAJO = 10;

  var g = function (id) { return document.getElementById(id); };
  var st = { suc: '', tipo: 'ENTRADA', cat: '', lines: [] };   // lines: [{r: fila, q: cantidad}]

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  function fmt(n) { return String(Math.round(n * 1000) / 1000); }
  function est(n) { return n <= 0 ? 'sin' : n < BAJO ? 'bajo' : 'ok'; }
  function signo() { return st.tipo === 'ENTRADA' ? 1 : -1; }
  function plural(n, a, b) { return n + ' ' + (n === 1 ? a : b); }
  function filas() { return D.filter(function (r) { return String(r.s) === st.suc; }); }
  function enLineas(r) { return st.lines.some(function (l) { return l.r === r; }); }

  /* ---------- Sucursales ---------- */
  function pintarSucs() {
    g('rgSucs').innerHTML = SUCS_LISTA.map(function (s) {
      return '<button type="button" class="rg-chip' + (st.suc === s.id ? ' on' : '') + '" data-s="' + esc(s.id) + '">' + esc(s.n) + '</button>';
    }).join('');
  }

  /* ---------- Categorías + lista de productos ---------- */
  function filtrados() {
    var t = g('rgQ').value.trim().toLowerCase();
    return filas().filter(function (r) {
      return (!st.cat || r.g === st.cat) && (!t || (r.n + ' ' + r.k).toLowerCase().indexOf(t) > -1);
    });
  }
  function pintarCats() {
    var cuenta = {};
    filas().forEach(function (r) { cuenta[r.g] = (cuenta[r.g] || 0) + 1; });
    var nombres = Object.keys(cuenta).sort(function (a, b) { return a.localeCompare(b, 'es'); });
    if (st.cat && !cuenta[st.cat]) st.cat = '';
    g('rgCats').innerHTML = '<button type="button" class="rg-cat' + (!st.cat ? ' on' : '') + '" data-g="">Todas</button>' +
      nombres.map(function (n) {
        return '<button type="button" class="rg-cat' + (st.cat === n ? ' on' : '') + '" data-g="' + esc(n) + '">' + esc(n) + '<small>' + cuenta[n] + '</small></button>';
      }).join('');
  }
  function pintarLista() {
    if (!st.suc) {
      g('rgLista').innerHTML = '<div class="rg-vacio">Primero elige una sucursal</div>';
      g('rgCuenta').textContent = ''; g('rgCats').innerHTML = ''; return;
    }
    pintarCats();
    var items = filtrados();
    g('rgCuenta').textContent = '· ' + items.length + ' en esta sucursal';
    g('rgLista').innerHTML = items.length ? items.map(function (r) {
      return '<button type="button" class="rg-prod' + (enLineas(r) ? ' on' : '') + '" data-p="' + r.p + '">' +
        '<span><b>' + esc(r.n) + '</b><small>' + esc(r.k) + '</small></span>' +
        '<span class="rg-der"><span class="rg-pill ' + est(r.c) + '">' + fmt(r.c) + ' ' + esc(r.u) + '</span><span class="rg-chk">✓</span></span></button>';
    }).join('') : '<div class="rg-vacio">Ningún producto coincide con el filtro.</div>';
  }

  /* ---------- Líneas del movimiento ---------- */
  function alternar(r) {
    var k = -1;
    st.lines.forEach(function (l, i) { if (l.r === r) k = i; });
    if (k > -1) st.lines.splice(k, 1); else st.lines.push({ r: r, q: 1 });
    pintarLista(); pintarLineas();
    if (k === -1) {
      var inp = g('rgLines').querySelector('[data-k="' + (st.lines.length - 1) + '"] input');
      if (inp) { inp.focus(); inp.select(); }
    }
  }
  function pintarLineas() {
    var n = st.lines.length;
    g('rgN').textContent = n;
    g('rgLimpiar').hidden = n < 2;
    if (!n) {
      g('rgLines').innerHTML = '<div class="rg-vacio-l"><b>📦</b>Aún no hay productos.<br>Elige una sucursal y toca los productos de la izquierda.</div>';
      resumen(); return;
    }
    g('rgLines').innerHTML = st.lines.map(function (l, i) {
      return '<div class="rg-ln" data-k="' + i + '">' +
        '<div class="rg-ln-top"><div><b>' + esc(l.r.n) + '</b><small>' + esc(l.r.k) + '</small></div>' +
        '<button type="button" class="rg-x" data-del aria-label="Quitar">×</button></div>' +
        '<div class="rg-ln-bot">' +
          '<div class="rg-step"><button type="button" data-menos aria-label="Restar">−</button>' +
          '<input data-q inputmode="numeric" maxlength="7" value="' + (l.q || '') + '" placeholder="0">' +
          '<button type="button" data-mas aria-label="Sumar">+</button><em>' + esc(l.r.u) + '</em></div>' +
          '<div class="rg-flow"><span>' + fmt(l.r.c) + '</span><i>→</i><b class="rg-nv"></b></div>' +
        '</div><div class="rg-ln-err"></div></div>';
    }).join('');
    st.lines.forEach(function (l, i) { pintarLinea(i); });
    resumen();
  }
  function pintarLinea(i) {
    var l = st.lines[i], row = g('rgLines').querySelector('[data-k="' + i + '"]'); if (!row) return;
    var nuevo = Math.round((l.r.c + signo() * l.q) * 1000) / 1000;
    row.className = 'rg-ln';
    row.querySelector('.rg-nv').textContent = l.q > 0 ? fmt(nuevo) + ' ' + l.r.u : '—';
    var err = row.querySelector('.rg-ln-err');
    if (l.q > 0) {
      if (nuevo < 0) { row.classList.add('mal'); err.textContent = 'Stock insuficiente: solo hay ' + fmt(l.r.c) + ' ' + l.r.u + '.'; }
      else row.classList.add(signo() > 0 ? 'sube' : 'baja');
    }
  }
  function resumen() {
    var n = st.lines.length, unidades = 0, sinCant = 0, mal = 0, hid = '';
    st.lines.forEach(function (l) {
      unidades += l.q;
      if (!(l.q > 0)) sinCant++;
      else if (l.r.c + signo() * l.q < 0) mal++;
      hid += '<input type="hidden" name="items[' + l.r.p + ']" value="' + l.q + '">';
    });
    g('rgHid').innerHTML = hid;
    g('rgSucursal').value = st.suc;
    g('rgTipo').value = st.tipo;
    g('rgTotTxt').textContent = n ? plural(n, 'producto', 'productos') + ' · ' + (st.tipo === 'ENTRADA' ? 'Entrada' : 'Salida') + ' en ' + SUCS[st.suc] : 'Sin productos';
    g('rgTotNum').textContent = n ? (signo() > 0 ? '+' : '−') + unidades + ' u.' : '';
    var ok = n > 0 && !sinCant && !mal;
    g('rgOk').disabled = !ok;
    g('rgHint').textContent = !n ? '' : mal ? 'Corrige los productos con stock insuficiente.' : sinCant ? 'Indica la cantidad de cada producto.' : '';
  }

  /* ---------- Eventos ---------- */
  g('rgSucs').addEventListener('click', function (e) {
    var b = e.target.closest('[data-s]'); if (!b || st.suc === b.dataset.s) return;
    st.suc = b.dataset.s; st.lines = []; st.cat = ''; g('rgQ').value = '';
    pintarSucs(); pintarLista(); pintarLineas();
  });
  g('rgCats').addEventListener('click', function (e) {
    var b = e.target.closest('[data-g]'); if (!b) return; st.cat = b.dataset.g; pintarLista();
  });
  g('rgLista').addEventListener('click', function (e) {
    var b = e.target.closest('[data-p]'); if (!b) return;
    var r = filas().filter(function (x) { return String(x.p) === b.dataset.p; })[0]; if (r) alternar(r);
  });
  g('rgQ').addEventListener('input', pintarLista);
  g('rgQ').addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return; e.preventDefault();
    var f = filtrados(); if (!f.length) return;
    if (!enLineas(f[0])) alternar(f[0]);
    g('rgQ').value = ''; pintarLista(); g('rgQ').focus();
  });
  g('rgSeg').addEventListener('click', function (e) {
    var b = e.target.closest('[data-t]'); if (!b) return;
    st.tipo = b.dataset.t;
    g('rgSeg').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    st.lines.forEach(function (l, i) { pintarLinea(i); }); resumen();
  });
  g('rgLines').addEventListener('input', function (e) {
    var inp = e.target.closest('[data-q]'); if (!inp) return;
    inp.value = inp.value.replace(/[^0-9]/g, '').replace(/^0+(?=\d)/, '');
    var i = +inp.closest('.rg-ln').dataset.k; st.lines[i].q = parseInt(inp.value, 10) || 0;
    pintarLinea(i); resumen();
  });
  g('rgLines').addEventListener('click', function (e) {
    var row = e.target.closest('.rg-ln'); if (!row) return; var i = +row.dataset.k, l = st.lines[i];
    if (e.target.closest('[data-del]')) { st.lines.splice(i, 1); pintarLista(); pintarLineas(); return; }
    var d = e.target.closest('[data-mas]') ? 1 : e.target.closest('[data-menos]') ? -1 : 0; if (!d) return;
    l.q = Math.max(0, l.q + d); row.querySelector('[data-q]').value = l.q || '';
    pintarLinea(i); resumen();
  });
  g('rgLimpiar').addEventListener('click', function () { st.lines = []; pintarLista(); pintarLineas(); });
  // Enter dentro de un campo no envía el formulario por accidente.
  g('rgForm').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') e.preventDefault();
  });
  g('rgForm').addEventListener('submit', function (e) { if (g('rgOk').disabled) e.preventDefault(); });

  /* ---------- Motivo con sugerencias (historial real de la base de datos) ---------- */
  var hi = -1, sug = [];
  function resaltar(txt, t) {
    if (!t) return esc(txt);
    var i = txt.toLowerCase().indexOf(t.toLowerCase()); if (i < 0) return esc(txt);
    return esc(txt.slice(0, i)) + '<mark>' + esc(txt.slice(i, i + t.length)) + '</mark>' + esc(txt.slice(i + t.length));
  }
  function pintarSug() {
    var v = g('rgMotivo').value.trim(), t = v.toLowerCase();
    sug = MOTIVOS.filter(function (m) { var l = m.toLowerCase(); return l !== t && (!t || l.indexOf(t) > -1); }).slice(0, 6);
    hi = -1;
    if (!sug.length) { g('rgSug').classList.remove('open'); return; }
    g('rgSug').innerHTML = '<h6>Usados antes</h6>' + sug.map(function (m, i) {
      return '<div class="rg-sug-i" data-j="' + i + '">' + resaltar(m, v) + '</div>';
    }).join('');
    g('rgSug').classList.add('open');
  }
  function marcar() { g('rgSug').querySelectorAll('.rg-sug-i').forEach(function (el, i) { el.classList.toggle('hi', i === hi); }); }
  function elegir(j) { g('rgMotivo').value = sug[j]; g('rgSug').classList.remove('open'); g('rgMotivo').focus(); }
  g('rgMotivo').addEventListener('focus', pintarSug);
  g('rgMotivo').addEventListener('input', pintarSug);
  g('rgMotivo').addEventListener('blur', function () { setTimeout(function () { g('rgSug').classList.remove('open'); }, 120); });
  g('rgMotivo').addEventListener('keydown', function (e) {
    var abierto = g('rgSug').classList.contains('open');
    if (e.key === 'ArrowDown' && abierto) { e.preventDefault(); hi = (hi + 1) % sug.length; marcar(); }
    else if (e.key === 'ArrowUp' && abierto) { e.preventDefault(); hi = (hi - 1 + sug.length) % sug.length; marcar(); }
    else if (e.key === 'Enter') { e.preventDefault(); if (abierto && hi > -1) elegir(hi); else g('rgSug').classList.remove('open'); }
    else if (e.key === 'Escape') g('rgSug').classList.remove('open');
  });
  g('rgSug').addEventListener('mousedown', function (e) {
    e.preventDefault();
    var it = e.target.closest('.rg-sug-i'); if (it) elegir(+it.dataset.j);
  });

  /* ---------- Estado inicial (error previo o enlace desde Stock) ---------- */
  st.tipo = OLD.tipo || 'ENTRADA';
  g('rgSeg').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x.dataset.t === st.tipo); });
  if (OLD.sucursal && SUCS[OLD.sucursal]) {
    st.suc = OLD.sucursal;
    filas().forEach(function (r) {
      var q = OLD.items[r.p];
      if (q !== undefined) st.lines.push({ r: r, q: parseInt(q, 10) || 0 });
    });
  } else if (PRE_SUC && PRE_COD) {
    var pre = D.filter(function (r) { return r.sn === PRE_SUC && r.k === PRE_COD; })[0];
    if (pre) { st.suc = String(pre.s); st.lines.push({ r: pre, q: 1 }); }
  }
  pintarSucs(); pintarLista(); pintarLineas();
})();
</script>
