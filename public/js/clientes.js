(() => {
  const root = document.getElementById('cl');
  if (!root) return;

  const $ = (id) => document.getElementById(id);
  const panel = $('cl-panel'), det = $('cl-det'), body = $('cl-dbody');
  const drawer = $('cl-drawer'), drop = $('cl-drop'), form = $('cl-form');
  const tbody = document.querySelector('#cl-tabla tbody');
  const D = root.dataset;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = (n) => 'S/ ' + Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const fecha = (s) => (s ? s.slice(8, 10) + '/' + s.slice(5, 7) + '/' + s.slice(0, 4) : '—');
  const iniciales = (n) => { const p = n.trim().split(/\s+/); return ((p[0] || '')[0] + (p[1] ? p[1][0] : '')).toUpperCase(); };
  const ESTADO = { VIP: ['y', 'VIP'], ACTIVO: ['ok', 'Activo'], INACTIVO: ['bad', 'Inactivo'] };

  /* ---------- Filtros: búsqueda + chips de estado ---------- */
  let filtroEstado = '';
  const filtrar = () => {
    const q = $('cl-q').value.trim().toLowerCase();
    let vis = 0;
    tbody.querySelectorAll('tr').forEach((tr) => {
      const ok = (!filtroEstado || tr.dataset.estado === filtroEstado) &&
                 (!q || (tr.dataset.nombre + ' ' + tr.dataset.doc).toLowerCase().includes(q));
      tr.hidden = !ok;
      if (ok) vis++;
    });
    $('cl-vacio').hidden = vis > 0;
  };
  $('cl-q').addEventListener('input', filtrar);
  $('cl-chips').addEventListener('click', (e) => {
    const b = e.target.closest('button'); if (!b) return;
    document.querySelectorAll('#cl-chips button').forEach((x) => x.classList.toggle('on', x === b));
    filtroEstado = b.dataset.f; filtrar();
  });

  /* ---------- Informe personal (panel superpuesto) ---------- */
  const cache = new Map();
  let actual = 0;

  const barras = (meses) => {
    const max = Math.max(1, ...meses.map((m) => m.v));
    return '<div class="cl-bars">' + meses.map((m) =>
      `<div><b>${m.v > 0 ? 'S/ ' + Math.round(m.v) : ''}</b><span style="height:${Math.max(3, m.v / max * 100)}%"></span><em>${esc(m.l)}</em></div>`).join('') + '</div>';
  };

  const pintar = (c) => {
    const [cls, txt] = ESTADO[c.estado] || ['', c.estado];
    const tickets = c.tickets.length
      ? `<table class="cl-tk"><thead><tr><th>Fecha</th><th>Monto</th><th>Ítems</th><th></th></tr></thead><tbody>` +
        c.tickets.map((t) => `<tr><td>${fecha(t.fecha)}</td><td>${money(t.total)}</td><td>${t.items}</td>
          <td><a href="${esc(D.ver)}&id=${t.id}" title="${esc(t.tipo_comprobante)} ${esc(t.serie)}-${esc(t.correlativo)}">Ver</a></td></tr>`).join('') +
        '</tbody></table>'
      : '<p class="cl-hint">Este cliente aún no tiene compras.</p>';

    body.innerHTML = `
      <div class="cl-id">
        <i class="av lg c${c.id % 6}">${esc(iniciales(c.nombre))}</i>
        <div><h3>${esc(c.nombre)}</h3><small>${esc(c.tipo_documento)} ${esc(c.numero_documento)}</small><span class="tag ${cls}">${txt}</span></div>
      </div>
      <div class="cl-kpis">
        <div><p>Total gastado</p><h4>${money(c.total)}</h4></div>
        <div><p>Compras</p><h4>${c.compras}</h4></div>
        <div><p>Ticket promedio</p><h4>${money(c.ticket)}</h4></div>
        <div><p>Última compra</p><h4>${fecha(c.ultima)}</h4></div>
      </div>
      <h5>Frecuencia de compra · 6 meses</h5>${barras(c.meses)}
      <h5>Últimos tickets</h5>${tickets}
      <h5>Acciones rápidas</h5>
      <div class="cl-qa">
        <button class="btn" data-qa="editar">✏️ Editar</button>
        <button class="btn d" data-qa="eliminar">🗑️ Eliminar</button>
      </div>`;
  };

  const marcar = (id) => tbody.querySelectorAll('tr').forEach((tr) => tr.classList.toggle('sel', +tr.dataset.id === id));

  const abrirInforme = async (id) => {
    actual = id; marcar(id);
    panel.classList.add('open'); det.setAttribute('aria-hidden', 'false');
    det.scrollTop = 0;
    if (cache.has(id)) return pintar(cache.get(id));
    body.innerHTML = '<div class="cl-load">Cargando informe…</div>';
    try {
      const r = await fetch(D.detalle + '&id=' + id, { headers: { Accept: 'application/json' } });
      if (!r.ok) throw 0;
      const c = await r.json();
      cache.set(id, c);
      if (actual === id) pintar(c);
    } catch { if (actual === id) body.innerHTML = '<div class="cl-fail">No se pudo cargar el informe.</div>'; }
  };
  const cerrarInforme = () => {
    actual = 0; marcar(0);
    panel.classList.remove('open'); det.setAttribute('aria-hidden', 'true');
  };

  tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr'); if (!tr) return;
    if (e.target.closest('[data-editar]')) return abrirDrawer(tr.dataset);
    if (e.target.closest('form')) return;           // eliminar usa su propio formulario
    abrirInforme(+tr.dataset.id);
  });
  tbody.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.tagName === 'TR') abrirInforme(+e.target.dataset.id);
  });
  document.querySelector('.cl-top')?.addEventListener('click', (e) => {
    const li = e.target.closest('[data-abrir]'); if (li) abrirInforme(+li.dataset.abrir);
  });
  document.querySelector('.cl-top')?.addEventListener('keydown', (e) => {
    const li = e.target.closest('[data-abrir]'); if (li && e.key === 'Enter') abrirInforme(+li.dataset.abrir);
  });
  $('cl-back').onclick = $('cl-cerrar').onclick = cerrarInforme;

  body.addEventListener('click', (e) => {
    const b = e.target.closest('[data-qa]'); if (!b) return;
    const c = cache.get(actual); if (!c) return;
    if (b.dataset.qa === 'editar') return abrirDrawer({ id: c.id, tipo: c.tipo_documento, doc: c.numero_documento, nombre: c.nombre });
    if (!confirm('¿Eliminar a ' + c.nombre + '?')) return;
    const f = document.createElement('form');
    f.method = 'post'; f.action = D.eliminar;
    f.innerHTML = `<input name="csrf" value="${esc(D.csrf)}"><input name="id" value="${c.id}">`;
    document.body.appendChild(f); f.submit();
  });

  /* ---------- Drawer: crear / editar ---------- */
  const ajustarDoc = () => {
    const n = $('f-tipo').value === 'RUC' ? 11 : 8;
    $('f-doc').maxLength = n; $('f-doc').pattern = '\\d{' + n + '}';
    $('f-doc').title = 'Debe tener ' + n + ' dígitos';
    $('f-ayuda').textContent = '(' + n + ' dígitos)';
  };
  $('f-tipo').addEventListener('change', ajustarDoc);
  $('f-doc').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, ''); });

  function abrirDrawer(d) {
    const nuevo = !d || !d.id;
    $('cl-dtitulo').textContent = nuevo ? 'Nuevo cliente' : 'Editar cliente';
    $('cl-dsub').textContent = nuevo ? 'Registra un cliente con DNI o RUC' : d.nombre;
    $('f-id').value = nuevo ? 0 : d.id;
    $('f-tipo').value = nuevo ? 'DNI' : d.tipo;
    $('f-doc').value = nuevo ? '' : d.doc;
    $('f-nombre').value = nuevo ? '' : d.nombre;
    ajustarDoc();
    drop.hidden = false; drawer.classList.add('open'); drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    setTimeout(() => $('f-nombre').focus(), 250);
  }
  function cerrarDrawer() {
    drawer.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true');
    drop.hidden = true; document.body.style.overflow = '';
  }
  $('cl-nuevo').onclick = () => abrirDrawer(null);
  $('cl-dclose').onclick = $('cl-dcancel').onclick = drop.onclick = cerrarDrawer;

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    if (drawer.classList.contains('open')) cerrarDrawer(); else if (actual) cerrarInforme();
  });
})();