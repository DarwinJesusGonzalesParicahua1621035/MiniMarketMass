<?php
$emitidas = array_filter($filas, fn($r) => $r['estado'] === 'EMITIDA');
$total = array_sum(array_map(fn($r) => (float)$r['total'], $emitidas));
$boletas = count(array_filter($emitidas, fn($r) => $r['tipo'] === 'BOLETA'));
$facturas = count($emitidas) - $boletas;
$anuladas = count($filas) - count($emitidas);
$prev = date('Y-m-d', strtotime($dia . ' -1 day'));
$next = date('Y-m-d', strtotime($dia . ' +1 day'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mis ventas · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="cajero">
<?php $activo = 'ventas'; require __DIR__ . '/_nav.php'; ?>
<main>
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Mis ventas<small>Comprobantes que emitiste</small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php" style="margin-bottom:14px">
    <input type="hidden" name="r" value="cajero/ventas">
    <a class="btn g sm" href="<?= Controller::url('cajero/ventas', ['fecha' => $prev]) ?>">‹</a>
    <input class="inp" type="date" name="fecha" value="<?= e($dia) ?>" style="width:auto" onchange="this.form.submit()">
    <a class="btn g sm" href="<?= Controller::url('cajero/ventas', ['fecha' => $next]) ?>">›</a>
    <a class="btn g sm" href="<?= Controller::url('cajero/ventas') ?>">Hoy</a>
  </form>

  <div class="kp-grid">
    <div class="kp y"><div class="hd"><i>💰</i><p>Total vendido</p></div><h2><?= money($total) ?></h2><em class="n"><?= count($emitidas) ?> comprobantes</em></div>
    <div class="kp b"><div class="hd"><i>🧾</i><p>Boletas</p></div><h2><?= $boletas ?></h2><em class="n">Emitidas</em></div>
    <div class="kp g"><div class="hd"><i>📄</i><p>Facturas</p></div><h2><?= $facturas ?></h2><em class="n">Emitidas</em></div>
    <div class="kp p"><div class="hd"><i>🚫</i><p>Anuladas</p></div><h2><?= $anuladas ?></h2><em class="n">Del día</em></div>
  </div>

  <div class="tw">
    <div class="tools">
      <h3>Ventas del <?= e(date('d/m/Y', strtotime($dia))) ?> <span class="tag b"><?= count($filas) ?></span></h3>
      <input class="search" data-filtro="tVentas" placeholder="Buscar comprobante o cliente…">
    </div>
    <div class="scroll">
      <table id="tVentas">
        <thead>
          <tr><th>Comprobante</th><th>Hora</th><th>Cliente</th><th>Pago</th><th style="text-align:right">Total</th><th>Estado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $v): ?>
          <tr>
            <td><b><?= e($v['comprobante']) ?></b> <?= celda($v['tipo'], 'etiqueta') ?></td>
            <td><?= e(date('H:i', strtotime($v['fecha']))) ?></td>
            <td><?= e($v['cliente']) ?></td>
            <td><?= e($v['pagos'] ?? '—') ?></td>
            <td style="text-align:right"><?= money($v['total']) ?></td>
            <td><?= celda($v['estado'], 'etiqueta') ?></td>
            <td><button type="button" class="btn g sm ver" data-id="<?= (int)$v['id'] ?>">Ver</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">No hay ventas en esta fecha</div><?php endif; ?>
    </div>
  </div>
</main>

<div class="modal" id="modal"><div class="mbox" id="mbox"></div></div>

<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);

  var URL_DET = <?= json_encode($urlDetalle) ?>;
  var modal = document.getElementById('modal'), mbox = document.getElementById('mbox');
  var esc = function (s) { return String(s).replace(/[&<>"']/g, function (m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; }); };
  var money = function (n) { return 'S/ ' + Number(n).toFixed(2); };
  var cerrar = function () { modal.classList.remove('on'); };

  modal.addEventListener('click', function (e) { if (e.target === modal) cerrar(); });

  document.querySelectorAll('.ver').forEach(function (b) {
    b.addEventListener('click', function () {
      fetch(URL_DET + '&id=' + b.dataset.id)
        .then(function (r) { return r.json(); })
        .then(function (r) {
          if (!r.ok) { alert(r.error); return; }
          var v = r.venta;
          var items = r.items.map(function (i) {
            return '<div>' + esc(i.producto) + '</div><div class="f"><span>' + Number(i.cantidad) + ' x ' + money(i.precio_unitario) + '</span><span>' + money(i.total_linea) + '</span></div>';
          }).join('');
          var pagos = r.pagos.map(function (p) {
            return '<div class="f"><span>' + esc(p.medio_pago) + '</span><span>' + money(p.monto) + '</span></div>';
          }).join('');
          mbox.innerHTML = '<h3>' + esc(v.comprobante) + ' ' + (v.estado === 'ANULADA' ? '<span class="tag bad">ANULADA</span>' : '') + '</h3>' +
            '<div id="ticket">' +
            '<div class="c"><b>TIENDAS MASS</b><br>' + esc(v.sucursal) + '</div><hr>' +
            '<div class="c">' + esc(v.tipo) + ' ELECTRÓNICA<br>' + esc(v.comprobante) + '</div><hr>' +
            '<div>Fecha: ' + esc(v.fecha) + '</div>' +
            '<div>Cliente: ' + esc(v.cliente) + '</div>' +
            (v.doc ? '<div>Doc: ' + esc(v.doc) + '</div>' : '') +
            '<div>Cajero: ' + esc(v.cajero) + '</div><hr>' + items + '<hr>' +
            '<div class="f"><span>Subtotal</span><span>' + money(v.subtotal) + '</span></div>' +
            '<div class="f"><span>IGV 18%</span><span>' + money(v.igv) + '</span></div>' +
            '<div class="f"><b>TOTAL</b><b>' + money(v.total) + '</b></div><hr>' + pagos +
            '</div>' +
            '<div class="mrow"><button class="btn g" type="button" id="dcerrar">Cerrar</button><button class="btn" type="button" id="dimprimir">🖨️ Imprimir</button></div>';
          document.getElementById('dcerrar').onclick = cerrar;
          document.getElementById('dimprimir').onclick = function () { window.print(); };
          modal.classList.add('on');
        });
    });
  });
})();
</script>
</body>
</html>