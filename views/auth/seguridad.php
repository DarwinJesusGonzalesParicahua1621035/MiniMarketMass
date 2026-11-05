<?php if (!empty($solo)): ?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Seguridad · Tiendas Mass</title></head>
<body style="margin:0;background:#f3f5fb;font-family:system-ui,sans-serif">
<div style="background:#ffc400;padding:14px 22px;display:flex;align-items:center;gap:12px">
  <b style="background:#111;color:#ffc400;border-radius:8px;padding:4px 11px">M</b><strong>Tiendas Mass</strong>
  <a href="<?= Controller::url('cajero/index') ?>" style="margin-left:auto;color:#111;font-weight:700;text-decoration:none">← Volver a mi caja</a>
</div>
<div style="padding:24px 16px">
<?php endif; ?>
<style>
.seg{max-width:560px;margin:8px auto;color:#0b1330}
.seg h1{font-size:24px;margin-bottom:6px}
.seg .sub{color:#5b6482;margin-bottom:22px}
.seg .box{background:#fff;border:1px solid #dfe4f1;border-radius:16px;padding:24px;margin-bottom:16px}
.seg .paso{display:flex;gap:14px;margin-bottom:20px}
.seg .paso b{flex:none;width:28px;height:28px;border-radius:50%;background:#ffc400;color:#111;display:grid;place-items:center;font-size:14px}
.seg #qr{display:inline-block;padding:10px;background:#fff;border:1px solid #dfe4f1;border-radius:12px;margin:10px 0}
.seg code{display:block;word-break:break-all;background:#f3f5fb;padding:8px 10px;border-radius:8px;font-size:13px;margin-top:6px}
.seg input[type=text],.seg input[type=password]{width:100%;height:46px;border:1.5px solid #dfe4f1;border-radius:10px;padding:0 14px;font-size:18px;letter-spacing:.2em;margin:8px 0 12px}
.seg input[type=password]{letter-spacing:normal;font-size:15px}
.seg button{height:44px;padding:0 22px;border:0;border-radius:10px;background:#ffc400;font-weight:700;cursor:pointer}
.seg button.peligro{background:#e5484d;color:#fff}
.seg .ok,.seg .err{padding:11px 14px;border-radius:10px;margin-bottom:14px;font-size:14px}
.seg .ok{background:#e7f8ef;color:#0c7a47}.seg .err{background:#fdecec;color:#c0262b}
.seg .estado{display:inline-block;padding:3px 10px;border-radius:99px;background:#e7f8ef;color:#0c7a47;font-size:13px;font-weight:600}
</style>

<div class="seg">
  <h1>Seguridad de la cuenta</h1>
  <p class="sub">Protege tu acceso con un código de 6 dígitos que cambia cada 30 segundos.</p>

  <?php if ($ok): ?><div class="ok" role="status"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>

  <?php if ($activo): ?>
    <div class="box">
      <p><span class="estado">Activado</span></p>
      <p style="margin:12px 0">Al iniciar sesión te pediremos el código de tu app de autenticación.</p>
      <form method="post" action="<?= Controller::url('seguridad/index') ?>">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="accion" value="desactivar">
        <label>Para desactivarlo, escribe tu contraseña</label>
        <input type="password" name="clave" autocomplete="current-password" required>
        <button class="peligro" type="submit">Desactivar segundo paso</button>
      </form>
    </div>
  <?php else: ?>
    <div class="box">
      <div class="paso"><b>1</b><div>
        <strong>Escanea el código QR</strong>
        <p style="color:#5b6482;font-size:14px">Abre Google Authenticator o Microsoft Authenticator y agrega una cuenta.</p>
        <div id="qr"></div>
        <p style="font-size:13px;color:#5b6482">¿No puedes escanear? Escribe esta clave a mano:</p>
        <code><?= e($secreto) ?></code>
      </div></div>
      <div class="paso" style="margin-bottom:0"><b>2</b><div style="flex:1">
        <strong>Escribe el código que aparece en la app</strong>
        <form method="post" action="<?= Controller::url('seguridad/index') ?>">
          <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
          <input type="hidden" name="accion" value="activar">
          <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="000000" autocomplete="one-time-code" required>
          <button type="submit">Activar segundo paso</button>
        </form>
      </div></div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>new QRCode(document.getElementById('qr'), {text: <?= json_encode($uri) ?>, width: 176, height: 176});</script>
  <?php endif; ?>
</div>

<?php if (!empty($solo)): ?>
</div></body></html>
<?php endif; ?>