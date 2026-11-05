/* Asistencia · filtros, resumen, tarjetas/lista y panel lateral.
   Los datos llegan desde la vista PHP en window.ASI = { hoy, ahora, reg: [...] }.
   Las tarjetas se dibujan desde esos datos cada vez que cambia un filtro
   (no se ocultan tarjetas ya dibujadas), así el filtrado no depende del HTML. */
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var root = $('asRoot');
  if (!root || !window.ASI) return;

  var DATA = window.ASI.reg || [];
  var HOY  = window.ASI.hoy;                    // AAAA-MM-DD según el servidor
  var NOW  = new Date(window.ASI.ahora);        // AAAA-MM-DDTHH:MM:SS según el servidor
  var st = { fecha: 'hoy', estado: '', q: '', orden: 'rec', vista: 'card', actual: null };

  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
  function dia(off) { var d = new Date(HOY + 'T00:00'); d.setDate(d.getDate() - off); return iso(d); }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function ini(n) { return String(n).trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join(''); }
  var LBL = { PRESENTE: 'Temprano', TARDANZA: 'Tarde', FALTA: 'Falta' };   // el valor guardado sigue siendo PRESENTE/TARDANZA/FALTA
  function cap(s) { return LBL[s] || s; }
  function ymd(r) { return r.in.slice(0, 10); }
  function fechaCorta(s) { return new Date(s + 'T00:00').toLocaleDateString('es-PE', { weekday: 'short', day: 'numeric', month: 'short' }); }
  function etq(s) { return s === HOY ? 'Hoy' : s === dia(1) ? 'Ayer' : fechaCorta(s); }

  /* Datos derivados de un registro */
  function info(r) {
    var falta = r.est === 'FALTA' && r.in.slice(11, 16) === '00:00', d = ymd(r), turno = !falta && !r.out && d === HOY, min = null;
    if (!falta && (r.out || turno)) {
      var fin = r.out ? new Date(r.out) : NOW;
      min = Math.max(0, Math.round((fin - new Date(r.in)) / 60000));
    }
    return {
      falta: falta, turno: turno, min: min,
      horas: min === null ? '—' : Math.floor(min / 60) + ' h ' + pad(min % 60) + ' min',
      salida: falta ? '—' : r.out ? r.out.slice(11, 16) : turno ? 'En turno' : 'Sin marcar',
      entrada: falta ? '—' : r.in.slice(11, 16)
    };
  }

  /* ---------- Filtros ---------- */
  function enFecha(r) {
    var d = ymd(r), f = st.fecha;
    if (f === 'todas') return true;
    if (f === 'hoy') return d === HOY;
    if (f === 'ayer') return d === dia(1);
    if (f === '7d') return d >= dia(6) && d <= HOY;
    return d === f;                                   // fecha elegida en el calendario
  }
  function base() { return DATA.filter(enFecha); }
  function visibles() {
    var t = st.q.trim().toLowerCase();
    var l = base().filter(function (r) {
      var ok = !st.estado || (st.estado === 'TURNO' ? info(r).turno : r.est === st.estado);
      return ok && (!t || String(r.emp).toLowerCase().indexOf(t) > -1);
    });
    l.sort(function (a, b) {
      if (st.orden === 'nom') return String(a.emp).localeCompare(String(b.emp), 'es') || (a.in < b.in ? 1 : -1);
      if (st.orden === 'hor') return (info(b).min || 0) - (info(a).min || 0);
      return a.in < b.in ? 1 : (a.in > b.in ? -1 : b.id - a.id);
    });
    return l;
  }

  /* ---------- Pintado ---------- */
  function pintarStats(b) {
    var c = function (e) { return b.filter(function (r) { return r.est === e; }).length; };
    var turno = b.filter(function (r) { return info(r).turno; }).length, n = b.length;
    var pct = function (x) { return n ? Math.round(x * 100 / n) + '%' : '—'; };
    var S = [
      ['PRESENTE', 'ok', '✅', c('PRESENTE'), 'Temprano', pct(c('PRESENTE'))],
      ['TARDANZA', 'warn', '⏰', c('TARDANZA'), 'Tarde', pct(c('TARDANZA'))],
      ['FALTA', 'bad', '🚫', c('FALTA'), 'Faltas', pct(c('FALTA'))],
      ['TURNO', 'live', '🟢', turno, 'En turno ahora', '']
    ];
    $('asStats').innerHTML = S.map(function (s) {
      return '<button type="button" class="as-stat as-' + s[1] + (st.estado === s[0] ? ' as-on' : '') + '" data-e="' + s[0] + '">' +
        '<i>' + s[2] + '</i><div><b>' + s[3] + '</b><span>' + s[4] + '</span></div>' + (s[5] ? '<em>' + s[5] + '</em>' : '') + '</button>';
    }).join('');
  }
  function pintarChips(b) {
    var c = function (e) { return b.filter(function (r) { return r.est === e; }).length; };
    var C = [['', 'Todos', b.length], ['PRESENTE', 'Temprano', c('PRESENTE')], ['TARDANZA', 'Tarde', c('TARDANZA')], ['FALTA', 'Falta', c('FALTA')]];
    $('asChips').innerHTML = C.map(function (x) {
      return '<button type="button" class="as-chip' + (st.estado === x[0] ? ' as-on' : '') + '" data-e="' + x[0] + '">' +
        (x[0] ? '<span class="as-dot as-' + x[0] + '"></span>' : '') + x[1] + '<small>' + x[2] + '</small></button>';
    }).join('');
  }
  function tarjeta(r) {
    var i = info(r), pct = i.min === null ? 0 : Math.min(100, i.min / 480 * 100);
    var cls = i.turno ? 'as-live' : (i.min >= 480 ? 'as-full' : '');
    return '<button type="button" class="as-it as-' + r.est + '" data-id="' + r.id + '">' +
      '<div class="as-top"><span class="as-av">' + esc(ini(r.emp)) + '</span><div class="as-who"><b>' + esc(r.emp) + '</b><small>' + etq(ymd(r)) + '</small></div>' +
      '<span class="as-tag as-' + r.est + '">' + cap(r.est) + '</span></div>' +
      '<div class="as-bar' + (i.min === null ? ' as-none' : '') + '" title="Jornada de 8 h"><i class="' + cls + '" style="width:' + pct + '%"></i></div>' +
      '<div class="as-times"><div><small>Entrada</small><b' + (i.falta ? ' class="as-mute"' : '') + '>' + i.entrada + '</b></div>' +
      '<div><small>Salida</small><b class="' + (i.turno ? 'as-live' : (i.falta || !r.out ? 'as-mute' : '')) + '">' + i.salida + '</b></div>' +
      '<div><small>Horas</small><b' + (i.min === null ? ' class="as-mute"' : '') + '>' + i.horas + '</b></div></div></button>';
  }
  function fila(r) {
    var i = info(r);
    return '<tr class="as-r" data-id="' + r.id + '"><td><div class="as-nm"><span class="as-av">' + esc(ini(r.emp)) + '</span>' + esc(r.emp) + '</div></td>' +
      '<td>' + etq(ymd(r)) + '</td><td>' + i.entrada + '</td><td>' + i.salida + '</td><td>' + i.horas + '</td>' +
      '<td><span class="as-tag as-' + r.est + '">' + cap(r.est) + '</span></td></tr>';
  }
  function pintar() {
    var b = base(), l = visibles(), body = $('asBody');
    $('asCuenta').textContent = l.length;
    pintarStats(b); pintarChips(b);
    if (!l.length) {
      body.innerHTML = '<div class="as-vacio"><b>🗓️</b>No hay registros con esos filtros.' +
        (st.estado || st.q || st.fecha !== 'todas' ? '<br><button type="button" id="asLimpiar">Ver todos los registros</button>' : '') + '</div>';
      return;
    }
    if (st.vista === 'list') {
      body.innerHTML = '<div class="as-tw"><table><thead><tr><th>Empleado</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Horas</th><th>Estado</th></tr></thead><tbody>' +
        l.map(fila).join('') + '</tbody></table></div>';
      return;
    }
    var dias = []; l.forEach(function (r) { if (dias.indexOf(ymd(r)) < 0) dias.push(ymd(r)); });
    if (dias.length > 1 && st.orden === 'rec') {      // varios días: se agrupa por fecha
      body.innerHTML = dias.map(function (d) {
        var g = l.filter(function (r) { return ymd(r) === d; });
        return '<div class="as-dia">' + etq(d) + '<small>' + g.length + '</small></div><div class="as-grid">' + g.map(tarjeta).join('') + '</div>';
      }).join('');
    } else {
      body.innerHTML = '<div class="as-grid">' + l.map(tarjeta).join('') + '</div>';
    }
  }

  /* ---------- Eventos de filtros ---------- */
  function fijarFecha(f) {
    st.fecha = f; st.estado = '';
    $('asSegFecha').querySelectorAll('button').forEach(function (b) { b.classList.toggle('as-on', b.dataset.f === f); });
    var custom = ['hoy', 'ayer', '7d', 'todas'].indexOf(f) < 0;
    $('asFecha').classList.toggle('as-on', custom);
    if (!custom) $('asFecha').value = '';
    pintar();
  }
  $('asSegFecha').addEventListener('click', function (e) { var b = e.target.closest('[data-f]'); if (b) fijarFecha(b.dataset.f); });
  $('asFecha').addEventListener('change', function () { fijarFecha(this.value ? this.value : 'hoy'); });
  $('asStats').addEventListener('click', function (e) {
    var b = e.target.closest('[data-e]'); if (!b) return;
    st.estado = st.estado === b.dataset.e ? '' : b.dataset.e; pintar();
  });
  $('asChips').addEventListener('click', function (e) { var b = e.target.closest('[data-e]'); if (b) { st.estado = b.dataset.e; pintar(); } });
  $('asQ').addEventListener('input', function () { st.q = this.value; pintar(); });
  $('asOrden').addEventListener('change', function () { st.orden = this.value; pintar(); });
  $('asVista').addEventListener('click', function (e) {
    var b = e.target.closest('[data-v]'); if (!b) return; st.vista = b.dataset.v;
    $('asVista').querySelectorAll('button').forEach(function (x) { x.classList.toggle('as-on', x === b); }); pintar();
  });
  $('asBody').addEventListener('click', function (e) {
    if (e.target.closest('#asLimpiar')) { $('asQ').value = ''; st.q = ''; fijarFecha('todas'); return; }
    var it = e.target.closest('[data-id]'); if (it) abrir(+it.dataset.id);
  });

  /* ---------- Panel lateral: detalle y edición (los cambios se guardan en el servidor) ---------- */
  function buscar(id) { return DATA.filter(function (r) { return r.id === id; })[0]; }
  function verDetalle() {
    var r = st.actual, i = info(r);
    $('asTit').textContent = 'Detalle de asistencia';
    $('asVAv').textContent = ini(r.emp); $('asVEmp').textContent = r.emp;
    $('asVSub').textContent = 'Registro #' + r.id + ' · ' + etq(ymd(r));
    $('asVEst').innerHTML = '<span class="as-tag as-' + r.est + '" style="margin:0">' + cap(r.est) + '</span>';
    $('asVIn').textContent = i.falta ? '—' : r.in.replace('T', ' ');
    $('asVOut').textContent = i.falta ? '—' : (r.out ? r.out.replace('T', ' ') : 'Sin marcar');
    $('asVHoras').textContent = i.horas;
    $('asDelId').value = r.id; $('asMarcarId').value = r.id;
    $('asMarcar').hidden = !i.turno;
    $('asEdit').hidden = true; $('asVer').hidden = false;
  }
  function editar() {
    var r = st.actual;
    $('asTit').textContent = 'Editar asistencia';
    $('asEId').value = r.id; $('asEEmp').value = r.empid;
    $('asEIn').value = r.in; $('asEOut').value = r.out; $('asEEst').value = info(r).falta ? 'FALTA' : 'AUTO';
    $('asEErr').textContent = '';
    $('asVer').hidden = true; $('asEdit').hidden = false; $('asEIn').focus();
  }
  function abrir(id) {
    st.actual = buscar(id); if (!st.actual) return;
    verDetalle(); $('asDrawer').classList.add('as-open'); $('asScrim').classList.add('as-open');
  }
  function cerrar() { $('asDrawer').classList.remove('as-open'); $('asScrim').classList.remove('as-open'); }

  $('asBEditar').addEventListener('click', editar);
  $('asBCancelar').addEventListener('click', verDetalle);
  $('asCerrar').addEventListener('click', cerrar);
  $('asScrim').addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });
  $('asEdit').addEventListener('submit', function (e) {
    var i = $('asEIn').value, o = $('asEOut').value;
    if (o && o < i) { e.preventDefault(); $('asEErr').textContent = 'La salida no puede ser anterior a la entrada.'; }
  });

  $('asHoy').textContent = NOW.toLocaleDateString('es-PE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  pintar();
})();
