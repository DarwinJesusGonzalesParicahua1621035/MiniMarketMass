<?php
$sucursalChip = preg_replace('/^Mass\s+/i', 'Sucursal ', (string)$sucursal);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva venta · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/cajero-panel.css?v=4">
</head>
<body class="cajero">
<?php $activo = 'venta'; require __DIR__ . '/_nav.php'; ?>
<main>
  <header>
    <?php if ($turno): ?>
      <h1>Nueva venta<small>Toca un producto o escanea el código · F2 buscar · F4 cobrar</small></h1>
    <?php else: ?>
      <h1>Nueva venta<small>Abre tu caja para empezar a vender</small></h1>
    <?php endif; ?>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursalChip) ?></span><?php endif; ?>
    <span class="chip live" id="cajaChip"<?= $turno ? '' : ' hidden' ?>><?= $turno ? e($turno['caja']) . ' · abierta' : '' ?></span>
    <span class="chip" id="clock">🕘</span>
  </header>

  <?php if (!$turno): ?>
    <div class="card hero" style="max-width:640px">
      <div class="big">🔒</div>
      <div><h2>Tu caja está cerrada</h2><p>Abre tu caja para empezar a vender.</p></div>
      <a class="btn y" href="<?= Controller::url('cajero/caja') ?>">Abrir caja</a>
    </div>
  <?php else: ?>
    <div class="pos">
      <div>
        <div class="scan">
          <input class="inp" id="q" placeholder="🔍  Código de barras o nombre del producto…" autocomplete="off">
          <button class="btn" id="agregar" type="button">Agregar</button>
        </div>
        <div class="cats" id="cats"></div>
        <div class="prods" id="grid"></div>
      </div>
      <div class="card cart" id="cartBox"></div>
    </div>
  <?php endif; ?>
</main>

<div class="modal" id="modal"><div class="mbox" id="mbox"></div></div>
<div id="toast"></div>

<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);
})();
</script>
<?php if ($cfg): ?>
<script>window.POS = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= Controller::base() ?>/js/venta.js?v=3"></script>
<?php endif; ?>
</body>
</html>
