/**
 * Sucursales: filtros, detalle y formulario en drawer.
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
(function () {
  var dist = '';
  var q = document.getElementById('suc-q');
  var cards = [].slice.call(document.querySelectorAll('.suc-item'));
  var fondo = document.getElementById('dr-fondo');
  var dr = document.getElementById('drawer');
  var vista = document.getElementById('suc-vista');
  var form = document.getElementById('suc-form');
  var ultimo = null;
  var actual = null;

  function aplicar() {
    var t = (q.value || '').trim().toLowerCase();
    var n = 0;
    cards.forEach(function (c) {
      var ok = (!dist || c.dataset.dist === dist) && (!t || c.dataset.txt.indexOf(t) > -1);
      c.style.display = ok ? '' : 'none';
      if (ok) n++;
    });
    document.getElementById('suc-total').textContent = n;
    document.getElementById('suc-kpi-n').textContent = n;
    document.getElementById('suc-vacio').classList.toggle('show', n === 0);
  }

  document.querySelectorAll('#suc-f-dist .prd-chip').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('#suc-f-dist .prd-chip').forEach(function (x) { x.classList.remove('on'); });
      b.classList.add('on');
      dist = b.dataset.v;
      aplicar();
    });
  });
  q.addEventListener('input', aplicar);

  function abrirDrawer() {
    fondo.classList.add('abierto');
    dr.classList.add('abierto');
    dr.setAttribute('aria-hidden', 'false');
  }
  function cerrar() {
    fondo.classList.remove('abierto');
    dr.classList.remove('abierto');
    dr.setAttribute('aria-hidden', 'true');
    dr.classList.remove('editando');
    vista.style.display = '';
    form.style.display = 'none';
    if (ultimo) ultimo.focus();
  }

  function mostrarDetalle(c) {
    actual = c;
    ultimo = c;
    var img = document.getElementById('dr-img');
    var foto = document.getElementById('dr-foto');
    if (c.dataset.img) {
      img.src = c.dataset.img;
      img.alt = c.dataset.nombre;
      foto.classList.remove('sin');
      document.getElementById('dr-emoji').style.display = 'none';
    } else {
      img.removeAttribute('src');
      foto.classList.add('sin');
      document.getElementById('dr-emoji').style.display = '';
    }
    document.getElementById('dr-nombre').textContent = c.dataset.nombre;
    document.getElementById('dr-sub').textContent = 'Sucursal N.º ' + c.dataset.id;
    document.getElementById('dr-dir').textContent = c.dataset.dir;
    document.getElementById('dr-dist').textContent = c.dataset.dist;
    document.getElementById('dr-sb').textContent = c.dataset.sb;
    document.getElementById('dr-sf').textContent = c.dataset.sf;
    document.getElementById('dr-cajas').textContent = c.dataset.cajas;
    document.getElementById('dr-id').value = c.dataset.id;
    vista.style.display = '';
    form.style.display = 'none';
    dr.classList.remove('editando');
    abrirDrawer();
    document.getElementById('dr-cerrar').focus();
  }

  function mostrarForm(c) {
    actual = c || null;
    document.getElementById('f-titulo').textContent = c ? 'Editar sucursal' : 'Nueva sucursal';
    document.getElementById('f-id').value = c ? c.dataset.id : '0';
    document.getElementById('f-nombre').value = c ? c.dataset.nombre : '';
    document.getElementById('f-dir').value = c ? c.dataset.dir : '';
    document.getElementById('f-dist').value = c ? c.dataset.distid : '';
    document.getElementById('f-sb').value = c ? c.dataset.sb : '';
    document.getElementById('f-sf').value = c ? c.dataset.sf : '';
    vista.style.display = 'none';
    form.style.display = 'flex';
    dr.classList.add('editando');
    abrirDrawer();
    document.getElementById('f-nombre').focus();
  }

  cards.forEach(function (c) {
    c.addEventListener('click', function () { mostrarDetalle(c); });
  });
  document.getElementById('suc-nuevo').addEventListener('click', function () {
    ultimo = this;
    mostrarForm(null);
  });
  document.getElementById('dr-editar').addEventListener('click', function () {
    if (actual) mostrarForm(actual);
  });
  document.getElementById('dr-cerrar').addEventListener('click', cerrar);
  document.getElementById('f-cerrar').addEventListener('click', cerrar);
  document.getElementById('f-cancelar').addEventListener('click', function () {
    if (actual) mostrarDetalle(actual);
    else cerrar();
  });
  fondo.addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && dr.classList.contains('abierto')) cerrar();
  });
})();
