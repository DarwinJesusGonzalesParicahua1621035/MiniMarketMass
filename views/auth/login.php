<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Ingresar · Tiendas Mass</title>
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
      <h1>Cada venta cuenta. Aquí las ves todas.</h1>
      <p>Ventas, caja e inventario de cada sucursal en un solo lugar.</p>
    </div>
    <div class="stage" aria-hidden="true">
      <div class="ticket" id="ticket">
        <h3>TIENDAS MASS</h3>
        <div class="sub">Mass Cayma · Caja 01</div>
        <ul>
          <li><span>1 Leche evaporada</span><span>4.80</span></li>
          <li><span>1 Pan de molde</span><span>6.50</span></li>
          <li><span>1 Aceite 1 L</span><span>9.90</span></li>
          <li><span>1 Arroz 5 kg</span><span>22.50</span></li>
        </ul>
        <div class="row"><span>IGV 18% incluido</span><span>6.67</span></div>
        <div class="tot"><span>TOTAL S/</span><span id="total">0.00</span></div>
        <div class="stamp">PAGADO</div>
      </div>
    </div>
    <footer>© 2026 Tiendas Mass · Arequipa</footer>
  </aside>

  <main class="panel">
    <button class="theme" id="theme" type="button" aria-label="Cambiar tema">
      <svg class="moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      <svg class="sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
    </button>

    <section class="card">
      <div class="logo mlogo"><b>M</b><span>Tiendas Mass</span></div>
      <h2>Inicia sesión</h2>
      <p class="lead">Usa tu usuario de Tiendas Mass para entrar.</p>

      <?php if ($error): ?>
        <div class="alert" role="alert">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form id="loginForm" method="post" action="<?= Controller::url('auth/login') ?>" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">

        <div class="field">
          <input id="usuario" name="usuario" type="text" placeholder=" " autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
          <label for="usuario">Usuario</label>
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        </div>

        <div class="field">
          <input id="clave" name="clave" type="password" placeholder=" " autocomplete="current-password" required>
          <label for="clave">Contraseña</label>
          <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
          <button class="eye" id="eye" type="button" aria-label="Mostrar contraseña">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>

        <div class="caps" id="caps">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3 4 13h5v5h6v-5h5z"/></svg>
          Bloq Mayús está activado
        </div>

        <div class="opts">
          <label class="chk"><input type="checkbox" name="recordar" value="1"> Mantener sesión en este equipo</label>
        </div>

        <button class="btn" id="btn" type="submit"><span class="spin"></span><span id="btxt">Entrar</span></button>
      </form>

      <p class="help">¿No recuerdas tu clave? Pide a tu administrador que la restablezca.</p>
    </section>
  </main>
</div>

<script>
const $ = s => document.querySelector(s);
const root = document.documentElement;

$('#theme').onclick = () => {
  const t = root.dataset.tema === 'dark' ? 'light' : 'dark';
  root.dataset.tema = t;
  try { localStorage.setItem('mm-tema', t); } catch(e){}
};

const pw = $('#clave');
$('#eye').onclick = e => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  e.currentTarget.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
};

const caps = $('#caps');
['keydown','keyup'].forEach(ev => pw.addEventListener(ev, e => {
  caps.classList.toggle('show', e.getModifierState && e.getModifierState('CapsLock'));
}));
pw.addEventListener('blur', () => caps.classList.remove('show'));

$('#loginForm').addEventListener('submit', () => {
  $('#btn').classList.add('load');
  $('#btxt').textContent = 'Verificando…';
});

const brand = $('#brand');
brand.addEventListener('pointermove', e => {
  const r = brand.getBoundingClientRect();
  brand.style.setProperty('--mx', (e.clientX - r.left) + 'px');
  brand.style.setProperty('--my', (e.clientY - r.top) + 'px');
});

const ticket = $('#ticket'), items = [...ticket.querySelectorAll('li')], totalEl = $('#total');
const TOTAL = 43.70;
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
let timers = [];
const at = (ms, fn) => timers.push(setTimeout(fn, ms));

function count(to, dur){
  const t0 = performance.now();
  (function step(t){
    const k = Math.min((t - t0) / dur, 1), e = 1 - Math.pow(1 - k, 3);
    totalEl.textContent = (to * e).toFixed(2);
    if (k < 1) requestAnimationFrame(step);
  })(t0);
}

function cycle(){
  timers.forEach(clearTimeout); timers = [];
  ticket.className = 'ticket';
  items.forEach(li => li.classList.remove('on'));
  totalEl.textContent = '0.00';
  void ticket.offsetWidth;
  ticket.classList.add('in');
  items.forEach((li, i) => at(1100 + i * 650, () => li.classList.add('on')));
  const t = 1100 + items.length * 650;
  at(t, () => count(TOTAL, 900));
  at(t + 1100, () => ticket.classList.add('done'));
  at(t + 4200, () => ticket.classList.add('out'));
  at(t + 5000, cycle);
}

if (reduce){
  items.forEach(li => li.classList.add('on'));
  totalEl.textContent = TOTAL.toFixed(2);
  ticket.classList.add('done');
} else {
  cycle();
}
</script>
</body>
</html>