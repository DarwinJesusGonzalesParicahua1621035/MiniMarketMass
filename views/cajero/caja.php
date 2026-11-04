<?php
// Mi caja (cajero): estado del turno, resumen de ventas y cierre.
// Variables que llegan desde CajeroController::caja():
//   $turno (null si la caja está cerrada), $sucursal, $user, $flash
//   $cajas   -> solo si no hay turno (cajas libres para abrir)
//   $resumen -> solo si hay turno (n, total, medios)
//   $ventas  -> solo si hay turno (ventas del turno)
$efectivo = $turno ? (float)($resumen['medios']['EFECTIVO'] ?? 0) : 0;
$esperado = $turno ? (float)$turno['monto_inicial'] + $efectivo : 0;
$medios = [['EFECTIVO', '💵', 'Efectivo', '#2F6BFF'], ['PLIN', '📲', 'Plin', '#22c3a0'],
           ['YAPE', '💜', 'Yape', '#7c5cff'], ['TARJETA', '💳', 'Tarjeta', '#FFB300']];
$cobrado = $turno ? (array_sum(array_map('floatval', $resumen['medios'])) ?: 1) : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi caja · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
<link rel="stylesheet" href="<?= Controller::base() ?>/css/cajero-abrir.css">
</head>
<body class="cajero">
<?php $activo = 'caja'; require __DIR__ . '/_nav.php'; ?>
<?php
/* Etapa del turno: 0 = sin asistencia · 1 = asistencia lista · 2 = caja abierta */
$fase = $turno ? 2 : (!empty($asistencia) ? 1 : 0);
$h = (int)date('G');
$saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre1 = explode(' ', trim($user['nombre']))[0];
?>
<main class="micaja">
  <header>
    <button id="menu" type="button">☰</button>
    <?php if ($fase === 2): ?>
      <h1>Mi caja<small>Resumen de tu turno</small></h1>
    <?php elseif ($fase === 0): ?>
      <h1><?= e($saludo) ?>, <?= e($nombre1) ?><small>Sigue estos pasos para empezar tu turno</small></h1>
    <?php else: ?>
      <h1>Abrir caja<small>Elige tu caja y cuenta el efectivo con el que empiezas</small></h1>
    <?php endif; ?>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>

  <?php if (!$turno): ?>

    <!-- Pasos del turno -->
    <div class="steps">
      <?php foreach ([[1, 'Asistencia'], [2, 'Abrir caja'], [3, 'Operar']] as $i => [$n, $t]):
          $cls = $fase > $i ? 'ok' : ($fase === $i ? 'on' : ''); ?>
        <div class="step <?= $cls ?>"><i><?= $fase > $i ? '✓' : $n ?></i><?= e($t) ?></div>
      <?php endforeach; ?>
    </div>

    <?php if ($fase === 0): ?>

      <!-- ETAPA 1: registrar asistencia -->
      <form class="card asist" method="post" action="<?= Controller::url('cajero/asistencia') ?>">
        <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
        <div class="big">🕘</div>
        <div>
          <h2>Registra tu asistencia</h2>
          <p>Marca tu entrada para poder abrir caja.</p>
        </div>
        <button class="btn y" type="submit">Registrar asistencia</button>
      </form>

    <?php else:
        $libres = array_filter($cajas, fn($c) => !$c['ocupada']);
        $hora   = date('H:i', strtotime($asistencia['entrada']));
    ?>

      <!-- ETAPA 2: elegir caja y abrir -->
      <div class="chip-ok">✓ Entrada registrada a las <?= e($hora) ?> · <?= e(ucfirst(mb_strtolower($asistencia['estado']))) ?></div>

      <form class="card formulario cj-form" method="post" action="<?= Controller::url('caja/abrir') ?>" style="max-width:780px">
        <h3>Datos de apertura</h3>
        <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">

        <?php if (!$cajas): ?>
          <div class="empty">Tu sucursal no tiene cajas registradas</div>
        <?php else: ?>

          <p class="cj-sub">1 · Elige tu caja</p>
          <div class="cj-grid" role="radiogroup" aria-label="Caja">
            <?php foreach ($cajas as $c): $uso = (bool)$c['ocupada']; ?>
              <label class="cj-item <?= $uso ? 'uso' : '' ?>">
                <input type="radio" name="caja_id" value="<?= (int)$c['id'] ?>" data-n="<?= e($c['nombre']) ?>"
                       <?= $uso ? 'disabled' : 'required' ?>>
                <span class="cj-card">
                  <span class="cj-ico">🖥️</span>
                  <b><?= e($c['nombre']) ?></b>
                  <small><?= e($c['sucursal']) ?></small>
                  <em class="cj-est"><?= $uso ? 'En uso · ' . e($c['responsable']) : 'Libre' ?></em>
                  <span class="cj-ck">✓</span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>

          <?php if (!$libres): ?>
            <div class="empty" style="margin-top:14px">Todas las cajas ya tienen un turno abierto</div>
          <?php else: ?>

            <p class="cj-sub">2 · Monto inicial</p>
            <div class="campos cj-monto">
              <label>Efectivo con el que empiezas (S/)
                <input type="number" name="monto_inicial" id="cj-monto" step="0.01" min="0" value="100.00" placeholder="0.00" required>
              </label>
            </div>
            <div class="cj-montos">
              <?php foreach ([50, 100, 200, 300] as $v): ?>
                <button type="button" class="cj-mchip" data-v="<?= $v ?>.00">S/ <?= $v ?></button>
              <?php endforeach; ?>
            </div>

            <div class="cj-resumen" id="cj-res"></div>
            <div class="row"><button class="btn y" type="submit">Abrir caja</button></div>

          <?php endif; ?>
        <?php endif; ?>
      </form>

      <script>
      (function () {
        var res = document.getElementById('cj-res');
        if (!res) return;
        var monto = document.getElementById('cj-monto'),
            radios = [].slice.call(document.querySelectorAll('.cj-form input[name=caja_id]')),
            chips = [].slice.call(document.querySelectorAll('.cj-mchip'));

        function resumen() {
          var m = monto.value.trim().replace(',', '.'),
              ok = /^\d+(\.\d{1,2})?$/.test(m),
              sel = radios.filter(function (r) { return r.checked; })[0];
          chips.forEach(function (c) { c.classList.toggle('on', ok && parseFloat(c.dataset.v) === parseFloat(m)); });
          res.innerHTML = !sel ? 'Elige una caja para continuar.'
            : 'Abrirás <b>' + sel.dataset.n + '</b> con <b>' + (ok ? 'S/ ' + Number(m).toFixed(2) : 'S/ —') + '</b>.';
        }

        radios.forEach(function (r) { r.addEventListener('change', resumen); });
        chips.forEach(function (b) { b.addEventListener('click', function () { monto.value = b.dataset.v; resumen(); }); });
        monto.addEventListener('input', resumen);
        resumen();
      })();
      </script>

    <?php endif; ?>

  <?php else: ?>
    <div class="hero">
      <div>
        <h2><?= e($turno['caja']) ?> · <?= e($turno['sucursal']) ?></h2>
        <p>Abierta desde las <?= e(date('H:i', strtotime($turno['apertura']))) ?></p>
      </div>
      <span>Turno abierto</span>
    </div>

    <div class="kp-grid">
      <div class="kp g"><div class="hd"><i>💰</i><p>Monto inicial</p></div>
        <h2><?= money($turno['monto_inicial']) ?></h2><em class="n">Con lo que abriste</em></div>
      <div class="kp y"><div class="hd"><i>🧾</i><p>Ventas del turno</p></div>
        <h2><?= money($resumen['total']) ?></h2><em><?= (int)$resumen['n'] ?> comprobantes</em></div>
      <div class="kp b"><div class="hd"><i>💵</i><p>Efectivo cobrado</p></div>
        <h2><?= money($efectivo) ?></h2><em class="n">Ya sin el vuelto</em></div>
      <div class="kp p"><div class="hd"><i>🏦</i><p>Esperado en caja</p></div>
        <h2><?= money($esperado) ?></h2><em class="n">Inicial + efectivo</em></div>
    </div>

    <div class="pos">
      <div class="tw">
        <div class="tools"><h3><i class="ti">🧾</i>Ventas del turno <span class="tag b"><?= count($ventas) ?></span></h3></div>
        <div class="scroll">
          <table>
            <thead>
              <tr><th>Comprobante</th><th>Hora</th><th>Cliente</th><th style="text-align:right">Total</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($ventas as $v): ?>
              <tr>
                <td><b><?= e($v['comprobante']) ?></b></td>
                <td><?= e(date('H:i', strtotime($v['fecha']))) ?></td>
                <td><?= e($v['cliente']) ?></td>
                <td style="text-align:right"><?= money($v['total']) ?></td>
                <td><?= celda($v['estado'], 'etiqueta') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (!$ventas): ?><div class="empty">Todavía no hay ventas en este turno</div><?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card" style="margin-bottom:16px">
          <h3 style="font-size:16px"><i class="ti">💳</i>Por medio de pago</h3>
          <?php foreach ($medios as [$clave, $ico, $nombre, $color]):
              $monto = (float)($resumen['medios'][$clave] ?? 0);
              $pct = round($monto / $cobrado * 100); ?>
            <div class="mp-row">
              <div class="mp-top">
                <u style="background:<?= $color ?>22"><?= $ico ?></u>
                <span><?= e($nombre) ?></span>
                <b><?= money($monto) ?></b>
              </div>
              <i><s style="width:<?= $pct ?>%;background:<?= $color ?>"></s></i>
            </div>
          <?php endforeach; ?>
        </div>

        <form class="card formulario" id="formCerrar" method="post" action="<?= Controller::url('caja/cerrar') ?>">
          <h3>Cerrar caja</h3>
          <small style="color:var(--g4)">Cuenta el dinero y escribe el total.</small>
          <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
          <input type="hidden" name="historia_id" value="<?= (int)$turno['id'] ?>">
          <div class="campos">
            <label>Dinero contado (S/)
                <input type="number" name="contado" id="contado" step="0.01" min="0" required>
            </label>
          </div>
          <p style="margin-top:12px;font-size:13px">Esperado: <b><?= money($esperado) ?></b> <span id="dif"></span></p>
          <div class="row"><button class="btn y" type="submit">Cerrar caja</button></div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</main>

<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);
})();
</script>
<?php if ($turno): ?>
<script>
(function () {
  var esperado = <?= json_encode($esperado) ?>;
  var inp = document.getElementById('contado'), dif = document.getElementById('dif');
  inp.addEventListener('input', function () {
    if (inp.value === '') { dif.textContent = ''; dif.className = ''; return; }
    var d = Math.round((parseFloat(inp.value) - esperado) * 100) / 100;
    dif.textContent = d === 0 ? 'Cuadra exacto ✔' : (d > 0 ? 'Sobra S/ ' : 'Falta S/ ') + Math.abs(d).toFixed(2);
    dif.className = 'tag ' + (d === 0 ? 'ok' : 'bad');
  });
})();
</script>
<div class="mc-modal" id="mcModal">
  <div class="mc-box">
    <i>🔒</i>
    <h3>¿Cerrar tu caja?</h3>
    <p>No podrás vender hasta abrirla otra vez.</p>
    <div class="mc-btns">
      <button type="button" class="mc-no" id="mcNo">Cancelar</button>
      <button type="button" class="mc-si" id="mcSi">Sí, cerrar caja</button>
    </div>
  </div>
</div>
<script>
(function () {
  var f = document.getElementById('formCerrar');
  var m = document.getElementById('mcModal');
  f.addEventListener('submit', function (e) { e.preventDefault(); m.classList.add('open'); });
  document.getElementById('mcNo').onclick = function () { m.classList.remove('open'); };
  document.getElementById('mcSi').onclick = function () { f.submit(); };
  m.addEventListener('click', function (e) { if (e.target === m) m.classList.remove('open'); });
})();
</script>
<?php endif; ?>
</body>
</html>