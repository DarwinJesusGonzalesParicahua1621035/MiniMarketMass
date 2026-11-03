(() => {
  const $ = id => document.getElementById(id);
  const drawer = $('vtDrawer');
  if (!drawer) return;
  const ov = $('vtOv');
  const page = document.querySelector('.vt-page');
  const MES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = n => 'S/ ' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const fdt = s => +s.slice(8, 10) + ' ' + MES[+s.slice(5, 7) - 1] + ', ' + s.slice(11, 16);
  const cap = s => s.charAt(0) + s.slice(1).toLowerCase();
  let last = null;

  const form = $('vtFiltros');
  form.querySelectorAll('select, input[type=checkbox]').forEach(el => el.addEventListener('change', () => form.requestSubmit()));
  $('vtDia').addEventListener('change', e => e.target.value && form.requestSubmit());

  function pintar(v) {
    $('vtTitle').textContent = v.comprobante;
    $('vtChip').innerHTML = '<span class="vt-chip ' + (v.tipo === 'FACTURA' ? 'fac' : 'bol') + '">' + cap(v.tipo) + '</span>'
      + (v.estado === 'ANULADA' ? ' <span class="vt-chip anu">Anulada</span>' : '');
    $('vtVer').href = drawer.dataset.ver + '&id=' + v.id;
    $('vtBody').innerHTML =
      '<div class="vt-meta">'
      + '<div><small>Fecha</small>' + fdt(v.fecha) + '</div>'
      + '<div><small>Sucursal</small>' + esc(v.sucursal) + '</div>'
      + '<div><small>Cliente</small>' + esc(v.cliente) + '</div>'
      + '<div><small>Caja</small>' + esc(v.caja) + '</div>'
      + '<div><small>Cajero</small>' + esc(v.cajero) + '</div>'
      + '<div><small>Pago</small>' + v.pagos.map(p => cap(p.medio_pago) + ' ' + money(p.monto)).map(esc).join('<br>') + '</div>'
      + '</div>'
      + (v.motivo ? '<div class="vt-motivo"><small>Motivo de anulación</small>' + esc(v.motivo) + '</div>' : '')
      + '<table class="vt-it"><thead><tr><th>Producto</th><th class="vt-num">Cant.</th><th class="vt-num">P. unit.</th><th class="vt-num">Importe</th></tr></thead><tbody>'
      + v.items.map(i => '<tr><td>' + esc(i.producto) + '</td><td class="vt-num">' + Number(i.cantidad) + '</td><td class="vt-num">'
        + money(i.precio_unitario) + '</td><td class="vt-num">' + money(i.total_linea) + '</td></tr>').join('')
      + '</tbody></table>'
      + '<div class="vt-tot"><div><span class="vt-mut">Subtotal</span><span>' + money(v.subtotal) + '</span></div>'
      + '<div><span class="vt-mut">IGV (18%)</span><span>' + money(v.igv) + '</span></div>'
      + '<div class="g"><span>Total</span><span>' + money(v.total) + '</span></div></div>';
  }

  async function abrir(id, btn) {
    try {
      const r = await fetch(drawer.dataset.url + '&id=' + id, {headers: {Accept: 'application/json'}});
      if (!r.ok) throw new Error();
      pintar(await r.json());
    } catch (e) {
      alert('No se pudo cargar el detalle. Recarga la página e inténtalo otra vez.');
      return;
    }
    last = btn;
    drawer.classList.add('on');
    ov.classList.add('on');
    drawer.setAttribute('aria-hidden', 'false');
    page.inert = true;
    $('vtClose').focus();
  }

  function cerrar() {
    drawer.classList.remove('on');
    ov.classList.remove('on');
    drawer.setAttribute('aria-hidden', 'true');
    page.inert = false;
    if (last) last.focus();
  }

  document.querySelector('.vt-page tbody').addEventListener('click', e => {
    const b = e.target.closest('[data-open]');
    const tr = e.target.closest('tr[data-id]');
    if (b) abrir(b.dataset.open, b);
    else if (tr) abrir(tr.dataset.id, tr.querySelector('[data-open]'));
  });
  $('vtClose').addEventListener('click', cerrar);
  ov.addEventListener('click', cerrar);
  $('vtPrint').addEventListener('click', () => window.print());
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && drawer.classList.contains('on')) cerrar();
  });
})();