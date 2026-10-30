<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Mass</title>
<style> /* Reutiliza tus estilos base */ body{display:grid;place-items:center;height:100vh;background:var(--g1)} .modal form{display:block} </style>
</head>
<body>
  <div class="modal on" style="position:static;background:transparent;">
    <form action="/login" method="POST" style="background:white; padding:20px; border-radius:14px;">
      <h3>Iniciar Sesión</h3>
      <?php if(isset($_GET['error'])): ?><p style="color:red;font-size:12px;">Credenciales incorrectas</p><?php endif; ?>
      <label>Usuario</label>
      <input type="text" name="usuario" required>
      <label>Clave</label>
      <input type="password" name="clave" required>
      <div class="row" style="margin-top:20px;"><button class="btn y" style="width:100%">Entrar</button></div>
    </form>
  </div>
</body>
</html>