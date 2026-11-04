(() => {
  const $ = id => document.getElementById(id);
  const page = document.querySelector('.cj-page');
  if (!page) return;

  const MES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = n => 'S/ ' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const fdt = s => +s.slice(8, 10) + ' ' + MES[+s.slice(5, 7) - 1] + ', ' + s.slice(11, 16);
  const cap = s => s.charAt(0) + s.slice(1).toLowerCase();

  /* Filtros: se aplican solos al elegir un chip o una fecha */
  const form = $('cjFiltros');
  document.querySelectorAll('#cjFiltros input[type=radio], #cjFiltros input[type=date], select[form="cjFiltros"]').forEach(el =>
    el.addEventListener('change', () => form.requestSubmit()));

  /* Ventanas de abrir, cerrar y nueva caja */
  document.querySelectorAll('dialog.cj-d [data-x]').forEach(b =>
    b.addEventListener('click', () => b.closest('dialog').close()));

  const dOpen = $('cjDOpen');
  const dClose = $('cjDClose');
  const dNew = $('cjDNew');

  function abrirCaja(el) {
    if (!dOpen) return;
    $('cjOpCaja').value = el.dataset.id;
    $('cjOpTitle').textContent = el.dataset.suc + ' · ' + el.dataset.nombre;
    dOpen.showModal();
  }

  function cerrarCaja(d) {
    const esperado = Number(d.ini) + Number(d.ef);
    $('cjClHid').value = d.hid;
    $('cjClTitle').textContent = d.suc + ' · ' + d.caja;
    $('cjClSum').innerHTML =
      '<div class="cj-lin"><span>Apertura</span><span>' + esc(d.ap) + '</span></div>'
      + '<div class="cj-lin"><span>Monto inicial</span><span>' + money(d.ini) + '</span></div>'
      + '<div class="cj-lin"><span>Ventas en efectivo</span><span>' + money(d.ef) + '</span></div>'
      + '<div class="cj-lin g"><span>Efectivo esperado</span><span>' + money(esperado) + '</span></div>';
    const inp = $('cjClContado');
    inp.value = '';
    inp.dataset.esperado = esperado;
    $('cjClDif').textContent = '';
    $('cjClDif').className = 'cj-diffline';
    dClose.showModal();
    inp.focus();
  }

  $('cjClContado').addEventListener('input', e => {
    const out = $('cjClDif');
    if (e.target.value === '') {
      out.textContent = '';
      out.className = 'cj-diffline';
      return;
    }
    const d = Math.round((Number(e.target.value) - Number(e.target.dataset.esperado)) * 100) / 100;
    if (d === 0) {
      out.textContent = 'Cuadra';
      out.className = 'cj-diffline ok';
    } else if (d < 0) {
      out.textContent = 'Falta ' + money(Math.abs(d));
      out.className = 'cj-diffline bad';
    } else {
      out.textContent = 'Sobra ' + money(d);
      out.className = 'cj-diffline sob';
    }
  });

  document.querySelectorAll('[data-abrir]').forEach(b => b.addEventListener('click', () => abrirCaja(b)));
  document.querySelectorAll('[data-cerrar]').forEach(b => b.addEventListener('click', () => cerrarCaja(b.dataset)));
  if ($('cjNueva') && dNew) $('cjNueva').addEventListener('click', () => dNew.showModal());

  const pre = new URLSearchParams(location.search).get('abrir');
  if (pre) {
    const b = document.querySelector('[data-abrir][data-id="' + CSS.escape(pre) + '"]');
    if (b) abrirCaja(b);
  }

  /* Drawer con las ventas de una caja */
  const drawer = $('cjDrawer');
  const ov = $('cjOv');
  let last = null;
  let ventas = [];

  function listar() {
    const t = document.querySelector('#cjDrawer input[name=dt]:checked').value;
    const p = document.querySelector('#cjDrawer input[name=dp]:checked').value;
    const lista = ventas.filter(v => (!t || v.tipo === t) && (!p || String(v.pagos || '').split(', ').includes(p)));
    const emitidas = a => a.filter(v => v.estado === 'EMITIDA');
    const suma = a => emitidas(a).reduce((s, v) => s + Number(v.total), 0);

    $('cjBody').innerHTML = lista.length
      ? lista.map(v =>
          '<div class="cj-vta' + (v.estado === 'ANULADA' ? ' anu' : '') + '"><div><b>' + esc(v.comprobante) + '</b>'
          + '<span class="cj-vchip ' + (v.tipo === 'FACTURA' ? 'fac' : 'bol') + '">' + cap(v.tipo) + '</span>'
          + (v.estado === 'ANULADA' ? '<span class="cj-vchip anu">Anulada</span>' : '')
          + '<br><small>' + esc(v.cliente) + ' · ' + esc(String(v.pagos || '—').split(', ').map(cap).join(', ')) + ' · ' + fdt(v.fecha) + '</small></div>'
          + '<div class="t">' + money(v.total) + '</div></div>').join('')
      : '<div class="empty">No hay ventas con estos filtros.</div>';

    $('cjSumT').textContent = money(suma(ventas));
    $('cjSumN').textContent = emitidas(ventas).length;
    $('cjSumF').textContent = money(suma(lista));
  }

  async function abrirVentas(el) {
    try {
      const r = await fetch(drawer.dataset.url + '&id=' + el.dataset.hid, {headers: {Accept: 'application/json'}});
      if (!r.ok) throw new Error();
      ventas = (await r.json()).ventas || [];
    } catch (e) {
      alert('No se pudieron cargar las ventas. Recarga la página e inténtalo otra vez.');
      return;
    }
    last = el;
    $('cjTitle').textContent = el.dataset.titulo;
    drawer.querySelectorAll('input[value=""]').forEach(i => { i.checked = true; });
    listar();
    drawer.classList.add('on');
    ov.classList.add('on');
    drawer.setAttribute('aria-hidden', 'false');
    $('cjClose').focus();
  }

  function cerrarDrawer() {
    drawer.classList.remove('on');
    ov.classList.remove('on');
    drawer.setAttribute('aria-hidden', 'true');
    if (last) last.focus();
  }

  drawer.querySelectorAll('input[type=radio]').forEach(i => i.addEventListener('change', listar));
  document.querySelectorAll('[data-ventas]').forEach(b => b.addEventListener('click', () => abrirVentas(b)));
  document.querySelectorAll('.cj-tr').forEach(tr => {
    tr.addEventListener('click', () => abrirVentas(tr));
    tr.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        abrirVentas(tr);
      }
    });
  });
  $('cjClose').addEventListener('click', cerrarDrawer);
  ov.addEventListener('click', cerrarDrawer);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && drawer.classList.contains('on')) cerrarDrawer();
  });
})();