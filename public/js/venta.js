// Punto de venta del cajero: productos, boleta (arrastrar o tocar), cobro y comprobante.
(function () {
  const C = window.POS;
  const IGV = 1.18;
  const $ = id => document.getElementById(id);
  const r2 = n => Math.round((n + Number.EPSILON) * 100) / 100;
  const money = n => 'S/ ' + Number(n).toFixed(2);
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const EMO = {Abarrotes: '🌾', 'Lácteos': '🥛', Bebidas: '🥤', Limpieza: '🧴', Snacks: '🍿'};
  const MEDIOS = [['EFECTIVO', '💵'], ['YAPE', '🟣'], ['PLIN', '🟦'], ['TARJETA', '💳']];
  const MAX_PAGOS = 2;
  const cap = s => s[0] + s.slice(1).toLowerCase();

  let cart = [], comp = 'BOLETA', cat = 'Todas', q = '', P = [], enviando = false;
  let cli = {doc: '', nombre: '', found: false};
  const prods = C.productos;
  const prod = id => prods.find(p => p.id === id);
  const total = () => r2(cart.reduce((a, l) => a + l.tl, 0));

  // ---------- Avisos y ventanas ----------
  function toast(t, bad) {
    const e = $('toast');
    e.textContent = t;
    e.className = 'on' + (bad ? ' bad' : '');
    clearTimeout(toast.t);
    toast.t = setTimeout(() => e.className = '', 2600);
  }
  const abrir = h => { $('mbox').innerHTML = h; $('modal').classList.add('on'); };
  const cerrar = () => $('modal').classList.remove('on');
  $('modal').addEventListener('click', e => { if (e.target.id === 'modal') cerrar(); });

  // ---------- Productos ----------
  function renderCats() {
    const cats = ['Todas', ...new Set(prods.map(p => p.categoria))];
    $('cats').innerHTML = cats.map(c => `<button type="button" class="${c === cat ? 'on' : ''}" data-cat="${esc(c)}">${esc(c)}</button>`).join('');
  }
  function filtrados() {
    const t = q.trim().toLowerCase();
    return prods.filter(p => (cat === 'Todas' || p.categoria === cat) &&
      (!t || p.codigo.includes(t) || p.nombre.toLowerCase().includes(t)));
  }
  function renderGrid() {
    const l = filtrados();
    $('grid').innerHTML = l.map(p => {
      const s = p.stock - enCarrito(p.id);
      const off = !p.activo || s <= 0;
      const estado = !p.activo ? '<span class="tag">Inactivo</span>'
        : p.stock <= 0 ? '<span class="tag bad">Sin stock</span>'
        : s <= 0 ? '<span class="tag y">Todo en la boleta</span>'
        : s < 10 ? `<span class="tag bad">${s} · stock bajo</span>` : 'Stock: ' + s;
      return `<div class="prod ${off ? 'off' : ''}" data-id="${p.id}" draggable="${off ? 'false' : 'true'}" tabindex="${off ? -1 : 0}" role="button" aria-disabled="${off}">
        <div class="ic">${EMO[p.categoria] || '📦'}</div><b>${esc(p.nombre)}</b>
        <span class="pr">${money(p.precio)}<small> /${esc(p.unidad)}</small></span>
        <small>${estado}</small></div>`;
    }).join('') || '<div class="vacio-g">Sin resultados</div>';
  }
  const enCarrito = id => { const l = cart.find(x => x.id === id); return l ? l.qty : 0; };

  // ---------- Boleta ----------
  function add(id, n) {
    const p = prod(id);
    if (!p) return;
    if (!p.activo) return toast(`${p.nombre} está inactivo`, true);
    const l = cart.find(x => x.id === id), paso = p.unidad === 'kg' ? 0.5 : 1, cant = n || paso, ya = l ? l.qty : 0;
    if (ya + cant > p.stock) return toast(`Stock insuficiente de ${p.nombre} (disp. ${p.stock})`, true);
    if (l) { l.qty = Math.round((l.qty + cant) * 1000) / 1000; l.tl = r2(l.qty * l.pu); }
    else cart.push({id, nombre: p.nombre, unidad: p.unidad, qty: cant, pu: p.precio, tl: r2(cant * p.precio)});
    renderLineas(); renderGrid();
  }
  function setQty(id, v) {
    const l = cart.find(x => x.id === id), p = prod(id);
    v = parseFloat(v);
    if (!(v > 0)) cart = cart.filter(x => x.id !== id);
    else {
      if (v > p.stock) { toast(`Stock insuficiente (disp. ${p.stock})`, true); v = p.stock; }
      if (l.unidad !== 'kg') v = Math.floor(v) || 1;
      l.qty = Math.round(v * 1000) / 1000; l.tl = r2(l.qty * l.pu);
    }
    renderLineas(); renderGrid();
  }

  function renderCart() {
    $('cartBox').innerHTML = `
      <div class="hd"><h3 id="ctitle"></h3><span class="tag b" id="cnt">0</span>
        <button class="btn g sm" id="vaciar" type="button">Vaciar</button></div>
      <div class="lines" id="lines"></div>
      <div class="cfg">
        <div class="seg"><button type="button" data-comp="BOLETA">Boleta ${esc(C.serieB)}</button><button type="button" data-comp="FACTURA">Factura ${esc(C.serieF)}</button></div>
        <div class="r2"><input class="inp" id="cdoc" inputmode="numeric" maxlength="11" autocomplete="off" value="${esc(cli.doc)}">
          <input class="inp" id="cnom" placeholder="Nombre / Razón social" autocomplete="off" value="${esc(cli.nombre)}" ${cli.found ? 'readonly' : ''}></div>
        <small id="cmsg"></small>
      </div>
      <div class="tot" id="tot"></div>
      <div class="dropmsg">⬇ Suelta aquí para agregar</div>`;
    renderComp();
    renderLineas();
  }
  function renderComp() {
    document.querySelectorAll('.seg button').forEach(b => b.classList.toggle('on', b.dataset.comp === comp));
    $('cdoc').placeholder = comp === 'FACTURA' ? 'RUC (11 dígitos)' : 'DNI / RUC';
    $('cmsg').textContent = cli.found ? '✔ Cliente registrado'
      : comp === 'FACTURA' ? 'La factura requiere cliente con RUC.' : 'Sin datos se emite como cliente genérico.';
    renderTitulo();
  }
  function renderTitulo() {
    const n = C.siguiente[comp], serie = comp === 'BOLETA' ? C.serieB : C.serieF;
    $('ctitle').textContent = `🧾 ${cap(comp)} ${serie}-${String(n).padStart(6, '0')}`;
  }
  function renderLineas() {
    const T = total(), sub = r2(T / IGV), igv = r2(T - sub), n = cart.length;
    $('cnt').textContent = n;
    $('vaciar').disabled = !n;
    $('lines').innerHTML = n ? cart.map(l => `<div class="ln"><b>${esc(l.nombre)}</b><span class="t">${money(l.tl)}</span>
        <span class="q">${money(l.pu)} ×<input type="number" min="0" step="${l.unidad === 'kg' ? '0.001' : '1'}" value="${l.qty}" data-qty="${l.id}"> ${esc(l.unidad)}</span>
        <button type="button" class="x" data-del="${l.id}" title="Quitar">✕</button></div>`).join('')
      : '<div class="empty-c">Arrastra un producto aquí<br>o tócalo para agregarlo.</div>';
    $('tot').innerHTML = `<div><span>Subtotal</span><span>${money(sub)}</span></div><div><span>IGV (18%)</span><span>${money(igv)}</span></div>
      <div class="T"><span>TOTAL</span><span class="tv">${money(T)}</span></div>
      <button class="btn y big" id="cobrar" type="button" ${n ? '' : 'disabled'}>Cobrar</button>`;
  }

  // ---------- Cliente ----------
  let busca = 0;
  function lookup(v) {
    v = v.replace(/\D/g, '');
    cli.doc = v;
    $('cdoc').value = v;
    const m = $('cmsg');
    if (cli.found) { cli.found = false; cli.nombre = ''; $('cnom').value = ''; $('cnom').readOnly = false; }
    m.textContent = (v.length === 8 || v.length === 11) ? 'Buscando…' : 'Escribe el DNI (8) o RUC (11).';
    if (v.length !== 8 && v.length !== 11) return;
    const mio = ++busca;
    fetch(C.urlCliente + '&doc=' + v, {credentials: 'same-origin'}).then(r => r.json()).then(r => {
      if (mio !== busca || cli.doc !== v) return;
      if (r.found) { cli.found = true; cli.nombre = r.nombre; $('cnom').value = r.nombre; $('cnom').readOnly = true; m.textContent = '✔ Cliente registrado'; }
      else m.textContent = 'Cliente nuevo: escribe el nombre y se registrará al cobrar.';
    }).catch(() => { m.textContent = 'No se pudo buscar el cliente.'; });
  }
  function validarCliente() {
    const d = cli.doc, nm = cli.nombre.trim();
    if (comp === 'FACTURA') {
      if (d.length !== 11) return 'La factura requiere un RUC de 11 dígitos.';
      if (!nm) return 'Falta la razón social del cliente.';
    } else if (d || nm) {
      if (d.length !== 8 && d.length !== 11) return 'El DNI debe tener 8 dígitos y el RUC 11.';
      if (!nm) return 'Falta el nombre del cliente.';
    }
    return '';
  }

  // ---------- Cobro ----------
  const pagado = () => r2(P.reduce((a, p) => a + (parseFloat(p.monto) || 0), 0));
  function estadoPago() {
    const T = total(), pd = pagado();
    const cash = r2(P.filter(p => p.m === 'EFECTIVO').reduce((a, p) => a + (parseFloat(p.monto) || 0), 0));
    const vuelto = Math.max(0, r2(pd - T)), falta = Math.max(0, r2(T - pd));
    const distintos = new Set(P.map(p => p.m)).size === P.length;
    return {T, pd, cash, vuelto, falta, ok: P.length > 0 && P.length <= MAX_PAGOS && distintos && pd >= T && vuelto <= cash && P.every(p => parseFloat(p.monto) > 0)};
  }
  function cobrar() {
    if (!cart.length) return;
    const err = validarCliente();
    if (err) return toast(err, true);
    P = [];
    const serie = comp === 'BOLETA' ? C.serieB : C.serieF;
    abrir(`<h3>Cobrar</h3><div class="big">${money(total())}</div>
      <small style="color:var(--g4)">${cap(comp)} ${esc(serie)}-${String(C.siguiente[comp]).padStart(6, '0')}</small>
      <div class="mp">${MEDIOS.map(m => `<button type="button" data-pay="${m[0]}">${m[1]}<br>${cap(m[0])}</button>`).join('')}</div>
      <div id="pl"></div><div class="sum" id="sum"></div>
      <div class="mrow"><button class="btn g" type="button" id="pcancel">Cancelar</button><button class="btn y" type="button" id="pok">Confirmar venta</button></div>`);
    addPay('EFECTIVO');
  }
  function addPay(m) {
    if (P.length >= MAX_PAGOS || P.some(p => p.m === m)) return;
    const falta = Math.max(0, r2(total() - pagado()));
    P.push({m, monto: falta > 0 ? falta : '', ref: ''});
    renderPay();
  }
  function renderPay() {
    $('pl').innerHTML = P.map((p, i) => `<div class="pl"><b style="font-size:13px">${cap(p.m)}</b>
      <input class="inp" type="number" step="0.01" min="0" value="${p.monto}" placeholder="Monto" data-monto="${i}">
      ${p.m === 'EFECTIVO' ? '<span style="font-size:12px;color:var(--g3)">Sin referencia</span>' : `<input class="inp" maxlength="30" placeholder="N.º operación" value="${esc(p.ref)}" data-ref="${i}">`}
      <button type="button" class="x" data-quitar="${i}">✕</button></div>`).join('')
      + (P.length >= MAX_PAGOS ? '<small style="color:var(--g4)">Máximo dos métodos de pago por venta.</small>' : '');
    document.querySelectorAll('[data-pay]').forEach(b => {
      b.disabled = P.length >= MAX_PAGOS || P.some(p => p.m === b.dataset.pay);
      b.style.opacity = b.disabled ? '.4' : '';
      b.style.cursor = b.disabled ? 'not-allowed' : '';
    });
    renderSuma();
  }
  function renderSuma() {
    const s = estadoPago();
    $('sum').innerHTML = `<div><span>Recibido</span><b>${money(s.pd)}</b></div>
      <div><span>Falta por cobrar</span><b class="${s.falta ? 'bad-t' : ''}">${money(s.falta)}</b></div>
      <div><span>Vuelto</span><span class="v ${s.vuelto > s.cash ? 'bad-t' : 'ok-t'}">${money(s.vuelto)}</span></div>
      ${s.vuelto > s.cash ? '<small class="bad-t">El vuelto solo puede salir del efectivo recibido.</small>' : ''}`;
    $('pok').disabled = !s.ok || enviando;
  }
  function confirmar() {
    if (!estadoPago().ok || enviando) return;
    enviando = true; $('pok').disabled = true; $('pok').textContent = 'Registrando…';
    const datos = {
      tipo: comp, doc: cli.doc, nombre: cli.nombre.trim(),
      items: cart.map(l => ({id: l.id, qty: l.qty})),
      pagos: P.map(p => ({m: p.m, monto: parseFloat(p.monto), ref: p.ref.trim()})),
    };
    const fd = new FormData();
    fd.append('csrf', C.csrf);
    fd.append('datos', JSON.stringify(datos));
    fetch(C.urlCobrar, {method: 'POST', body: fd, credentials: 'same-origin'})
      .then(r => r.json().catch(() => ({ok: false, error: 'Respuesta inválida del servidor. Recarga la página.'})))
      .then(r => {
        enviando = false;
        if (!r.ok) { $('pok').textContent = 'Confirmar venta'; renderSuma(); return toast(r.error || 'No se pudo registrar la venta.', true); }
        cart.forEach(l => { prod(l.id).stock = Math.round((prod(l.id).stock - l.qty) * 1000) / 1000; });
        C.siguiente = r.siguiente;
        cart = []; cli = {doc: '', nombre: '', found: false}; comp = 'BOLETA';
        renderCart(); renderGrid();
        ticket(r);
      })
      .catch(() => { enviando = false; $('pok').textContent = 'Confirmar venta'; renderSuma(); toast('Sin conexión con el servidor.', true); });
  }

  // ---------- Comprobante ----------
  function ticket(r) {
    const v = r.venta, f = n => Number(n).toFixed(2);
    abrir(`<h3>✅ Venta registrada</h3><div id="ticket">
      <div class="c"><b>TIENDAS MASS</b><br>${esc(v.sucursal)} · ${esc(v.direccion)}<br>${esc(v.tipo)} ELECTRÓNICA<br><b>${esc(v.comprobante)}</b></div><hr>
      Fecha: ${esc(v.fecha)}<br>Cajero: ${esc(v.cajero)} · ${esc(v.caja)}<br>Cliente: ${esc(v.cliente)}${v.doc ? '<br>' + esc(v.doc) : ''}<hr>
      ${r.items.map(i => `${esc(i.producto)}<div class="f"><span>${Number(i.cantidad)} × ${f(i.precio_unitario)}</span><span>${f(i.total_linea)}</span></div>`).join('')}<hr>
      <div class="f"><span>Subtotal</span><span>${f(v.subtotal)}</span></div><div class="f"><span>IGV 18%</span><span>${f(v.igv)}</span></div>
      <div class="f"><b>TOTAL</b><b>${money(v.total)}</b></div><hr>
      ${r.pagos.map(p => `<div class="f"><span>${cap(p.medio_pago)}${p.referencia && p.referencia !== '—' ? ' (' + esc(p.referencia) + ')' : ''}</span><span>${f(p.monto)}</span></div>`).join('')}
      ${r.vuelto > 0 ? `<div class="f"><span>Vuelto</span><span>${f(r.vuelto)}</span></div>` : ''}<hr><div class="c">¡Gracias por su compra!</div></div>
      <div class="mrow"><button class="btn g" type="button" id="tcerrar">Cerrar</button><button class="btn" type="button" id="timprimir">🖨️ Imprimir</button></div>`);
  }

  // ---------- Eventos ----------
  // Productos: tocar o arrastrar a la boleta.
  const grid = $('grid'), box = $('cartBox');
  grid.addEventListener('click', e => {
    const el = e.target.closest('.prod');
    if (el && !el.classList.contains('off')) add(Number(el.dataset.id));
  });
  grid.addEventListener('keydown', e => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const el = e.target.closest('.prod');
    if (el && !el.classList.contains('off')) { e.preventDefault(); add(Number(el.dataset.id)); }
  });
  grid.addEventListener('dragstart', e => {
    const el = e.target.closest('.prod');
    if (!el || el.classList.contains('off')) return e.preventDefault();
    e.dataTransfer.setData('text/plain', el.dataset.id);
    e.dataTransfer.effectAllowed = 'copy';
    document.body.classList.add('arrastrando');
  });
  grid.addEventListener('dragend', () => { document.body.classList.remove('arrastrando'); box.classList.remove('drop'); });
  box.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'copy'; box.classList.add('drop'); });
  box.addEventListener('dragleave', e => { if (!box.contains(e.relatedTarget)) box.classList.remove('drop'); });
  box.addEventListener('drop', e => {
    e.preventDefault();
    box.classList.remove('drop');
    document.body.classList.remove('arrastrando');
    const id = Number(e.dataTransfer.getData('text/plain'));
    if (id) add(id);
  });

  // Buscador y categorías.
  $('q').addEventListener('input', e => { q = e.target.value; renderGrid(); });
  $('q').addEventListener('keydown', e => { if (e.key === 'Enter') escanear(); });
  $('agregar').addEventListener('click', escanear);
  function escanear() {
    const t = q.trim();
    if (!t) return;
    let p = prods.find(x => x.codigo === t);
    if (!p) { const l = filtrados(); if (l.length === 1) p = l[0]; }
    if (!p) return toast('Producto no encontrado o hay varias coincidencias', true);
    add(p.id); q = ''; $('q').value = ''; renderGrid(); $('q').focus();
  }
  $('cats').addEventListener('click', e => {
    const b = e.target.closest('[data-cat]');
    if (b) { cat = b.dataset.cat; renderCats(); renderGrid(); }
  });

  // Boleta.
  box.addEventListener('click', e => {
    const t = e.target;
    if (t.closest('[data-del]')) return setQty(Number(t.closest('[data-del]').dataset.del), 0);
    if (t.closest('[data-comp]')) { comp = t.closest('[data-comp]').dataset.comp; return renderComp(); }
    if (t.id === 'vaciar') { cart = []; renderLineas(); renderGrid(); }
    if (t.id === 'cobrar') cobrar();
  });
  box.addEventListener('change', e => { if (e.target.dataset.qty) setQty(Number(e.target.dataset.qty), e.target.value); });
  box.addEventListener('input', e => {
    if (e.target.id === 'cdoc') lookup(e.target.value);
    if (e.target.id === 'cnom') cli.nombre = e.target.value;
  });

  // Ventanas (cobro y comprobante).
  $('mbox').addEventListener('click', e => {
    const t = e.target;
    if (t.closest('[data-pay]')) return addPay(t.closest('[data-pay]').dataset.pay);
    if (t.closest('[data-quitar]')) { P.splice(Number(t.closest('[data-quitar]').dataset.quitar), 1); return renderPay(); }
    if (t.id === 'pcancel' || t.id === 'tcerrar') cerrar();
    if (t.id === 'pok') confirmar();
    if (t.id === 'timprimir') window.print();
  });
  $('mbox').addEventListener('input', e => {
    const d = e.target.dataset;
    if (d.monto !== undefined) { P[Number(d.monto)].monto = e.target.value; renderSuma(); }
    if (d.ref !== undefined) P[Number(d.ref)].ref = e.target.value;
  });

  renderCats(); renderGrid(); renderCart();
})();