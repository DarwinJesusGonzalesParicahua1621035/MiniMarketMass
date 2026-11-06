<?php
// Chat con IA para el administrador. Variables: $listo (hay clave de Gemini), $url (donde se envía la pregunta)
$sugerencias = [
    '¿Cuánto vendimos hoy en cada sucursal?',
    '¿Qué productos están agotados o con stock bajo?',
    'Top 5 de productos más vendidos del mes',
    'Compara esta semana con la anterior',
    '¿Qué debería reponer primero?',
];
?>
<style>
.ia{display:flex;flex-direction:column;max-width:920px;height:calc(100vh - 130px);min-height:440px}
.ia-aviso{margin-bottom:14px;background:#FFF8DD;border-color:#F3DB85;font-size:14px;line-height:1.55}
.ia-aviso code{background:#fff;border:1px solid var(--g2);border-radius:6px;padding:1px 6px;font-size:13px}
.ia-aviso pre{background:#fff;border:1px solid var(--g2);border-radius:10px;padding:10px 12px;margin:8px 0;font-size:13px;overflow:auto}
.ia-msgs{flex:1;overflow-y:auto;background:#fff;border:1px solid var(--g2);border-radius:var(--r);padding:18px;display:flex;flex-direction:column;gap:12px}
.ia-m{max-width:82%;padding:11px 15px;border-radius:16px;font-size:14px;line-height:1.55;word-wrap:break-word}
.ia-m p{margin:0 0 6px}.ia-m p:last-child{margin-bottom:0}
.ia-m ul{margin:4px 0 6px 18px}.ia-m li{margin-bottom:3px}
.ia-m code{background:rgba(0,0,0,.07);border-radius:5px;padding:0 5px}
.ia-m.user{align-self:flex-end;background:var(--k);color:#fff;border-bottom-right-radius:5px;white-space:pre-wrap}
.ia-m.bot{align-self:flex-start;background:var(--g1);color:var(--k);border-bottom-left-radius:5px}
.ia-m.err{background:#fbe5e5;color:var(--bad)}
.ia-dots i{display:inline-block;width:7px;height:7px;margin:0 2px;border-radius:50%;background:var(--g3);animation:iaD 1s infinite}
.ia-dots i:nth-child(2){animation-delay:.15s}.ia-dots i:nth-child(3){animation-delay:.3s}
@keyframes iaD{0%,60%,100%{opacity:.25;transform:translateY(0)}30%{opacity:1;transform:translateY(-3px)}}
.ia-sug{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 4px}
.ia-sug button{border:1px solid var(--g2);background:#fff;border-radius:99px;padding:7px 14px;font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.ia-sug button:hover{border-color:var(--y);background:var(--y2)}
.ia-form{display:flex;gap:10px;margin-top:8px;align-items:flex-end}
.ia-form textarea{flex:1;resize:none;border:1px solid var(--g2);border-radius:14px;padding:12px 14px;font:inherit;font-size:14px;outline:none;max-height:120px;background:#fff}
.ia-form textarea:focus{border-color:var(--b);box-shadow:0 0 0 3px var(--b2)}
.ia-form .btn{height:46px;padding:0 22px}
.ia-form .btn:disabled{opacity:.45;cursor:not-allowed}
.ia-q{margin-top:8px;font-size:12px}.ia-q summary{cursor:pointer;color:var(--g4);font-weight:600}
.ia-q pre{background:#fff;border:1px solid var(--g2);border-radius:8px;padding:8px 10px;margin:6px 0 0;font-size:11.5px;white-space:pre-wrap;word-break:break-word}
.ia-q .e{color:var(--bad)}
.ia-nota{font-size:11.5px;color:var(--g4);margin-top:8px}
.ia-nota a{color:var(--b);cursor:pointer;font-weight:600}
</style>

<?php if (!$listo): ?>
<div class="card ia-aviso">
  <b>Falta conectar la clave de Gemini.</b>
  <ol style="margin:6px 0 0 18px">
    <li>Entra a <b>aistudio.google.com/apikey</b> y crea una clave (botón «Obtener clave de API»).</li>
    <li>Copia <code>config/gemini.local.php.example</code> como <code>config/gemini.local.php</code> y pega tu clave:
      <pre>&lt;?php
return ['key' =&gt; 'TU_CLAVE_AQUI'];</pre>
    </li>
    <li>Recarga esta página.</li>
  </ol>
</div>
<?php endif; ?>

<div class="ia">
  <div class="ia-msgs" id="iaMsgs">
    <div class="ia-m bot">
      <p>Hola, soy el asistente de Tiendas Mass. Consulto la base de datos del sistema (solo lectura) para responderte:</p>
      <ul><li>ventas, stock, mermas y reposiciones, de cualquier fecha</li><li>empleados, asistencia y capacitaciones</li><li>cajas, proveedores, productos, precios e incidentes</li><li>clientes (solo cifras, sin datos personales)</li></ul>
      <p>Pregúntame lo que necesites.</p>
    </div>
  </div>

  <div class="ia-sug" id="iaSug">
    <?php foreach ($sugerencias as $s): ?><button type="button"><?= e($s) ?></button><?php endforeach; ?>
  </div>

  <div class="ia-form">
    <textarea id="iaQ" rows="1" maxlength="500" placeholder="Escribe tu pregunta… (Enter envía, Shift+Enter salto de línea)"></textarea>
    <button class="btn y" type="button" id="iaGo">Enviar</button>
  </div>
  <div class="ia-nota">La IA puede equivocarse: confirma las cifras importantes en las pantallas del sistema. · <a id="iaNueva">Nueva conversación</a></div>
</div>

<script>
(function () {
  var URL_PREGUNTA = <?= json_encode($url) ?>, CSRF = <?= json_encode(Controller::csrf()) ?>;
  var msgs = document.getElementById('iaMsgs'), q = document.getElementById('iaQ'), go = document.getElementById('iaGo');
  var inicio = msgs.innerHTML, hist = [], ocupado = false;

  function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  function inline(s) { return s.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/`(.+?)`/g, '<code>$1</code>'); }
  // Texto de la IA -> HTML seguro (primero se escapa todo, después se aplican negritas y listas)
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
    return '<details class="ia-q"><summary>Ver consultas usadas (' + c.length + ')</summary>' + c.map(function (x) {
      return '<pre>' + esc(x.sql) + '\n→ ' + (x.error ? '<span class="e">' + esc(x.error) + '</span>' : x.filas + ' fila(s)') + '</pre>';
    }).join('') + '</details>';
  }
  function add(clase, html) {
    var d = document.createElement('div');
    d.className = 'ia-m ' + clase; d.innerHTML = html;
    msgs.appendChild(d); msgs.scrollTop = msgs.scrollHeight;
    return d;
  }

  function enviar(texto) {
    texto = (texto || q.value).trim();
    if (!texto || ocupado) return;
    ocupado = true; go.disabled = true; q.value = ''; q.style.height = '';
    add('user', esc(texto));
    var espera = add('bot', '<span class="ia-dots"><i></i><i></i><i></i></span>');
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
      })
      .catch(function (e) { espera.className = 'ia-m bot err'; espera.textContent = e.message; })
      .then(function () { ocupado = false; go.disabled = false; q.focus(); msgs.scrollTop = msgs.scrollHeight; });
  }

  go.onclick = function () { enviar(); };
  q.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); } });
  q.addEventListener('input', function () { q.style.height = 'auto'; q.style.height = Math.min(q.scrollHeight, 120) + 'px'; });
  document.getElementById('iaSug').addEventListener('click', function (e) { if (e.target.tagName === 'BUTTON') enviar(e.target.textContent); });
  document.getElementById('iaNueva').onclick = function () { if (!ocupado) { hist = []; msgs.innerHTML = inicio; } };
})();
</script>
