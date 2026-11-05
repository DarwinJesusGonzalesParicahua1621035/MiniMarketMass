<?php
// Panel del cajero: asistencia, apertura de caja, resumen del turno y cierre.
// Variables que llegan desde CajeroController::caja():
//   $turno (null si la caja está cerrada), $sucursal, $user, $flash
//   $cajas   -> solo si no hay turno (cajas libres para abrir)
//   $resumen -> solo si hay turno (n, total, medios)
//   $ventas  -> solo si hay turno (ventas del turno)
$efectivo = $turno ? (float)($resumen['medios']['EFECTIVO'] ?? 0) : 0;
$esperado = $turno ? (float)$turno['monto_inicial'] + $efectivo : 0;

/* Etapa del turno: 0 = sin asistencia · 1 = falta abrir caja · 2 = caja abierta · 3 = turno terminado */
$fase = $turno ? 2 : (!empty($asistencia) ? (!empty($asistencia['salida']) ? 3 : 1) : 0);
$h = (int)date('G');
$saludo = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre1 = explode(' ', trim($user['nombre']))[0];
$sucursalChip = preg_replace('/^Mass\s+/i', 'Sucursal ', (string)$sucursal);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Panel del cajero · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/cajero-panel.css?v=4">
</head>
<body class="cajero">
<?php $activo = 'caja'; require __DIR__ . '/_nav.php'; ?>
<main>
  <header>
    <h1 id="ttl">
      <?php if ($fase === 2): ?>Mi caja<small>Resumen de tu turno</small>
      <?php elseif ($fase === 3): ?>Turno terminado<small>Gracias por tu trabajo de hoy</small>
      <?php elseif ($fase === 0): ?><?= e($saludo) ?>, <?= e($nombre1) ?><small>Sigue estos pasos para empezar tu turno</small>
      <?php else: ?>Abrir caja<small>Elige tu caja y cuenta el efectivo con el que empiezas</small>
      <?php endif; ?>
    </h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursalChip) ?></span><?php endif; ?>
    <span class="chip live" id="cajaChip"<?= $turno ? '' : ' hidden' ?>><?= $turno ? e($turno['caja']) . ' · abierta' : '' ?></span>
    <span class="chip" id="clock">🕘</span>
  </header>

  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>

  <div id="v">
  <?php if ($fase === 3):
      $ent = strtotime($asistencia['entrada']);
      $sal = strtotime($asistencia['salida']);
      $min = max(0, (int)round(($sal - $ent) / 60));
  ?>

    <div class="card hero" style="max-width:640px">
      <div class="big">👋</div>
      <div><h2>¡Hasta mañana, <?= e($nombre1) ?>!</h2>
        <p>Tu salida quedó registrada. Ya no puedes registrar otra entrada hoy.</p></div>
    </div>
    <div class="card" style="max-width:640px;margin-top:14px">
      <div class="resumen">
        <div><small>Entrada</small><b><?= e(date('H:i', $ent)) ?></b></div>
        <div><small>Salida</small><b><?= e(date('H:i', $sal)) ?></b></div>
        <div><small>Tiempo trabajado</small><b><?= intdiv($min, 60) ?> h <?= $min % 60 ?> min</b></div>
      </div>
      <a class="btn y" href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a>
    </div>

  <?php elseif ($fase === 0): ?>

    <div class="steps">
      <?php foreach (['Asistencia', 'Abrir caja', 'Operar'] as $i => $t): ?>
        <div class="step <?= $fase > $i ? 'ok' : ($fase === $i ? 'on' : '') ?>"><i><?= $fase > $i ? '✓' : $i + 1 ?></i><?= e($t) ?></div>
      <?php endforeach; ?>
    </div>
    <form class="card hero" method="post" action="<?= Controller::url('cajero/asistencia') ?>">
      <div class="big">🕘</div>
      <div><h2>Registra tu asistencia</h2><p>Marca tu entrada para poder abrir caja. Hasta las 8:30 se registra como temprano y después de las 8:30 como tarde.</p></div>
      <button class="btn y" type="submit">Registrar asistencia</button>
      <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    </form>

  <?php elseif ($fase === 1):
      $libres = array_filter($cajas, fn($c) => !$c['ocupada']);
      $hora = date('H:i', strtotime($asistencia['entrada']));
  ?>

    <div class="steps">
      <?php foreach (['Asistencia', 'Abrir caja', 'Operar'] as $i => $t): ?>
        <div class="step <?= $fase > $i ? 'ok' : ($fase === $i ? 'on' : '') ?>"><i><?= $fase > $i ? '✓' : $i + 1 ?></i><?= e($t) ?></div>
      <?php endforeach; ?>
    </div>

    <?php $esAs = $asistencia['estado']; $colAs = ['PRESENTE' => ['#e3f6ec', 'var(--ok)'], 'TARDANZA' => ['#fff3c4', '#8a6700'], 'FALTA' => ['#fbe5e5', 'var(--bad)']][$esAs] ?? ['#e3f6ec', 'var(--ok)']; ?>
    <span class="chip" style="display:inline-block;margin-bottom:14px;background:<?= $colAs[0] ?>;color:<?= $colAs[1] ?>;border:0">✓ Entrada registrada a las <?= e($hora) ?> · <?= e(AsistenciaRepository::etiqueta($esAs)) ?></span>

    <div class="split">
      <form class="card" id="formAbrir" method="post" action="<?= Controller::url('caja/abrir') ?>">
        <?php if (!$cajas): ?>
          <div class="empty">Tu sucursal no tiene cajas registradas</div>
        <?php else: ?>
          <p class="sub">1 · Elige tu caja</p>
          <div class="cajas">
            <?php foreach ($cajas as $c): $uso = (bool)$c['ocupada']; ?>
              <label class="cj <?= $uso ? 'uso' : '' ?>">
                <input type="radio" name="caja_id" value="<?= (int)$c['id'] ?>" data-n="<?= e($c['nombre']) ?>" <?= $uso ? 'disabled' : 'required' ?>>
                <span style="font-size:22px">🖥️</span>
                <b><?= e($c['nombre']) ?></b>
                <small><?= $uso ? 'En uso · ' . e($c['responsable']) : 'Libre' ?></small>
              </label>
            <?php endforeach; ?>
          </div>

          <?php if (!$libres): ?>
            <div class="empty" style="margin-top:14px">Todas las cajas ya tienen un turno abierto</div>
          <?php else: ?>
            <p class="sub">2 · Monto inicial</p>
            <div style="max-width:260px"><input class="inp" id="mi" name="monto_inicial" type="number" step="0.01" min="0" value="100.00"></div>
            <div class="chips">
              <?php foreach ([50, 100, 200, 300] as $v): ?>
                <button type="button" data-v="<?= $v ?>.00">S/ <?= $v ?></button>
              <?php endforeach; ?>
            </div>
            <div class="sum" id="sumAb"></div>
            <button class="btn y" id="ab" type="submit" disabled>Abrir caja</button>
          <?php endif; ?>

          <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
        <?php endif; ?>
      </form>

      <form class="card fin" id="formSalida" method="post" action="<?= Controller::url('cajero/salida') ?>">
        <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
        <i>🚪</i>
        <h3>¿Terminas por hoy?</h3>
        <p>Si no vas a vender, registra tu salida sin abrir caja.</p>
        <button type="button" class="btn red" id="btnTerminar" style="width:100%">Terminar mi turno</button>
      </form>
    </div>

  <?php else: ?>

    <div id="paneCaja">
      <div class="kp">
        <div><p>💰 Monto inicial</p><h2><?= money($turno['monto_inicial']) ?></h2></div>
        <div><p>🧾 Ventas del turno</p><h2><?= money($resumen['total']) ?></h2><small><?= (int)$resumen['n'] ?> comprobantes</small></div>
        <div><p>💵 Efectivo cobrado</p><h2><?= money($efectivo) ?></h2></div>
        <div style="background:var(--k);color:#fff;border:0"><p style="color:var(--g3)">🏦 Esperado en caja</p><h2 style="color:var(--y)"><?= money($esperado) ?></h2></div>
      </div>

      <div class="card" style="padding:0;overflow:hidden">
        <table>
          <thead><tr><th>Comprobante</th><th>Hora</th><th>Cliente</th><th style="text-align:right">Total</th></tr></thead>
          <tbody>
          <?php foreach ($ventas as $v): ?>
            <tr>
              <td><b><?= e($v['comprobante']) ?></b></td>
              <td><?= e(date('H:i', strtotime($v['fecha']))) ?></td>
              <td><?= e($v['cliente']) ?></td>
              <td style="text-align:right"><?= money($v['total']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php if (!$ventas): ?><div class="empty">Todavía no hay ventas en este turno</div><?php endif; ?>
      </div>
    </div>

    <div id="paneCierre" style="display:none">
      <div class="card" style="max-width:560px">
        <div class="resumen">
          <div><small>Inicial</small><b><?= money($turno['monto_inicial']) ?></b></div>
          <div><small>Efectivo cobrado</small><b><?= money($efectivo) ?></b></div>
          <div><small>Esperado</small><b><?= money($esperado) ?></b></div>
        </div>
        <form id="formCerrar" method="post" action="<?= Controller::url('caja/cerrar') ?>">
          <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
          <input type="hidden" name="historia_id" value="<?= (int)$turno['id'] ?>">
          <p class="sub">Dinero contado (S/)</p>
          <input class="inp" id="contado" name="contado" type="number" step="0.01" min="0" placeholder="0.00" required autofocus>
          <p style="margin-top:12px">Diferencia: <span class="tag" id="dif" style="display:none"></span></p>
          <button class="btn y big" style="margin-top:16px" type="submit">Cerrar caja</button>
        </form>
      </div>
    </div>

  <?php endif; ?>
  </div>
</main>

<div class="modal" id="modal"><div class="mbox" id="mbox"></div></div>
<div id="toast"></div>

<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var clock = $('clock');
  function t() {
    clock.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false});
  }
  t(); setInterval(t, 1000);

  var modal = $('modal'), mbox = $('mbox');
  window.cerrarModal = function () { modal.classList.remove('on'); };
  function abrirModal(html) { mbox.innerHTML = html; modal.classList.add('on'); }
  modal.addEventListener('click', function (e) { if (e.target === modal) cerrarModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarModal(); });

  <?php if ($fase === 1): ?>
  var monto = $('mi');
  if (monto) {
    var radios = [].slice.call(document.querySelectorAll('#formAbrir input[name=caja_id]')),
        chips = [].slice.call(document.querySelectorAll('#formAbrir .chips button')),
        res = $('sumAb'), ab = $('ab');

    function resumen() {
      var m = monto.value.trim().replace(',', '.'),
          ok = /^\d+(\.\d{1,2})?$/.test(m),
          sel = radios.filter(function (r) { return r.checked; })[0];
      [].slice.call(document.querySelectorAll('.cj')).forEach(function (l) {
        l.classList.toggle('on', l.querySelector('input').checked);
      });
      chips.forEach(function (c) { c.classList.toggle('on', ok && parseFloat(c.dataset.v) === parseFloat(m)); });
      res.innerHTML = !sel ? 'Elige una caja para continuar.'
        : 'Abrirás <b>' + sel.dataset.n + '</b> con <b>' + (ok ? 'S/ ' + Number(m).toFixed(2) : 'S/ —') + '</b>.';
      if (ab) ab.disabled = !sel || !ok;
    }

    radios.forEach(function (r) { r.addEventListener('change', resumen); });
    chips.forEach(function (b) { b.addEventListener('click', function () { monto.value = b.dataset.v; resumen(); }); });
    monto.addEventListener('input', resumen);
    resumen();
  }

  var formSalida = $('formSalida');
  function confTurno() {
    abrirModal('<h3>🚪 ¿Terminar tu turno?</h3><p style="color:var(--g4)">Se registrará tu salida a las <b>'
      + tHora() + '</b>. No podrás volver a registrar asistencia hoy.</p>'
      + '<div class="mrow"><button class="btn g" type="button" onclick="cerrarModal()">Cancelar</button>'
      + '<button class="btn y" type="button" onclick="document.getElementById(\'formSalida\').submit()">Sí, terminar turno</button></div>');
  }
  function tHora() {
    return new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false});
  }
  $('btnTerminar').addEventListener('click', confTurno);
  var navTerminar = $('navTerminar');
  if (navTerminar) navTerminar.addEventListener('click', function (e) { e.preventDefault(); confTurno(); });
  if (location.hash === '#terminar' && formSalida) confTurno();
  window.confTurno = confTurno;
  <?php endif; ?>

  <?php if ($fase === 2): ?>
  var paneCaja = $('paneCaja'), paneCierre = $('paneCierre'), ttl = $('ttl'),
      navCaja = $('navCaja'), navCierre = $('navCierre'),
      tituloCaja = 'Mi caja<small>Resumen de tu turno</small>',
      tituloCierre = 'Cerrar caja<small>Cuenta el dinero y escribe el total</small>';

  function tab(t) {
    var cierre = t === 'cierre';
    paneCaja.style.display = cierre ? 'none' : '';
    paneCierre.style.display = cierre ? '' : 'none';
    ttl.innerHTML = cierre ? tituloCierre : tituloCaja;
    if (navCaja) navCaja.classList.toggle('on', !cierre);
    if (navCierre) { navCierre.classList.toggle('on', cierre); navCierre.classList.toggle('out', !cierre); }
  }
  window.addEventListener('hashchange', function () { tab(location.hash === '#cierre' ? 'cierre' : 'caja'); });
  tab(location.hash === '#cierre' ? 'cierre' : 'caja');

  var formCerrar = $('formCerrar');
  formCerrar.addEventListener('submit', function (e) {
    e.preventDefault();
    abrirModal('<h3>🔒 ¿Cerrar tu caja?</h3><p style="color:var(--g4)">No podrás vender hasta abrirla otra vez. Después podrás abrir otra caja o terminar tu turno.</p>'
      + '<div class="mrow"><button class="btn g" type="button" onclick="cerrarModal()">Cancelar</button>'
      + '<button class="btn y" type="button" onclick="document.getElementById(\'formCerrar\').submit()">Sí, cerrar caja</button></div>');
  });

  var esperado = <?= json_encode($esperado) ?>;
  var inp = $('contado'), dif = $('dif');
  inp.addEventListener('input', function () {
    if (inp.value === '') { dif.style.display = 'none'; return; }
    var d = Math.round((parseFloat(inp.value) - esperado) * 100) / 100;
    dif.style.display = '';
    dif.textContent = d === 0 ? 'Cuadra exacto ✔' : (d > 0 ? 'Sobra ' : 'Falta ') + 'S/ ' + Math.abs(d).toFixed(2);
    dif.className = 'tag ' + (d === 0 ? 'ok' : 'bad');
  });
  <?php endif; ?>
})();
</script>
</body>
</html>
