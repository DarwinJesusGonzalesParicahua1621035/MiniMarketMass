(function () {
  const $ = id => document.getElementById(id);
  const root = $('asiRoot');
  if (!root) return;

  const hoy = root.dataset.hoy, ayer = root.dataset.ayer;
  const items = [...document.querySelectorAll('.asi-item')];
  const drawer = $('asiDrawer'), scrim = $('asiScrim');
  const filtro = { fecha: 'hoy', estado: '' };
  let actual = null;

  /* ---------- Filtros, contadores y resumen ---------- */
  const enFecha = it =>
    filtro.fecha === 'todas' ||
    it.dataset.dia === (filtro.fecha === 'hoy' ? hoy : ayer);

  function filtrar() {
    const t = $('asiBuscar').value.trim().toLowerCase();
    const base = items.filter(enFecha);
    const cuenta = est => base.filter(i => i.dataset.est === est).length;

    $('stP').textContent = cuenta('PRESENTE');
    $('stT').textContent = cuenta('TARDANZA');
    $('stF').textContent = cuenta('FALTA');
    $('stE').textContent = base.filter(i => i.dataset.turno === '1').length;
    document.querySelectorAll('.asi-chip i').forEach(i => {
      i.textContent = i.dataset.n ? cuenta(i.dataset.n) : base.length;
    });

    let n = 0;
    items.forEach(it => {
      const ok = enFecha(it) &&
        (!filtro.estado || it.dataset.est === filtro.estado) &&
        it.dataset.emp.toLowerCase().includes(t);
      it.hidden = !ok;
      if (ok) n++;
    });
    $('asiCount').textContent = n;
    $('asiVacio').hidden = n > 0;
  }
  document.querySelectorAll('.asi-chip').forEach(c =>
    c.addEventListener('click', () => {
      document.querySelectorAll('.asi-chip[data-grupo="' + c.dataset.grupo + '"]')
        .forEach(x => x.classList.remove('on'));
      c.classList.add('on');
      filtro[c.dataset.grupo] = c.dataset.v;
      filtrar();
    }));
  $('asiBuscar').addEventListener('input', filtrar);

  /* ---------- Drawer ---------- */
  const iniciales = n => n.trim().split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('');
  const cap = s => s.charAt(0) + s.slice(1).toLowerCase();

  function verDetalle() {
    const d = actual.dataset, falta = d.est === 'FALTA';
    $('asiTitulo').textContent = 'Detalle de asistencia';
    $('vAv').textContent = iniciales(d.emp);
    $('vEmp').textContent = d.emp;
    $('vSub').textContent = 'Registro #' + d.id + ' · ' + d.etq;
    $('vEst').innerHTML = '<span class="asi-tag ' + d.est + '" style="margin:0">' + cap(d.est) + '</span>';
    $('vIn').textContent = falta ? '—' : d.in.replace('T', ' ');
    $('vOut').textContent = falta ? '—' : (d.out ? d.out.replace('T', ' ') : 'Sin marcar');
    $('vHoras').textContent = d.horas;
    $('delId').value = d.id;
    $('marcarId').value = d.id;
    $('asiMarcar').hidden = d.turno !== '1';
    $('asiEdit').hidden = true;
    $('asiVer').hidden = false;
  }
  function editar() {
    const d = actual.dataset;
    $('asiTitulo').textContent = 'Editar asistencia';
    $('eId').value = d.id;
    $('eEmp').value = d.empid;
    $('eIn').value = d.in;
    $('eOut').value = d.out;
    $('eEst').value = d.est;
    $('eErr').textContent = '';
    $('asiVer').hidden = true;
    $('asiEdit').hidden = false;
    $('eIn').focus();
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
  $('asiCerrar').addEventListener('click', cerrar);
  scrim.addEventListener('click', cerrar);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });

  $('asiEdit').addEventListener('submit', e => {
    const i = $('eIn').value, o = $('eOut').value;
    if (o && o < i) {
      e.preventDefault();
      $('eErr').textContent = 'La salida no puede ser anterior a la entrada.';
    }
  });

  filtrar();
})();