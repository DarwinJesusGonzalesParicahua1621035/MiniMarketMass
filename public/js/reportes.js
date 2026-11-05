// Reportes del cajero: incidencias y merma / ajuste de stock, sin recargar la página.
(function () {
  const REP = window.REP;
  if (!REP) return;

  const $ = id => document.getElementById(id);
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const r3 = n => Math.round((Number(n) + Number.EPSILON) * 1000) / 1000;
  const num = n => String(r3(n));
  const uni = (u, n) => {
    const t = String(u || 'u.');
    if (t !== 'unidad') return t;
    return Math.abs(Number(n) - 1) < 0.0005 ? 'unidad' : 'unidades';
  };

  function iniciar() {
    // Ventana propia: no choca con la del punto de venta ni con la de caja.
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'repModal';
    modal.innerHTML = '<div class="mbox" id="repBox"></div>';
    document.body.appendChild(modal);
    const box = $('repBox');
    const abrir = html => { box.innerHTML = html; modal.classList.add('on'); };
    const cerrar = () => modal.classList.remove('on');
    modal.addEventListener('click', e => { if (e.target === modal) cerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });

    let toast = $('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      document.body.appendChild(toast);
    }
    let tAviso = 0;
    function aviso(t, malo) {
      toast.textContent = t;
      toast.className = 'on' + (malo ? ' bad' : '');
      clearTimeout(tAviso);
      tAviso = setTimeout(() => { toast.className = ''; }, 3200);
    }

    // ---------- Incidencias ----------
    function verIncidencia() {
      const tipos = REP.tipos || ['Equipos', 'Seguridad', 'Stock', 'Otro'];
      abrir(`<h3>⚠️ Reportar incidencia</h3>
        <p class="rep-p">Cuéntanos qué pasó en ${esc(REP.sucursal || 'tu sucursal')}. El administrador recibirá tu reporte.</p>
        <div class="rep-l">Tipo de incidencia <small>Obligatorio</small></div>
        <select class="inp" id="ri-tipo">${tipos.map(t => `<option>${esc(t)}</option>`).join('')}</select>
        <div class="rep-l">Descripción <small id="ri-n">0 / 250</small></div>
        <textarea class="inp" id="ri-desc" maxlength="250" placeholder="Ej.: el lector de códigos no enciende desde las 10:00."></textarea>
        <div class="mrow"><button class="btn g" type="button" id="ri-cancel">Cancelar</button><button class="btn y" type="button" id="ri-send">Enviar reporte</button></div>`);
      const ta = $('ri-desc');
      ta.addEventListener('input', () => { $('ri-n').textContent = ta.value.length + ' / 250'; });
      $('ri-cancel').addEventListener('click', cerrar);
      $('ri-send').addEventListener('click', enviarIncidencia);
      ta.focus();
    }

    function enviarIncidencia() {
      const tipo = $('ri-tipo').value;
      const desc = $('ri-desc').value.trim();
      if (desc.length < 5) return aviso('Escribe una descripción de al menos 5 caracteres.', true);
      const b = $('ri-send');
      b.disabled = true;
      b.textContent = 'Enviando…';
      const fd = new FormData();
      fd.append('csrf', REP.csrf);
      fd.append('ajax', '1');
      fd.append('volver', 'cajero/caja');
      fd.append('tipo', tipo);
      fd.append('descripcion', desc);
      fetch(REP.urlIncidencia, {method: 'POST', body: fd, credentials: 'same-origin'})
        .then(r => r.json().catch(() => ({ok: false, error: 'Respuesta inválida del servidor.'})))
        .then(r => {
          if (!r.ok) throw new Error(r.error || 'No se pudo enviar la incidencia.');
          abrir(`<h3>✅ Incidencia enviada</h3>
            <p class="rep-p">El reporte <b>#${Number(r.id) || ''}</b> quedó registrado. El administrador lo revisará pronto.</p>
            <div class="rep-sum"><b>${esc(tipo)}</b><br>${esc(desc)}</div>
            <div class="mrow"><button class="btn y" type="button" id="ri-ok">Listo</button></div>`);
          $('ri-ok').addEventListener('click', cerrar);
          aviso('Incidencia enviada al administrador');
        })
        .catch(e => {
          b.disabled = false;
          b.textContent = 'Enviar reporte';
          aviso(e.message, true);
        });
    }

    // ---------- Merma / ajuste de stock (varios productos) ----------
    let accion = 'MERMA';
    let filaSeq = 0;
    let catFiltro = 'Todas';

    const categorias = () => ['Todas', ...new Set((REP.productos || []).map(p => p.categoria).filter(Boolean))];
    const productosFiltrados = () => (REP.productos || []).filter(p => catFiltro === 'Todas' || p.categoria === catFiltro);
    const opcionesProducto = () => productosFiltrados().map(p =>
      `<option value="${p.id}">${esc(p.nombre)} · ${num(p.stock)} ${esc(uni(p.unidad, p.stock))}</option>`
    ).join('');

    function pintarCats() {
      const cats = categorias();
      if (!cats.includes(catFiltro)) catFiltro = 'Todas';
      $('rs-cats').innerHTML = cats.map(c =>
        `<button type="button" data-cat="${esc(c)}" class="${c === catFiltro ? 'on' : ''}">${esc(c)}</button>`
      ).join('');
    }

    function llenarSelect(sel, valor) {
      sel.innerHTML = '<option value="">Elige un producto…</option>' + opcionesProducto();
      sel.value = valor && productosFiltrados().some(p => p.id === Number(valor)) ? String(valor) : '';
    }

    function datosFila(fila) {
      const sel = fila.querySelector('[data-prod]');
      const inp = fila.querySelector('[data-cant]');
      const p = (REP.productos || []).find(x => x.id === Number(sel.value)) || null;
      return {fila, sel, inp, p, cant: parseInt(inp.value, 10) || 0};
    }

    function filas() {
      return [...document.querySelectorAll('#rs-lines .rep-line')].map(datosFila);
    }

    function agregarFila() {
      const fila = document.createElement('div');
      fila.className = 'rep-line';
      fila.dataset.row = 'r' + (++filaSeq);
      fila.innerHTML = `<select class="inp" data-prod>
          <option value="">Elige un producto…</option>${opcionesProducto()}
        </select>
        <div class="rep-line-qty">
          <button class="rep-step" type="button" data-step="-1" title="Disminuir">−</button>
          <input class="inp" data-cant type="text" inputmode="numeric" pattern="[0-9]*" maxlength="7" placeholder="0" autocomplete="off">
          <button class="rep-step" type="button" data-step="1" title="Aumentar">+</button>
          <button class="rep-del" type="button" data-quitar-fila title="Quitar producto">✕</button>
        </div>
        <small class="rep-line-info" data-info></small>`;
      $('rs-lines').appendChild(fila);
      refrescarStock();
      return fila;
    }

    function verStock() {
      const lista = REP.productos || [];
      if (!lista.length) {
        abrir(`<h3>📦 Merma / ajuste de stock</h3>
          <p class="rep-p">Tu sucursal no tiene productos asignados. Pide al administrador que los asigne desde Productos.</p>
          <div class="mrow"><button class="btn y" type="button" id="rs-ok">Entendido</button></div>`);
        $('rs-ok').addEventListener('click', cerrar);
        return;
      }
      accion = 'MERMA';
      catFiltro = 'Todas';
      abrir(`<h3>📦 Merma / ajuste de stock</h3>
        <p class="rep-p">Reporta unidades perdidas (merma) o corrige varios productos con un conteo físico.</p>
        <div class="seg rep-seg" id="rs-acc">
          <button type="button" data-acc="MERMA" class="on">Merma</button>
          <button type="button" data-acc="AJUSTE">Ajuste de stock</button>
        </div>
        <div class="rep-l">Filtrar por categoría</div>
        <div class="rep-cats" id="rs-cats"></div>
        <div class="rep-l"><span id="rs-cant-l">Productos con merma</span> <small id="rs-count"></small></div>
        <div id="rs-lines"></div>
        <button class="btn g rep-add" type="button" id="rs-add">+ Agregar producto</button>
        <div class="rep-l">Motivo <small>Opcional</small></div>
        <input class="inp" id="rs-motivo" maxlength="70" placeholder="Ej.: vencido, dañado, conteo físico…" autocomplete="off">
        <div class="chips rep-chips" id="rs-chips"></div>
        <div class="rep-sum" id="rs-sum"></div>
        <div class="mrow"><button class="btn g" type="button" id="rs-cancel">Cancelar</button><button class="btn y" type="button" id="rs-send">Registrar</button></div>`);

      pintarCats();
      $('rs-add').addEventListener('click', () => {
        if (filas().length >= 20) return aviso('Puedes reportar hasta 20 productos a la vez.', true);
        agregarFila().querySelector('[data-prod]').focus();
      });
      $('rs-cats').addEventListener('click', e => {
        const b = e.target.closest('[data-cat]');
        if (!b) return;
        catFiltro = b.dataset.cat;
        pintarCats();
        document.querySelectorAll('#rs-lines .rep-line').forEach(fila => {
          const sel = fila.querySelector('[data-prod]');
          const prev = sel.value;
          llenarSelect(sel, prev);
          if (prev && !sel.value) fila.querySelector('[data-cant]').value = '';
        });
        refrescarStock();
      });
      $('rs-lines').addEventListener('keydown', e => {
        if (e.target.matches('[data-cant]') && ['.', ',', '-', '+', 'e', 'E'].includes(e.key)) e.preventDefault();
      });
      $('rs-lines').addEventListener('input', e => {
        if (e.target.matches('[data-cant]')) {
          const limpio = e.target.value.split(/[.,]/)[0].replace(/\D+/g, '').slice(0, 7);
          if (limpio !== e.target.value) e.target.value = limpio;
        }
        refrescarStock();
      });
      $('rs-lines').addEventListener('change', e => {
        if (e.target.matches('[data-prod]') && accion === 'AJUSTE') {
          const inp = e.target.closest('.rep-line').querySelector('[data-cant]');
          const p = (REP.productos || []).find(x => x.id === Number(e.target.value));
          if (p && !inp.value.trim()) inp.value = String(Math.floor(p.stock));
        }
        refrescarStock();
      });
      $('rs-lines').addEventListener('click', e => {
        const quitar = e.target.closest('[data-quitar-fila]');
        if (quitar) {
          const fila = quitar.closest('.rep-line');
          if (filas().length === 1) {
            fila.querySelector('[data-prod]').value = '';
            fila.querySelector('[data-cant]').value = '';
          } else {
            fila.remove();
          }
          refrescarStock();
          return;
        }
        const paso = e.target.closest('[data-step]');
        if (!paso) return;
        const inp = paso.closest('.rep-line').querySelector('[data-cant]');
        const actual = parseInt(inp.value, 10) || 0;
        const nuevo = Math.max(0, actual + Number(paso.dataset.step));
        inp.value = nuevo ? String(nuevo) : '';
        refrescarStock();
        inp.focus();
      });
      $('rs-chips').addEventListener('click', e => {
        const c = e.target.closest('[data-motivo]');
        if (!c) return;
        $('rs-motivo').value = c.dataset.motivo;
        $('rs-motivo').focus();
      });
      $('rs-acc').addEventListener('click', e => {
        const b = e.target.closest('[data-acc]');
        if (!b) return;
        accion = b.dataset.acc;
        [...$('rs-acc').children].forEach(x => x.classList.toggle('on', x === b));
        if (accion === 'AJUSTE') {
          document.querySelectorAll('#rs-lines .rep-line').forEach(fila => {
            const inp = fila.querySelector('[data-cant]');
            const p = (REP.productos || []).find(x => x.id === Number(fila.querySelector('[data-prod]').value));
            if (p && !inp.value.trim()) inp.value = String(Math.floor(p.stock));
          });
        }
        refrescarStock();
      });
      $('rs-cancel').addEventListener('click', cerrar);
      $('rs-send').addEventListener('click', enviarStock);
      agregarFila().querySelector('[data-prod]').focus();
    }

    function refrescarStock() {
      const chips = accion === 'MERMA'
        ? ['Vencido', 'Dañado', 'Rotura', 'Faltante']
        : ['Conteo físico', 'Error de registro', 'Devolución'];
      $('rs-chips').innerHTML = chips.map(c => `<button type="button" data-motivo="${esc(c)}">${esc(c)}</button>`).join('');

      const todas = filas();
      const activas = todas.filter(f => f.p || f.inp.value.trim() !== '');
      const usados = {};
      activas.forEach(f => { if (f.p) usados[f.p.id] = (usados[f.p.id] || 0) + 1; });

      let validas = 0, cambios = 0, errores = 0;
      todas.forEach(f => {
        const info = f.fila.querySelector('[data-info]');
        info.classList.remove('bad');
        if (!f.p && f.inp.value.trim() === '') { info.textContent = ''; return; }
        if (!f.p) {
          info.textContent = 'Elige un producto.';
          info.classList.add('bad');
          errores++;
          return;
        }
        if (usados[f.p.id] > 1) {
          info.textContent = 'Este producto ya está en otra fila.';
          info.classList.add('bad');
          errores++;
          return;
        }
        if (accion === 'MERMA') {
          if (!(f.cant > 0)) {
            info.textContent = 'Stock actual: ' + num(f.p.stock) + ' ' + uni(f.p.unidad, f.p.stock) + '. Escribe cuántas se perdieron.';
            errores++;
            return;
          }
          if (f.cant > f.p.stock) {
            info.textContent = 'Solo hay ' + num(f.p.stock) + ' ' + uni(f.p.unidad, f.p.stock) + '.';
            info.classList.add('bad');
            errores++;
            return;
          }
          info.textContent = 'Stock: ' + num(f.p.stock) + ' → ' + num(r3(f.p.stock - f.cant)) + ' ' +
            uni(f.p.unidad, r3(f.p.stock - f.cant)) + ' (merma de ' + num(f.cant) + ').';
          validas++;
          cambios++;
          return;
        }
        if (!(f.cant > 0)) {
          info.textContent = 'Stock actual: ' + num(f.p.stock) + '. Escribe la cantidad contada.';
          errores++;
          return;
        }
        const dif = r3(f.cant - f.p.stock);
        if (Math.abs(dif) < 0.0005) {
          info.textContent = 'Stock actual: ' + num(f.p.stock) + ' · sin cambios.';
          validas++;
          return;
        }
        info.textContent = 'Stock: ' + num(f.p.stock) + ' → ' + num(f.cant) +
          ' (' + (dif > 0 ? '+' : '') + num(dif) + ' ' + uni(f.p.unidad, Math.abs(dif)) + ').';
        validas++;
        cambios++;
      });

      $('rs-count').textContent = activas.length + (activas.length === 1 ? ' producto' : ' productos');
      $('rs-cant-l').textContent = accion === 'MERMA' ? 'Productos con merma' : 'Productos contados';

      if (!activas.length) {
        $('rs-sum').textContent = 'Agrega al menos un producto.';
      } else if (errores) {
        $('rs-sum').innerHTML = '<b class="bad-t">Completa los productos y cantidades marcados.</b>';
      } else if (accion === 'AJUSTE' && !cambios) {
        $('rs-sum').innerHTML = '<b class="bad-t">Todas las cantidades coinciden con el stock actual.</b>';
      } else {
        $('rs-sum').innerHTML = accion === 'MERMA'
          ? `Se registrará la merma de <b>${cambios} ${cambios === 1 ? 'producto' : 'productos'}</b>.`
          : `Se ajustarán <b>${cambios} de ${validas} ${validas === 1 ? 'producto' : 'productos'}</b>.`;
      }
    }

    function enviarStock() {
      const usables = filas().filter(f => f.p || f.inp.value.trim() !== '');
      if (!usables.length) return aviso('Agrega al menos un producto.', true);

      const items = [];
      const vistos = new Set();
      for (const f of usables) {
        if (!f.p) return aviso('Elige el producto en todas las filas.', true);
        if (vistos.has(f.p.id)) return aviso('Hay un producto repetido en la lista.', true);
        vistos.add(f.p.id);
        if (!Number.isInteger(f.cant) || f.cant <= 0) {
          return aviso('Escribe una cantidad entera mayor que 0 en todas las filas.', true);
        }
        if (accion === 'MERMA' && f.cant > f.p.stock) {
          return aviso(`Solo hay ${num(f.p.stock)} ${uni(f.p.unidad, f.p.stock)} de ${f.p.nombre}.`, true);
        }
        items.push({producto_id: f.p.id, cantidad: f.cant});
      }

      const antes = {};
      items.forEach(i => {
        const p = (REP.productos || []).find(x => x.id === i.producto_id);
        antes[i.producto_id] = p ? Number(p.stock) : 0;
      });
      if (accion === 'AJUSTE' && items.every(i => Math.abs(i.cantidad - antes[i.producto_id]) < 0.0005)) {
        return aviso('Las cantidades coinciden con el stock actual; no hay cambios.', true);
      }

      const b = $('rs-send');
      b.disabled = true;
      b.textContent = 'Registrando…';
      const fd = new FormData();
      fd.append('csrf', REP.csrf);
      fd.append('datos', JSON.stringify({
        accion: accion,
        motivo: ($('rs-motivo').value || '').trim(),
        items: items,
      }));
      fetch(REP.urlStock, {method: 'POST', body: fd, credentials: 'same-origin'})
        .then(r => r.json().catch(() => ({ok: false, error: 'Respuesta inválida del servidor.'})))
        .then(r => {
          if (!r.ok) throw new Error(r.error || 'No se pudo registrar el reporte.');
          (r.items || []).forEach(it => {
            const p = (REP.productos || []).find(x => x.id === Number(it.producto_id));
            if (p) p.stock = Number(it.stock);
            window.dispatchEvent(new CustomEvent('rep:stock', {detail: {producto_id: Number(it.producto_id), stock: Number(it.stock)}}));
          });
          const detalle = (r.items || []).map(it => {
            const a = antes[it.producto_id] ?? 0;
            const u = uni(it.unidad, it.stock);
            return `<div class="rep-res"><span>${esc(it.nombre || '')}</span><b>${it.cambiado === false ? 'sin cambios' : num(a) + ' → ' + num(it.stock) + ' ' + esc(u)}</b></div>`;
          }).join('');
          abrir(`<h3>✅ ${accion === 'MERMA' ? 'Merma registrada' : 'Ajuste registrado'}</h3>
            <p class="rep-p">${esc(r.mensaje || 'Stock actualizado.')}</p>
            <div class="rep-sum">${detalle}</div>
            <div class="mrow"><button class="btn y" type="button" id="rs-ok">Listo</button></div>`);
          $('rs-ok').addEventListener('click', cerrar);
          aviso('Stock actualizado');
        })
        .catch(e => {
          b.disabled = false;
          b.textContent = 'Registrar';
          aviso(e.message, true);
        });
    }

    // ---------- Botones de la barra lateral ----------
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-rep]');
      if (!b) return;
      e.preventDefault();
      if (b.dataset.rep === 'incidencia') verIncidencia();
      if (b.dataset.rep === 'stock') verStock();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
