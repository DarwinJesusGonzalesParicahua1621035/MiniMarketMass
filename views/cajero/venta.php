<?php
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nueva venta · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="cajero">
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <nav>
    <h6>Operación</h6>
    <a href="<?= Controller::url('cajero/venta') ?>" class="on"><span>🛒</span>Nueva venta</a>
    <button type="button"><span>🧾</span>Mis ventas</button>
    <a href="<?= Controller::url('cajero/caja') ?>"><span>💵</span>Mi caja</a>
    <h6>Consulta</h6>
    <button type="button"><span>👥</span>Clientes</button>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small>
      <a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></div></div>
</aside>
<main>
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Nueva venta<small>Toca o arrastra un producto a la boleta</small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <?php if ($turno): ?><span class="chip">💵 <?= e($turno['caja']) ?> <span class="tag ok">ABIERTA</span></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <?php if (!$turno): ?>
    <div class="card vacio">
      <div style="font-size:42px">🔒</div>
      <h3 style="font-size:20px;margin:10px 0 6px;color:var(--k)">Tu caja está cerrada</h3>
      <p style="margin-bottom:18px">Abre tu caja para empezar a vender.</p>
      <a class="btn y" href="<?= Controller::url('caja/abrir') ?>">Abrir caja</a>
    </div>
  <?php else: ?>
    <div class="pos">
      <div>
        <div class="scan">
          <input class="inp" id="q" placeholder="🔍  Código de barras o nombre del producto…" autocomplete="off">
          <button class="btn k" id="agregar" type="button">Agregar</button>
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

<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);
})();
</script>
<?php if ($cfg): ?>
<script>window.POS = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= Controller::base() ?>/js/venta.js"></script>
<?php endif; ?>
</body>
</html>