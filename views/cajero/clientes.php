<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Clientes · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="cajero">
<?php $activo = 'clientes'; require __DIR__ . '/_nav.php'; ?>
<main>
  <header>
    <button id="menu" type="button">☰</button>
    <h1>Clientes<small>Consulta por nombre, DNI o RUC</small></h1>
    <?php if ($sucursal): ?><span class="chip">🏬 <?= e($sucursal) ?></span><?php endif; ?>
    <span class="chip" id="clock">🕘</span>
  </header>

  <div class="tw">
    <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
      <input type="hidden" name="r" value="cajero/clientes">
      <h3>Clientes registrados <span class="tag b"><?= count($filas) ?></span></h3>
      <input class="inp" name="q" value="<?= e($q) ?>" placeholder="🔍  Nombre, DNI o RUC…" style="width:280px" autofocus>
      <button class="btn k" type="submit">Buscar</button>
      <?php if ($q !== ''): ?><a class="btn g" href="<?= Controller::url('cajero/clientes') ?>">Limpiar</a><?php endif; ?>
    </form>
    <div class="scroll">
      <table>
        <thead>
          <tr><th>Tipo</th><th>N.º documento</th><th>Nombre o razón social</th><th style="text-align:right">Compras</th></tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $c): ?>
          <tr>
            <td><?= celda($c['tipo_documento'], 'etiqueta') ?></td>
            <td><b><?= e($c['numero_documento']) ?></b></td>
            <td><?= e($c['nombre']) ?></td>
            <td style="text-align:right"><?= (int)$c['compras'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$filas): ?><div class="empty">No se encontraron clientes</div><?php endif; ?>
    </div>
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