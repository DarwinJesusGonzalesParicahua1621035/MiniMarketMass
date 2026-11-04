(function () {
  const $ = id => document.getElementById(id);
  const drawer = $('cliDrawer');
  if (!drawer) return;

  const scrim = $('cliScrim');
  const items = [...document.querySelectorAll('.cli-item')];
  const filtro = { tipo: '', compras: '' };
  let actual = null;

  /* ---------- Filtros (chips + buscador) ---------- */
  function filtrar() {
    const t = $('cliBuscar').value.trim().toLowerCase();
    let n = 0;
    items.forEach(it => {
      const v = +it.dataset.ventas;
      const ok =
        (!filtro.tipo || it.dataset.tipo === filtro.tipo) &&
        (!filtro.compras || (filtro.compras === 'con' ? v > 0 : v === 0)) &&
        (it.dataset.nombre.toLowerCase().includes(t) || it.dataset.doc.includes(t));
      it.hidden = !ok;
      if (ok) n++;
    });
    $('cliCount').textContent = n;
    $('cliVacio').hidden = n > 0;
  }
  document.querySelectorAll('.cli-chip').forEach(c =>
    c.addEventListener('click', () => {
      document.querySelectorAll('.cli-chip[data-grupo="' + c.dataset.grupo + '"]')
        .forEach(x => x.classList.remove('on'));
      c.classList.add('on');
      filtro[c.dataset.grupo] = c.dataset.v;
      filtrar();
    }));
  $('cliBuscar').addEventListener('input', filtrar);

  /* ---------- Drawer ---------- */
  function iniciales(n) {
    return n.trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
  }
  function ajustarDoc() {
    const n = $('eTipo').value === 'RUC' ? 11 : 8;
    const d = $('eDoc');
    d.maxLength = n;
    d.pattern = '\\d{' + n + '}';
    d.title = 'El ' + $('eTipo').value + ' debe tener ' + n + ' dígitos';
  }
  function verDetalle() {
    const d = actual.dataset;
    $('cliTitulo').textContent = 'Detalle del cliente';
    $('vAv').textContent = iniciales(d.nombre);
    $('vNombre').textContent = d.nombre;
    $('vId').textContent = 'Cliente #' + d.id;
    $('vTipo').textContent = d.tipo;
    $('vDoc').textContent = d.doc;
    $('vVentas').textContent = d.ventas;
    $('delId').value = d.id;
    const conCompras = +d.ventas > 0;
    $('delBtn').disabled = conCompras;
    $('vNota').hidden = !conCompras;
    $('cliEdit').hidden = true;
    $('cliVer').hidden = false;
  }
  function editar() {
    const d = actual.dataset;
    $('cliTitulo').textContent = 'Editar cliente';
    $('eId').value = d.id;
    $('eTipo').value = d.tipo;
    $('eDoc').value = d.doc;
    $('eNombre').value = d.nombre;
    ajustarDoc();
    $('cliVer').hidden = true;
    $('cliEdit').hidden = false;
    $('eNombre').focus();
  }
  function abrir(it) {
    actual = it;
    verDetalle();
    drawer.classList.add('open');
    scrim.classList.add('open');
  }
  function cerrar() {
    drawer.classList.remove('open');
    scrim.classList.remove('open');
  }

  items.forEach(it => it.addEventListener('click', () => abrir(it)));
  $('btnEditar').addEventListener('click', editar);
  $('btnCancelar').addEventListener('click', verDetalle);
  $('eTipo').addEventListener('change', ajustarDoc);
  $('cliCerrar').addEventListener('click', cerrar);
  scrim.addEventListener('click', cerrar);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });
})();