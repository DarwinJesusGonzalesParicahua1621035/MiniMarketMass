<?php
// Mi caja (cajero): estado del turno, resumen de ventas y cierre.
// Variables que llegan desde CajeroController::caja():
//   $turno (null si la caja está cerrada), $sucursal, $user, $flash
//   $cajas   -> solo si no hay turno (cajas libres para abrir)
//   $resumen -> solo si hay turno (n, total, medios)
//   $ventas  -> solo si hay turno (ventas del turno)
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));
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
</head>
<body class="cajero">
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <nav>
    <h6>Operación</h6>
    <a href="<?= Controller::url('cajero/venta') ?>"><span>🛒</span>Nueva venta</a>
    <button type="button"><span>🧾</span>Mis ventas</button>
    <a href="<?= Controller::url('cajero/caja') ?>" class="on"><span>💵</span>Mi caja</a>
    <h6>Consulta</h6>
    <button type="button"><span>👥</span>Clientes</button>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small>
      <a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></div></div>
</aside>
<main class="micaja">
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Mi caja<small>Resumen de tu turno</small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>

  <?php if (!$turno): ?>
    <!-- ===== Caja cerrada: abrir turno ===== -->
    <form class="card formulario" method="post" action="<?= Controller::url('caja/abrir') ?>">
      <h3>🔒 Tu caja está cerrada</h3>
      <small style="color:var(--g4)">Elige una caja y el dinero con el que empiezas.</small>
      <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
      <?php if (!$cajas): ?>
        <div class="empty">Todas las cajas ya tienen un turno abierto</div>
      <?php else: ?>
        <div class="campos">
          <label>Caja
            <select name="caja_id" required>
              <?php foreach ($cajas as $id => $t): ?>
                <option value="<?= (int)$id ?>"><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Monto inicial (S/)
            <input type="number" name="monto_inicial" step="0.01" min="0" placeholder="Ej: 100.00" required>
          </label>
        </div>
        <div class="row"><button class="btn y" type="submit">Abrir caja</button></div>
      <?php endif; ?>
    </form>

  <?php else: ?>
    <!-- ===== Caja abierta: resumen del turno ===== -->
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
              <input type="number" name="monto_final" id="contado" step="0.01" min="0" required>
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
// Diferencia entre el dinero contado y lo esperado en caja.
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
<?php endif; ?>
<?php if ($turno): ?>
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