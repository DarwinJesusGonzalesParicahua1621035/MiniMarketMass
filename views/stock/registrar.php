<?php
$old = $old ?? [];
[$oldS, $oldP] = array_pad(explode('-', (string)($old['stock'] ?? ''), 2), 2, '');
$tipoSel = $old['tipo'] ?? 'ENTRADA';
$tipos = [
    'ENTRADA' => ['t-entrada', 'Entrada', 'Suma · llegada de mercadería',      '+'],
    'SALIDA'  => ['t-salida',  'Salida',  'Resta · retiro de stock',           '-'],
    'AJUSTE'  => ['t-ajuste',  'Ajuste',  'Suma · corrección de inventario',   '+'],
    'MERMA'   => ['t-merma',   'Merma',   'Resta · producto dañado o vencido', '-'],
];
$sucs = [];
$data = [];
foreach ($stocks as $r) {
    $sucs[$r['sucursal_id']] = $r['sucursal'];
    $data[] = [
        's' => (int)$r['sucursal_id'],
        'p' => (int)$r['producto_id'],
        'n' => $r['producto'],
        'u' => $r['unidad'],
        'c' => (float)$r['cantidad'],
        'sn' => $r['sucursal'],
        'k' => $r['codigo'],
    ];
}
$signos = array_map(fn($t) => $t[3] === '+' ? 1 : -1, $tipos);
?>
<div class="stk">
  <div class="stk-head">
    <div>
       <h2>Nuevo movimiento de inventario</h2>
      <p>Completa los 5 pasos. El stock se actualiza y queda constancia en el historial.</p>
    </div>
  </div>

  <div class="reg">

    <form class="reg-card" method="post" action="<?= Controller::url('stock/guardar') ?>">
      <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
      <input type="hidden" name="stock" id="regStock" value="">

      <div class="reg-paso">
        <div class="reg-n">1</div>
        <div class="reg-c">
          <label class="t" for="regSuc">Sucursal</label>
          <small class="h">¿Dónde se hace el movimiento?</small>
          <select id="regSuc" required>
            <option value="">Elige una sucursal</option>
            <?php foreach ($sucs as $id => $nombre): ?>
              <option value="<?= (int)$id ?>"><?= e($nombre) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="reg-paso">
        <div class="reg-n">2</div>
        <div class="reg-c">
          <label class="t" for="regProd">Producto</label>
          <small class="h">Solo salen los productos de la sucursal elegida.</small>
          <select id="regProd" required disabled>
            <option value="">Primero elige la sucursal</option>
          </select>
        </div>
      </div>

      <div class="reg-paso">
        <div class="reg-n">3</div>
        <div class="reg-c">
          <span class="t">Tipo de movimiento</span>
          <small class="h">¿Qué pasó con el producto?</small>
          <div class="reg-tipos">
            <?php foreach ($tipos as $clave => [$cls, $tit, $sub]): ?>
              <label class="reg-tipo <?= $cls ?>">
                <input type="radio" name="tipo" value="<?= $clave ?>" <?= $clave === $tipoSel ? 'checked' : '' ?>>
                <span><b><?= e($tit) ?></b><small><?= e($sub) ?></small></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="reg-paso">
        <div class="reg-n">4</div>
        <div class="reg-c">
          <label class="t" for="regCant">Cantidad</label>
          <small class="h">Cuántas unidades entran o salen.</small>
          <div class="reg-cant">
            <input type="number" id="regCant" name="cantidad" step="0.001" min="0.001"
                   value="<?= e($old['cantidad'] ?? '') ?>" placeholder="0" required>
            <em id="regUni"></em>
          </div>
        </div>
      </div>

      <div class="reg-paso">
        <div class="reg-n">5</div>
        <div class="reg-c">
          <label class="t" for="regMotivo">Motivo</label>
          <small class="h">Opcional. Ej: Reposición, Inventario, Envase dañado.</small>
          <input type="text" id="regMotivo" name="motivo" maxlength="100"
                 value="<?= e($old['motivo'] ?? '') ?>" placeholder="Escribe el motivo">
        </div>
      </div>

      <div class="reg-btns">
        <a class="btn g" href="<?= Controller::url('stock/index') ?>">Cancelar</a>
        <button class="btn y" type="submit" id="regBtn">Registrar movimiento</button>
      </div>
    </form>

    <aside class="reg-res">
      <div class="reg-res-top">
        <small>Stock actual</small>
        <b id="sumActual">—</b>
      </div>
      <div class="reg-res-body">
        <div class="reg-fila"><span>Sucursal</span><b id="sumSuc">—</b></div>
        <div class="reg-fila"><span>Producto</span><b id="sumProd">—</b></div>
        <div class="reg-fila"><span>Movimiento</span><b id="sumMov">—</b></div>
        <div class="reg-queda" id="sumQueda">
          <small id="sumAviso">Así quedará el stock</small>
          <b id="sumQuedaVal">—</b>
        </div>
      </div>
    </aside>

  </div>
</div>

<script>
(function () {
  var D = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var SIGNO = <?= json_encode($signos) ?>;
  var oldS = <?= json_encode((string)$oldS) ?>, oldP = <?= json_encode((string)$oldP) ?>;
  var g = function (id) { return document.getElementById(id); };
  var suc = g('regSuc'), prod = g('regProd'), hid = g('regStock'), cant = g('regCant'), btn = g('regBtn');

  function fmt(n) { return String(Math.round(n * 1000) / 1000); }

  function llenar() {
    prod.innerHTML = '';
    var o = document.createElement('option');
    o.value = '';
    o.textContent = suc.value ? 'Elige un producto' : 'Primero elige la sucursal';
    prod.appendChild(o);
    D.filter(function (r) { return String(r.s) === suc.value; }).forEach(function (r) {
      var x = document.createElement('option');
      x.value = r.p;
      x.textContent = r.n;
      prod.appendChild(x);
    });
    prod.disabled = !suc.value;
  }

  function fila() {
    return D.find(function (r) { return String(r.s) === suc.value && String(r.p) === prod.value; }) || null;
  }

  function actualizar() {
    var r = fila();
    hid.value = r ? r.s + '-' + r.p : '';
    g('sumSuc').textContent = suc.value ? suc.options[suc.selectedIndex].textContent : '—';
    g('sumProd').textContent = r ? r.n : '—';
    g('sumActual').textContent = r ? fmt(r.c) + ' ' + r.u : '—';
    g('regUni').textContent = r ? r.u : '';

    var t = document.querySelector('input[name=tipo]:checked').value;
    g('sumMov').textContent = t.charAt(0) + t.slice(1).toLowerCase();

    var q = parseFloat(cant.value), box = g('sumQueda');
    box.classList.remove('bad');
    btn.disabled = false;
    g('sumAviso').textContent = 'Así quedará el stock';
    g('sumQuedaVal').textContent = '—';
    if (r && q > 0) {
      var n = Math.round((r.c + SIGNO[t] * q) * 1000) / 1000;
      g('sumQuedaVal').textContent = fmt(n) + ' ' + r.u;
      if (n < 0) {
        box.classList.add('bad');
        btn.disabled = true;
        g('sumAviso').textContent = 'Stock insuficiente';
      }
    }
  }

  suc.addEventListener('change', function () { llenar(); actualizar(); });
  prod.addEventListener('change', actualizar);
  cant.addEventListener('input', actualizar);
  document.querySelectorAll('input[name=tipo]').forEach(function (i) { i.addEventListener('change', actualizar); });

  var preSuc = <?= json_encode((string)($_GET['suc'] ?? '')) ?>;
  var preCod = <?= json_encode((string)($_GET['cod'] ?? '')) ?>;
  if (!oldS && preSuc && preCod) {
    var pre = D.find(function (r) { return r.sn === preSuc && r.k === preCod; });
    if (pre) { oldS = String(pre.s); oldP = String(pre.p); }
  }
  if (oldS) { suc.value = oldS; }
  llenar();
  if (oldP) { prod.value = oldP; }
  actualizar();
})();
</script>