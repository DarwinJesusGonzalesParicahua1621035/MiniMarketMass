<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Seguridad · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
<style>
/* Dentro del panel del cajero el título ya está en la cabecera */
.sgd-wrap .sgd{margin:0;max-width:600px}
.sgd-wrap .sgd>h1,.sgd-wrap .sgd>.sub{display:none}
</style>
</head>
<body class="cajero">
<?php $activo = 'seguridad'; require __DIR__ . '/_nav.php'; ?>
<main>
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Seguridad<small>Protege tu acceso con un código de 6 dígitos que cambia cada 30 segundos</small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <div class="sgd-wrap">
    <?php require ROOT . '/views/auth/seguridad.php'; ?>
  </div>
</main>

<script src="<?= Controller::base() ?>/js/app.js"></script>
<script>
(function () {
  var c = document.getElementById('clock');
  function t() { c.textContent = '🕘 ' + new Date().toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit', hour12: false}); }
  t(); setInterval(t, 1000);
})();
</script>
</body>
</html>
