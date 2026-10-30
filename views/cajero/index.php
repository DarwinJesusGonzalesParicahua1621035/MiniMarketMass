<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Caja · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="login">
  <div class="login-card">
    <div class="logo"><i>M</i><div>Tiendas Mass<small>CAJA</small></div></div>
    <h3>Hola, <?= e($user['nombre']) ?></h3>
    <p style="margin:10px 0 0;color:#5B616C">Pantalla del cajero en construcción.</p>
    <a class="btn" style="text-align:center;margin-top:20px" href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a>
  </div>
</body>
</html>