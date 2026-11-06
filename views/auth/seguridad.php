<style>
.sgd{max-width:560px;margin:8px auto;color:#0b1330}
.sgd h1{font-size:24px;margin-bottom:6px}
.sgd .sub{color:#5b6482;margin-bottom:22px}
.sgd .box{background:#fff;border:1px solid #dfe4f1;border-radius:16px;padding:24px;margin-bottom:16px}
.sgd .paso{display:flex;gap:14px;margin-bottom:20px}
.sgd .paso b{flex:none;width:28px;height:28px;border-radius:50%;background:#ffc400;color:#111;display:grid;place-items:center;font-size:14px}
.sgd #qr{display:inline-block;padding:10px;background:#fff;border:1px solid #dfe4f1;border-radius:12px;margin:10px 0}
.sgd code{display:block;word-break:break-all;background:#f3f5fb;padding:8px 10px;border-radius:8px;font-size:13px;margin-top:6px}
.sgd input[type=text],.sgd input[type=password]{width:100%;height:46px;border:1.5px solid #dfe4f1;border-radius:10px;padding:0 14px;font-size:18px;letter-spacing:.2em;margin:8px 0 12px}
.sgd input[type=password]{letter-spacing:normal;font-size:15px}
.sgd button{height:44px;padding:0 22px;border:0;border-radius:10px;background:#ffc400;font-weight:700;cursor:pointer}
.sgd button.peligro{background:#e5484d;color:#fff}
.sgd .ok,.sgd .err{padding:11px 14px;border-radius:10px;margin-bottom:14px;font-size:14px}
.sgd .ok{background:#e7f8ef;color:#0c7a47}.sgd .err{background:#fdecec;color:#c0262b}
.sgd .estado{display:inline-block;padding:3px 10px;border-radius:99px;background:#e7f8ef;color:#0c7a47;font-size:13px;font-weight:600}
</style>

<div class="sgd">
  <h1>Seguridad de la cuenta</h1>
  <p class="sub">Protege tu acceso con un código de 6 dígitos que cambia cada 30 segundos.</p>

  <?php if ($ok): ?><div class="ok" role="status"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>

  <?php if ($totp): ?>
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
