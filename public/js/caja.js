(() => {
  const $ = id => document.getElementById(id);
  const page = document.querySelector('.cj-page');
  if (!page) return;

  const MES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = n => 'S/ ' + Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const fdt = s => +s.slice(8, 10) + ' ' + MES[+s.slice(5, 7) - 1] + ', ' + s.slice(11, 16);
  const dur = (a, b) => {
    const m = Math.max(0, Math.round((new Date(b.replace(' ', 'T')) - new Date(a.replace(' ', 'T'))) / 60000));
    return Math.floor(m / 60) + ' h ' + String(m % 60).padStart(2, '0') + ' min';
  };

  const form = $('cjFiltros');
  document.querySelectorAll('#cjFiltros select, select[form="cjFiltros"]').forEach(el =>
    el.addEventListener('change', () => form.requestSubmit()));
  ['cjDesde', 'cjHasta'].forEach(id => $(id).addEventListener('change', () => {
    $('cjP').value = '';
    form.requestSubmit();
  }));

  document.querySelectorAll('dialog.cj-d [data-x]').forEach(b =>
    b.addEventListener('click', () => b.closest('dialog').close()));

  const dOpen = $('cjDOpen');
  const dClose = $('cjDClose');
  const dNew = $('cjDNew');

  function abrirCaja(el) {
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
  if ($('cjNueva')) $('cjNueva').addEventListener('click', () => dNew.showModal());

  const pre = new URLSearchParams(location.search).get('abrir');
  if (pre) {
    const b = document.querySelector('[data-abrir][data-id="' + CSS.escape(pre) + '"]');
    if (b) abrirCaja(b);
  }

  const drawer = $('cjDrawer');
  const ov = $('cjOv');
  let last = null;

  function pintar(t) {
    const abierto = t.st === 'ABIERTA';
    const esperado = Number(t.ini) + Number(t.ef);
    $('cjTitle').textContent = 'Turno #' + t.id;
    $('cjChip').innerHTML = '<span class="cj-st ' + (abierto ? 'on' : '') + '">' + (abierto ? 'Abierto' : 'Cerrado') + '</span>';

    let arqueo = '<div class="cj-lin"><span>Monto inicial</span><span>' + money(t.ini) + '</span></div>'
      + '<div class="cj-lin"><span>+ Ventas en efectivo</span><span>' + money(t.ef) + '</span></div>'
      + '<div class="cj-lin g"><span>Efectivo esperado</span><span>' + money(esperado) + '</span></div>';
    if (!abierto) {
      const d = Math.round(Number(t.dif) * 100) / 100;
      const txt = d === 0 ? 'Cuadra' : (d < 0 ? 'Falta ' : 'Sobra ') + money(Math.abs(d));
      const cls = d === 0 ? 'ok' : (d < 0 ? 'bad' : 'sob');
      arqueo += '<div class="cj-lin"><span>Efectivo contado</span><span>' + money(t.mf) + '</span></div>'
        + '<div class="cj-lin"><span>Diferencia</span><span class="cj-dif ' + cls + '">' + txt + '</span></div>';
    }

    $('cjBody').innerHTML =
      '<div class="cj-meta">'
      + '<div><small>Sucursal</small>' + esc(t.suc) + '</div>'
      + '<div><small>Caja</small>' + esc(t.caja) + '</div>'
      + '<div><small>Responsable</small>' + esc(t.resp) + '</div>'
      + '<div><small>Duración</small>' + (abierto ? 'En curso' : dur(t.ap, t.ci)) + '</div>'
      + '<div><small>Apertura</small>' + fdt(t.ap) + '</div>'
      + '<div><small>Cierre</small>' + (t.ci ? fdt(t.ci) : '—') + '</div>'
      + '</div>'
      + '<div class="cj-bloque"><h4>Ventas del turno · ' + t.n + '</h4>'
      + '<div class="cj-lin"><span>Efectivo</span><span>' + money(t.ef) + '</span></div>'
      + '<div class="cj-lin"><span>Yape</span><span>' + money(t.ya) + '</span></div>'
      + '<div class="cj-lin"><span>Plin</span><span>' + money(t.pl) + '</span></div>'
      + '<div class="cj-lin"><span>Tarjeta</span><span>' + money(t.ta) + '</span></div>'
      + '<div class="cj-lin g"><span>Total</span><span>' + money(t.tot) + '</span></div></div>'
      + '<div class="cj-bloque"><h4>Arqueo</h4>' + arqueo + '</div>';

    $('cjFoot').innerHTML = '<a class="btn g" href="' + drawer.dataset.ventas + '&turno=' + t.id + '">Ver ventas del turno</a>'
      + (abierto ? '<button type="button" class="btn y" id="cjFootCerrar">Cerrar caja</button>' : '');
    const bc = $('cjFootCerrar');
    if (bc) bc.addEventListener('click', () => {
      cerrar();
      cerrarCaja({hid: t.id, suc: t.suc, caja: t.caja, ap: fdt(t.ap), ini: t.ini, ef: t.ef});
    });
  }

  function abrir(tr) {
    last = tr;
    pintar(JSON.parse(tr.dataset.t));
    drawer.classList.add('on');
    ov.classList.add('on');
    drawer.setAttribute('aria-hidden', 'false');
    $('cjClose').focus();
  }

  function cerrar() {
    drawer.classList.remove('on');
    ov.classList.remove('on');
    drawer.setAttribute('aria-hidden', 'true');
    if (last) last.focus();
  }

  document.querySelectorAll('.cj-tr').forEach(tr => {
    tr.addEventListener('click', () => abrir(tr));
    tr.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        abrir(tr);
      }
    });
  });
  $('cjClose').addEventListener('click', cerrar);
  ov.addEventListener('click', cerrar);
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && drawer.classList.contains('on')) cerrar();
  });
})();