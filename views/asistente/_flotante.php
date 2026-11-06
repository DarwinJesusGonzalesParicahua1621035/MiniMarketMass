<?php
// Asistente IA flotante: botón en la esquina inferior derecha + panel lateral (drawer).
// Se incluye desde views/layout/main.php solo para el ADMINISTRADOR.
$iaListo = Gemini::disponible();
$iaSug = [
    '¿Cuánto vendimos hoy en cada sucursal?',
    '¿Qué productos están agotados o con stock bajo?',
    'Top 5 de productos más vendidos del mes',
    'Compara esta semana con la anterior',
    '¿Cuántos empleados tenemos?',
];
?>
<style>
.iaw-fab{position:fixed;right:24px;bottom:24px;z-index:900;width:58px;height:58px;border-radius:50%;border:0;cursor:pointer;
  background:var(--y);color:var(--k);display:grid;place-items:center;box-shadow:0 6px 20px rgba(0,0,0,.25);transition:transform .15s,box-shadow .15s}
.iaw-fab:hover{transform:scale(1.08);box-shadow:0 8px 26px rgba(0,0,0,.3)}
.iaw-fab:focus-visible{outline:3px solid var(--b);outline-offset:3px}
.iaw-fab svg{width:28px;height:28px}
.iaw-fab.oculto{transform:scale(0);pointer-events:none}
.iaw-fondo{position:fixed;inset:0;background:rgba(15,17,21,.35);z-index:950;opacity:0;pointer-events:none;transition:opacity .25s}
.iaw-fondo.on{opacity:1;pointer-events:auto}
.iaw{position:fixed;top:0;right:0;height:100vh;height:100dvh;width:min(430px,100vw);z-index:960;background:#fff;display:flex;flex-direction:column;
  box-shadow:-10px 0 40px rgba(0,0,0,.2);transform:translateX(105%);transition:transform .28s cubic-bezier(.3,.8,.3,1);visibility:hidden}
.iaw.on{transform:none;visibility:visible}
.iaw-top{display:flex;align-items:center;gap:10px;background:var(--y);padding:14px 16px;flex-shrink:0}
.iaw-top i{background:var(--k);color:var(--y);width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-style:normal;font-size:17px}
.iaw-top b{font-size:16px;letter-spacing:-.3px;flex:1}
.iaw-top small{display:block;font-size:11px;font-weight:600;opacity:.7}
.iaw-top button{border:0;background:rgba(0,0,0,.1);color:var(--k);border-radius:9px;height:34px;min-width:34px;padding:0 10px;font:inherit;font-size:13px;font-weight:700;cursor:pointer}
.iaw-top button:hover{background:rgba(0,0,0,.2)}
.iaw-aviso{background:#FFF8DD;border-bottom:1px solid #F3DB85;padding:10px 16px;font-size:13px;line-height:1.5}
.iaw-aviso code{background:#fff;border:1px solid var(--g2);border-radius:5px;padding:0 5px}
.iaw-msgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#fff}
.iaw-m{max-width:88%;padding:10px 14px;border-radius:16px;font-size:14px;line-height:1.55;word-wrap:break-word}
.iaw-m p{margin:0 0 6px}.iaw-m p:last-child{margin-bottom:0}
.iaw-m ul{margin:4px 0 6px 18px}.iaw-m li{margin-bottom:3px}
.iaw-m code{background:rgba(0,0,0,.07);border-radius:5px;padding:0 5px}
.iaw-m.user{align-self:flex-end;background:var(--k);color:#fff;border-bottom-right-radius:5px;white-space:pre-wrap}
.iaw-m.bot{align-self:flex-start;background:var(--g1);color:var(--k);border-bottom-left-radius:5px}
.iaw-m.err{background:#fbe5e5;color:var(--bad)}
.iaw-dots i{display:inline-block;width:7px;height:7px;margin:0 2px;border-radius:50%;background:var(--g3);animation:iawD 1s infinite}
.iaw-dots i:nth-child(2){animation-delay:.15s}.iaw-dots i:nth-child(3){animation-delay:.3s}
@keyframes iawD{0%,60%,100%{opacity:.25;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}
.iaw-sug{display:flex;gap:6px;flex-wrap:wrap;padding:0 16px 10px;background:#fff}
.iaw-sug button{border:1px solid var(--g2);background:#fff;border-radius:99px;padding:6px 12px;font:inherit;font-size:12.5px;font-weight:600;cursor:pointer;text-align:left}
.iaw-sug button:hover{border-color:var(--y);background:var(--y2)}
.iaw-form{display:flex;gap:8px;padding:10px 16px 6px;border-top:1px solid var(--g2);align-items:flex-end;background:#fff}
.iaw-form textarea{flex:1;resize:none;border:1px solid var(--g2);border-radius:14px;padding:11px 13px;font:inherit;font-size:14px;outline:none;max-height:110px;background:#fff}
.iaw-form textarea:focus{border-color:var(--b);box-shadow:0 0 0 3px var(--b2)}
.iaw-form .btn{height:44px;padding:0 16px}
.iaw-form .btn:disabled{opacity:.45;cursor:not-allowed}
.iaw-nota{font-size:11px;color:var(--g4);padding:2px 16px 12px;background:#fff}
.iaw-q{margin-top:8px;font-size:12px}.iaw-q summary{cursor:pointer;color:var(--g4);font-weight:600}
.iaw-q pre{background:#fff;border:1px solid var(--g2);border-radius:8px;padding:8px 10px;margin:6px 0 0;font-size:11.5px;white-space:pre-wrap;word-break:break-word}
.iaw-q .e{color:var(--bad)}
@media (max-width:600px){.iaw-fab{right:16px;bottom:16px}.iaw{width:100vw}}
@media (prefers-reduced-motion:reduce){.iaw,.iaw-fab,.iaw-fondo{transition:none}}
</style>

<button type="button" class="iaw-fab" id="iawFab" aria-label="Abrir asistente IA" title="Asistente IA">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/><path d="M9 11.5h.01M12 11.5h.01M15 11.5h.01" stroke-width="2.6"/>
  </svg>
</button>
<div class="iaw-fondo" id="iawFondo"></div>

<div class="iaw" id="iaw" role="dialog" aria-label="Asistente IA" aria-hidden="true">
  <div class="iaw-top">
    <i>🤖</i>
    <b>Asistente IA<small>Consulta tu sistema en tiempo real</small></b>
    <button type="button" id="iawNueva" title="Nueva conversación">Nueva</button>
    <button type="button" id="iawCerrar" aria-label="Cerrar" title="Cerrar (Esc)">✕</button>
  </div>
  <?php if (!$iaListo): ?>
  <div class="iaw-aviso"><b>Falta la clave de Gemini.</b> Copia <code>config/gemini.local.php.example</code> como <code>config/gemini.local.php</code>, pega tu clave de <b>aistudio.google.com/apikey</b> y recarga.</div>
  <?php endif; ?>
  <div class="iaw-msgs" id="iawMsgs"></div>
  <div class="iaw-sug" id="iawSug">
    <?php foreach ($iaSug as $s): ?><button type="button"><?= e($s) ?></button><?php endforeach; ?>
  </div>
  <div class="iaw-form">
    <textarea id="iawQ" rows="1" maxlength="500" placeholder="Escribe tu pregunta… (Enter envía)"></textarea>
    <button class="btn y" type="button" id="iawGo">Enviar</button>
  </div>
  <div class="iaw-nota">La IA puede equivocarse: confirma las cifras importantes en las pantallas del sistema.</div>
</div>

<script>
(function () {
  var URL_PREGUNTA = <?= json_encode(Controller::url('asistente/preguntar')) ?>, CSRF = <?= json_encode(Controller::csrf()) ?>;
  var fab = document.getElementById('iawFab'), panel = document.getElementById('iaw'), fondo = document.getElementById('iawFondo');
  var msgs = document.getElementById('iawMsgs'), q = document.getElementById('iawQ'), go = document.getElementById('iawGo'), sug = document.getElementById('iawSug');
  var hist = [], ocupado = false;
  var BIENVENIDA = 'Hola, soy el asistente de Tiendas Mass. Consulto la base de datos del sistema (solo lectura) para responderte: ventas, stock, empleados, asistencia, cajas, proveedores, incidentes… de cualquier fecha. ¿Qué necesitas?';

  // La conversación sobrevive al cambiar de pantalla (solo en esta pestaña).
  function guardar() { try { sessionStorage.setItem('iaw_hist', JSON.stringify(hist.slice(-20))); } catch (e) {} }
  function cargar() { try { var h = JSON.parse(sessionStorage.getItem('iaw_hist') || '[]'); return Array.isArray(h) ? h : []; } catch (e) { return []; } }
  function abierto(v) { try { if (v === undefined) return sessionStorage.getItem('iaw_open') === '1'; sessionStorage.setItem('iaw_open', v ? '1' : '0'); } catch (e) {} }

  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  function inline(s) { return s.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/`(.+?)`/g, '<code>$1</code>'); }
  function md(t) {
    var out = [], lista = false;
    esc(t).split('\n').forEach(function (l) {
      var m = l.match(/^\s*[-*•]\s+(.*)$/);
      if (m) {
        if (!lista) { out.push('<ul>'); lista = true; }
        out.push('<li>' + inline(m[1]) + '</li>');
      } else {
        if (lista) { out.push('</ul>'); lista = false; }
        if (l.trim() !== '') out.push('<p>' + inline(l) + '</p>');
      }
    });
    if (lista) out.push('</ul>');
    return out.join('');
  }
  function consultasHtml(c) {
    if (!c || !c.length) return '';
    return '<details class="iaw-q"><summary>Ver consultas usadas (' + c.length + ')</summary>' + c.map(function (x) {
      return '<pre>' + esc(x.sql) + '\n→ ' + (x.error ? '<span class="e">' + esc(x.error) + '</span>' : x.filas + ' fila(s)') + '</pre>';
    }).join('') + '</details>';
  }
  function add(clase, html) {
    var d = document.createElement('div');
    d.className = 'iaw-m ' + clase; d.innerHTML = html;
    msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
    return d;
  }
  function pintar() {
    msgs.innerHTML = '';
    add('bot', '<p>' + esc(BIENVENIDA) + '</p>');
    hist.forEach(function (h) { h.rol === 'user' ? add('user', esc(h.texto)) : add('bot', md(h.texto)); });
    sug.style.display = hist.length ? 'none' : '';
  }

  function abrir() {
    panel.classList.add('on'); fondo.classList.add('on'); fab.classList.add('oculto');
    panel.setAttribute('aria-hidden', 'false'); abierto(true);
    setTimeout(function () { q.focus(); msgs.scrollTop = msgs.scrollHeight; }, 280);
  }
  function cerrar() {
    panel.classList.remove('on'); fondo.classList.remove('on'); fab.classList.remove('oculto');
    panel.setAttribute('aria-hidden', 'true'); abierto(false); fab.focus();
  }

  function enviar(texto) {
    texto = (texto || q.value).trim();
    if (!texto || ocupado) return;
    ocupado = true; go.disabled = true; q.value = ''; q.style.height = ''; sug.style.display = 'none';
    add('user', esc(texto));
    var espera = add('bot', '<span class="iaw-dots"><i></i><i></i><i></i></span>');
    var fd = new FormData();
    fd.append('csrf', CSRF); fd.append('mensaje', texto); fd.append('historial', JSON.stringify(hist.slice(-8)));
    fetch(URL_PREGUNTA, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (t) {
        var d;
        try { d = JSON.parse(t); } catch (e) { throw new Error(t.replace(/<[^>]*>/g, ' ').trim().slice(0, 160) || 'Respuesta inesperada del servidor.'); }
        if (!d.ok) throw new Error(d.error || 'Error desconocido.');
        espera.innerHTML = md(d.respuesta) + consultasHtml(d.consultas);
        hist.push({ rol: 'user', texto: texto }, { rol: 'model', texto: d.respuesta });
        guardar();
      })
      .catch(function (e) { espera.className = 'iaw-m bot err'; espera.textContent = e.message; })
      .then(function () { ocupado = false; go.disabled = false; q.focus(); msgs.scrollTop = msgs.scrollHeight; });
  }

  fab.onclick = abrir;
  fondo.onclick = cerrar;
  document.getElementById('iawCerrar').onclick = cerrar;
  document.getElementById('iawNueva').onclick = function () { if (!ocupado) { hist = []; guardar(); pintar(); q.focus(); } };
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('on')) cerrar(); });
  go.onclick = function () { enviar(); };
  q.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); } });
  q.addEventListener('input', function () { q.style.height = 'auto'; q.style.height = Math.min(q.scrollHeight, 110) + 'px'; });
  sug.addEventListener('click', function (e) { if (e.target.tagName === 'BUTTON') enviar(e.target.textContent); });

  hist = cargar(); pintar();
  if (abierto()) abrir();
})();
</script>
