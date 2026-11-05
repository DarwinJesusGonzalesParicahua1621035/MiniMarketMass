// public/js/incidentes.js — pestañas, filtros, buscador y ventana de reportar/editar
(function () {
  const root = document.getElementById('imRoot');
  if (!root) return;

  const $ = (id) => document.getElementById(id);
  const items = Array.from(root.querySelectorAll('.im-item'));
  const tabs = Array.from(root.querySelectorAll('.im-tab'));
  const KEY = 'im-estado';

  // ---------- Estado de los filtros (se recuerda al recargar la página) ----------
  const st = { vista: 'abiertos', tipo: '', suc: '', q: '' };
  try { Object.assign(st, JSON.parse(sessionStorage.getItem(KEY) || '{}')); } catch (e) {}
  if (!tabs.some((t) => t.dataset.vista === st.vista)) st.vista = 'abiertos';
  const guardar = () => { try { sessionStorage.setItem(KEY, JSON.stringify(st)); } catch (e) {} };

  const porVista = {
    abiertos:  (i) => i.dataset.est === 'ABIERTO',
    urgentes:  (i) => i.dataset.est === 'ABIERTO' && i.dataset.urg === '1',
    resueltos: (i) => i.dataset.est === 'RESUELTO',
    todos:     () => true,
  };
  const pasaFiltros = (i) =>
    (!st.tipo || i.dataset.tipo === st.tipo) &&
    (!st.suc || i.dataset.sucid === st.suc) &&
    (!st.q || i.dataset.texto.includes(st.q));

  function pintar() {
    // Contadores de cada pestaña (respetan buscador, sucursal y tipo)
    Object.keys(porVista).forEach((v) => {
      const n = items.filter((i) => pasaFiltros(i) && porVista[v](i)).length;
      const el = root.querySelector('[data-n="' + v + '"]');
      if (el) el.textContent = n;
    });
    tabs.forEach((t) => {
      const on = t.dataset.vista === st.vista;
      t.classList.toggle('on', on);
      t.setAttribute('aria-selected', on ? 'true' : 'false');
    });

    let visibles = 0;
    items.forEach((i) => {
      const ok = pasaFiltros(i) && porVista[st.vista](i);
      i.hidden = !ok;
      if (ok) visibles++;
    });

    const hayFiltro = !!(st.tipo || st.suc || st.q);
    const vacio = $('imVacio');
    vacio.hidden = visibles > 0;
    if (!vacio.hidden) {
      const msg = {
        abiertos:  ['🎉', 'No hay incidentes por atender. ¡Todo en orden!'],
        urgentes:  ['👍', 'No hay incidentes urgentes.'],
        resueltos: ['📭', 'Todavía no hay incidentes resueltos.'],
        todos:     ['📭', 'Aún no se ha reportado ningún incidente.'],
      }[st.vista];
      $('imVacioIc').textContent = hayFiltro ? '🔍' : msg[0];
      $('imVacioTx').textContent = hayFiltro ? 'Ningún incidente coincide con esos filtros.' : msg[1];
      $('imLimpiar').hidden = !hayFiltro;
    }
    guardar();
  }

  tabs.forEach((t) => t.addEventListener('click', () => { st.vista = t.dataset.vista; pintar(); }));
  $('imBuscar').addEventListener('input', (e) => { st.q = e.target.value.trim().toLowerCase(); pintar(); });
  $('imSuc').addEventListener('change', (e) => { st.suc = e.target.value; pintar(); });
  $('imTipo').addEventListener('change', (e) => { st.tipo = e.target.value; pintar(); });
  $('imLimpiar').addEventListener('click', () => {
    st.q = st.suc = st.tipo = '';
    $('imBuscar').value = ''; $('imSuc').value = ''; $('imTipo').value = '';
    pintar();
  });

  // Restaurar controles con el estado guardado
  $('imBuscar').value = st.q;
  $('imSuc').value = st.suc;
  $('imTipo').value = st.tipo;
  if ($('imSuc').value !== st.suc) st.suc = '';
  if ($('imTipo').value !== st.tipo) st.tipo = '';

  // ---------- Ventana de reportar / editar ----------
  const dlg = $('imDlg');
  const form = $('imForm');
  const cuenta = () => { $('fCnt').textContent = $('fDesc').value.length; };
  $('fDesc').addEventListener('input', cuenta);

  function abrir(item) {
    const nuevo = !item;
    form.reset();
    $('imTitulo').textContent = nuevo ? 'Reportar incidente' : 'Editar incidente';
    $('fGuardar').textContent = nuevo ? 'Reportar incidente' : 'Guardar cambios';
    $('fId').value = nuevo ? 0 : item.dataset.id;
    $('delId').value = nuevo ? 0 : item.dataset.id;
    $('fEliminar').hidden = nuevo;
    $('fEstadoBox').hidden = nuevo;

    const estado = nuevo ? 'ABIERTO' : item.dataset.est;
    $('fEstado').value = estado;
    form.querySelectorAll('[name=estado_vis]').forEach((r) => { r.checked = r.value === estado; });

    const tipo = nuevo ? '' : item.dataset.tipo;
    form.querySelectorAll('[name=tipo]').forEach((r) => { r.checked = tipo !== '' && r.value === tipo; });

    // Sucursal: al editar la del incidente; al crear, la del filtro (si hay) o sin elegir
    $('fSuc').value = nuevo ? (st.suc || '') : item.dataset.sucid;
    $('fDesc').value = nuevo ? '' : item.dataset.desc;
    $('fFecha').value = nuevo ? root.dataset.ahora : item.dataset.fecha;
    cuenta();

    if (typeof dlg.showModal === 'function') dlg.showModal(); else dlg.setAttribute('open', '');
    (nuevo ? form.querySelector('[name=tipo]') : $('fDesc')).focus();
  }
  const cerrar = () => { if (dlg.open) dlg.close(); };

  form.querySelectorAll('[name=estado_vis]').forEach((r) =>
    r.addEventListener('change', () => { $('fEstado').value = r.value; }));

  $('imNuevo').addEventListener('click', () => abrir(null));
  $('imCerrar').addEventListener('click', cerrar);
  $('fCancelar').addEventListener('click', cerrar);
  // Clic fuera de la ventana (en el fondo oscuro) la cierra
  dlg.addEventListener('mousedown', (e) => { if (e.target === dlg) cerrar(); });

  // Editar: botón "Editar" o clic en cualquier parte de la fila (menos en los botones)
  root.addEventListener('click', (e) => {
    const item = e.target.closest('.im-item');
    if (!item) return;
    if (e.target.closest('.im-edit')) return abrir(item);
    if (e.target.closest('form, button, a, input, select')) return;
    abrir(item);
  });

  // Evitar doble envío
  document.querySelectorAll('.im-acts form, #imForm').forEach((f) =>
    f.addEventListener('submit', (e) => {
      if (e.defaultPrevented) return;
      const b = f.querySelector('button[type=submit], button:not([type])');
      setTimeout(() => { if (b) b.disabled = true; }, 0);
    }));

  // Si se vuelve con el botón «Atrás», rehabilitar los botones
  window.addEventListener('pageshow', () =>
    document.querySelectorAll('.im-acts button, #imForm button').forEach((b) => { b.disabled = false; }));

  pintar();
})();
