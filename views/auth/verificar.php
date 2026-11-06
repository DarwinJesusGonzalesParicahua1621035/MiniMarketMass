<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Verifica tu identidad · Tiendas Mass</title>
<link rel="stylesheet" href="css/login.css">
<script>
  (function(){try{var t=localStorage.getItem('mm-tema');
  if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';
  document.documentElement.dataset.tema=t}catch(e){}})();
</script>
</head>
<body>
<div class="shell">
  <aside class="brand" id="brand">
    <div class="logo"><b>M</b><span>Tiendas Mass<small>Panel de gestión</small></span></div>
    <div class="hero">
      <h1>Un paso más para proteger tu caja.</h1>
      <p>Abre la app de autenticación de tu celular y escribe el código de 6 dígitos.</p>
    </div>
    <div class="stage"></div>
    <footer>© 2026 Tiendas Mass · Arequipa</footer>
  </aside>

  <main class="panel">
    <button class="theme" id="theme" type="button" aria-label="Cambiar tema">
      <svg class="moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      <svg class="sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>

    <section class="card">
      <div class="logo mlogo"><b>M</b><span>Tiendas Mass</span></div>
      <h2>Verifica tu identidad</h2>
      <p class="lead">Escribe el código de 6 dígitos que muestra tu app de autenticación.</p>

      <?php if ($error): ?>
        <div class="alert" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form id="otpForm" method="post" action="<?= Controller::url('auth/verificar') ?>" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input class="otp" id="codigo" name="codigo" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="000000" aria-label="Código de 6 dígitos" required autofocus>
        <button class="btn" id="btn" type="submit"><span class="spin"></span><span id="btxt">Verificar</span></button>
      </form>

      <p class="help"><a href="<?= Controller::url('auth/login') ?>">Volver al inicio de sesión</a></p>
    </section>
  </main>
</div>

<script>
const $ = s => document.querySelector(s);
$('#theme').onclick = () => {
  const r = document.documentElement, t = r.dataset.tema === 'dark' ? 'light' : 'dark';
  r.dataset.tema = t;
  try { localStorage.setItem('mm-tema', t); } catch(e){}
};
const cod = $('#codigo'), form = $('#otpForm');
let enviado = false;
function marcar(){ $('#btn').classList.add('load'); $('#btxt').textContent = 'Verificando…'; }
cod.addEventListener('input', () => {
  cod.value = cod.value.replace(/\D/g, '').slice(0, 6);
  if (cod.value.length === 6 && !enviado) { enviado = true; marcar(); form.submit(); }
});
form.addEventListener('submit', () => { enviado = true; marcar(); });
</script>
</body>
</html>