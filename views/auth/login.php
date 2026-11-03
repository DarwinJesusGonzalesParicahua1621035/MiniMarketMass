<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar · Tiendas Mass</title>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
</head>
<body class="login">
  <form class="login-card campos" method="post" action="<?= Controller::url('auth/login') ?>">
    <div class="logo"><i>M</i><div>Tiendas Mass<small>PANEL ADMIN</small></div></div>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <label>Usuario<input name="usuario" required autofocus></label>
    <label>Clave<input type="password" name="clave" required></label>
    <button class="btn">Ingresar</button>
  </form>
</body>
</html>