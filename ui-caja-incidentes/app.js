(() => {
  const KEY = "mass_caja_incidentes_v1";

  const seed = () => ({
    sucursales: [
      { id: 1, nombre: "Mass Cayma" },
      { id: 2, nombre: "Mass Yanahuara" },
      { id: 3, nombre: "Mass Cercado" },
    ],
    cajas: [
      { id: 1, sucursal_id: 1, nombre: "Caja 1" },
      { id: 2, sucursal_id: 1, nombre: "Caja 2" },
      { id: 3, sucursal_id: 2, nombre: "Caja 1" },
      { id: 4, sucursal_id: 3, nombre: "Caja 1" },
    ],
    historial: [
      {
        id: 1,
        caja_id: 1,
        empleado: "Jorge Salas",
        apertura: "2026-10-28 08:02:00",
        cierre: null,
        monto_inicial: 150,
        monto_final: null,
        estado: "ABIERTA",
      },
      {
        id: 2,
        caja_id: 3,
        empleado: "Lucía Paredes",
        apertura: "2026-10-27 08:10:00",
        cierre: "2026-10-27 21:05:00",
        monto_inicial: 100,
        monto_final: 485.5,
        estado: "CERRADA",
      },
    ],
    incidentes: [
      {
        id: 1,
        sucursal_id: 1,
        tipo: "Seguridad",
        descripcion: "Cámara del ingreso sin señal",
        fecha: "2026-10-27 14:20:00",
        estado: "ABIERTO",
      },
      {
        id: 2,
        sucursal_id: 2,
        tipo: "Equipos",
        descripcion: "Impresora de boletas atascada",
        fecha: "2026-10-26 11:05:00",
        estado: "RESUELTO",
      },
      {
        id: 3,
        sucursal_id: 3,
        tipo: "Stock",
        descripcion: "Faltante de gaseosas en anaquel",
        fecha: "2026-10-28 09:40:00",
        estado: "ABIERTO",
      },
    ],
    next: { caja: 5, historia: 3, incidente: 4 },
    usuario: "Lucía Paredes",
  });

  const load = () => {
    try {
      const raw = localStorage.getItem(KEY);
      return raw ? JSON.parse(raw) : seed();
    } catch {
      return seed();
    }
  };

  const save = () => localStorage.setItem(KEY, JSON.stringify(state));

  let state = load();
  let view = "cajas";
  let editIncidenteId = null;
  let preselectCaja = null;
  let preselectHistoria = null;

  const $ = (sel) => document.querySelector(sel);
  const vista = $("#vista");
  const flashEl = $("#flash");

  const money = (n) =>
    n == null || n === ""
      ? "—"
      : `S/ ${Number(n).toLocaleString("es-PE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

  const sucursalNombre = (id) =>
    state.sucursales.find((s) => s.id === id)?.nombre || "—";

  const cajaDe = (id) => state.cajas.find((c) => c.id === id);

  const turnoAbierto = (cajaId) =>
    state.historial.find((h) => h.caja_id === cajaId && h.estado === "ABIERTA");

  const tagEstado = (estado) => {
    if (estado === "ABIERTA" || estado === "ABIERTO") return `<span class="tag y">${estado}</span>`;
    if (estado === "CERRADA" || estado === "RESUELTO") return `<span class="tag ok">${estado}</span>`;
    return `<span class="tag">${estado || "Sin turno"}</span>`;
  };

  const flash = (msg, err = false) => {
    flashEl.textContent = msg;
    flashEl.classList.toggle("err", err);
    flashEl.classList.remove("hidden");
    clearTimeout(flash._t);
    flash._t = setTimeout(() => flashEl.classList.add("hidden"), 2800);
  };

  const setNav = () => {
    document.querySelectorAll(".nav-btn").forEach((btn) => {
      btn.classList.toggle("on", btn.dataset.view === view || (view === "incidente-form" && btn.dataset.view === "incidente-form" && editIncidenteId));
      if (view === "incidente-form" && !editIncidenteId) {
        btn.classList.toggle("on", btn.dataset.view === "incidente-form");
      }
    });
  };

  const titles = {
    cajas: ["Operaciones", "Cajas"],
    historial: ["Operaciones", "Historial de caja"],
    abrir: ["Operaciones", "Abrir turno"],
    cerrar: ["Operaciones", "Cerrar turno"],
    nueva: ["Operaciones", "Nueva caja"],
    incidentes: ["Configuración", "Incidentes"],
    "incidente-form": ["Configuración", "Incidente"],
  };

  const go = (name, opts = {}) => {
    view = name;
    if ("editId" in opts) editIncidenteId = opts.editId;
    if ("cajaId" in opts) preselectCaja = opts.cajaId;
    if ("historiaId" in opts) preselectHistoria = opts.historiaId;
    if (name !== "incidente-form") editIncidenteId = name === "incidente-form" ? editIncidenteId : null;
    const [eye, title] = titles[name] || ["Panel", name];
    $("#eyebrow").textContent = eye;
    $("#titulo").textContent =
      name === "incidente-form" ? (editIncidenteId ? "Editar incidente" : "Nuevo incidente") : title;
    setNav();
    render();
  };

  const filterRows = (q, rows) => {
    if (!q) return rows;
    const n = q.toLowerCase();
    return rows.filter((r) => JSON.stringify(r).toLowerCase().includes(n));
  };

  function renderCajas() {
    const suc = Number($("#filtro-sucursal")?.value || 0) || 0;
    const q = $("#filtro-buscar")?.value || "";
    let filas = state.cajas.map((c) => {
      const t = turnoAbierto(c.id);
      return {
        ...c,
        sucursal: sucursalNombre(c.sucursal_id),
        abierta: !!t,
        historia_id: t?.id,
        empleado: t?.empleado,
        apertura: t?.apertura,
        monto_inicial: t?.monto_inicial,
      };
    });
    if (suc) filas = filas.filter((f) => f.sucursal_id === suc);
    filas = filterRows(q, filas);

    const abiertas = state.historial.filter((h) => h.estado === "ABIERTA").length;

    vista.innerHTML = `
      <div class="kpis">
        <div class="kpi"><span>Cajas</span><strong>${state.cajas.length}</strong></div>
        <div class="kpi"><span>Turnos abiertos</span><strong>${abiertas}</strong></div>
        <div class="kpi"><span>Incidentes abiertos</span><strong>${state.incidentes.filter((i) => i.estado === "ABIERTO").length}</strong></div>
      </div>
      <div class="panel">
        <div class="tools">
          <h3>Cajas <span class="count">${filas.length}</span></h3>
          <select class="search" id="filtro-sucursal">
            <option value="">Todas las sucursales</option>
            ${state.sucursales.map((s) => `<option value="${s.id}" ${suc === s.id ? "selected" : ""}>${s.nombre}</option>`).join("")}
          </select>
          <input class="search" id="filtro-buscar" placeholder="Buscar…" value="${q.replaceAll('"', "&quot;")}">
          <button type="button" class="btn g" data-go="historial">Ver historial</button>
          <button type="button" class="btn g" data-go="nueva">+ Nueva caja</button>
          <button type="button" class="btn y" data-go="abrir">Abrir caja</button>
        </div>
        <div class="scroll">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Sucursal</th><th>Caja</th><th>Estado</th><th>Responsable</th>
                <th>Apertura</th><th class="money">Monto inicial</th><th></th>
              </tr>
            </thead>
            <tbody>
              ${filas
                .map(
                  (f) => `
                <tr>
                  <td>${f.id}</td>
                  <td>${f.sucursal}</td>
                  <td><b>${f.nombre}</b></td>
                  <td>${f.abierta ? tagEstado("ABIERTA") : '<span class="tag">Sin turno</span>'}</td>
                  <td>${f.abierta ? f.empleado : "—"}</td>
                  <td>${f.abierta ? f.apertura : "—"}</td>
                  <td class="money">${f.abierta ? money(f.monto_inicial) : "—"}</td>
                  <td>
                    ${
                      f.abierta
                        ? `<button type="button" class="btn g sm" data-cerrar="${f.historia_id}">Cerrar</button>`
                        : `<button type="button" class="btn sm" data-abrir="${f.id}">Abrir</button>`
                    }
                  </td>
                </tr>`
                )
                .join("")}
            </tbody>
          </table>
          ${filas.length ? "" : '<div class="empty">Sin cajas registradas</div>'}
        </div>
      </div>`;

    $("#filtro-sucursal").onchange = () => renderCajas();
    $("#filtro-buscar").oninput = () => renderCajas();
  }

  function renderHistorial() {
    const estado = $("#filtro-estado")?.value || "";
    const q = $("#filtro-buscar")?.value || "";
    let filas = state.historial
      .map((h) => {
        const c = cajaDe(h.caja_id);
        const diff =
          h.monto_final != null ? Number(h.monto_final) - Number(h.monto_inicial) : null;
        return {
          ...h,
          sucursal: sucursalNombre(c?.sucursal_id),
          caja: c?.nombre || "—",
          diff,
        };
      })
      .sort((a, b) => b.id - a.id);
    if (estado) filas = filas.filter((f) => f.estado === estado);
    filas = filterRows(q, filas);

    vista.innerHTML = `
      <div class="panel">
        <div class="tools">
          <h3>Historial <span class="count">${filas.length}</span></h3>
          <select class="search" id="filtro-estado">
            <option value="">Todos los estados</option>
            <option value="ABIERTA" ${estado === "ABIERTA" ? "selected" : ""}>ABIERTA</option>
            <option value="CERRADA" ${estado === "CERRADA" ? "selected" : ""}>CERRADA</option>
          </select>
          <input class="search" id="filtro-buscar" placeholder="Buscar…" value="${q.replaceAll('"', "&quot;")}">
          <button type="button" class="btn g" data-go="cajas">Volver a cajas</button>
        </div>
        <div class="scroll">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Sucursal</th><th>Caja</th><th>Empleado</th>
                <th>Apertura</th><th>Cierre</th>
                <th class="money">Inicial</th><th class="money">Final</th><th class="money">Diferencia</th><th>Estado</th>
              </tr>
            </thead>
            <tbody>
              ${filas
                .map(
                  (f) => `
                <tr>
                  <td>${f.id}</td>
                  <td>${f.sucursal}</td>
                  <td><b>${f.caja}</b></td>
                  <td>${f.empleado}</td>
                  <td>${f.apertura}</td>
                  <td>${f.cierre || "—"}</td>
                  <td class="money">${money(f.monto_inicial)}</td>
                  <td class="money">${money(f.monto_final)}</td>
                  <td class="money ${f.diff == null ? "" : f.diff >= 0 ? "diff-pos" : "diff-neg"}">${
                    f.diff == null ? "—" : money(f.diff)
                  }</td>
                  <td>${tagEstado(f.estado)}</td>
                </tr>`
                )
                .join("")}
            </tbody>
          </table>
          ${filas.length ? "" : '<div class="empty">Sin registros</div>'}
        </div>
      </div>`;

    $("#filtro-estado").onchange = () => renderHistorial();
    $("#filtro-buscar").oninput = () => renderHistorial();
  }

  function renderNueva() {
    vista.innerHTML = `
      <div class="panel form-card">
        <h3>Nueva caja</h3>
        <p>Solo administrador. El nombre no se repite dentro de la misma sucursal.</p>
        <form id="form-nueva">
          <label class="field">Sucursal
            <select class="campo" name="sucursal_id" required>
              ${state.sucursales.map((s) => `<option value="${s.id}">${s.nombre}</option>`).join("")}
            </select>
          </label>
          <label class="field">Nombre
            <input class="campo" name="nombre" maxlength="40" placeholder="Ej: Caja 3" required>
          </label>
          <div class="actions">
            <button type="button" class="btn g" data-go="cajas">Cancelar</button>
            <button class="btn y" type="submit">Guardar caja</button>
          </div>
        </form>
      </div>`;

    $("#form-nueva").onsubmit = (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const sucursal_id = Number(fd.get("sucursal_id"));
      const nombre = String(fd.get("nombre") || "").trim();
      if (!nombre) return flash("Ingresa un nombre", true);
      if (state.cajas.some((c) => c.sucursal_id === sucursal_id && c.nombre.toLowerCase() === nombre.toLowerCase())) {
        return flash("Ya existe una caja con ese nombre en la sucursal", true);
      }
      state.cajas.push({ id: state.next.caja++, sucursal_id, nombre });
      save();
      flash("Caja creada");
      go("cajas");
    };
  }

  function renderAbrir() {
    const libres = state.cajas.filter((c) => !turnoAbierto(c.id));
    const selected = preselectCaja && libres.some((c) => c.id === preselectCaja) ? preselectCaja : "";

    vista.innerHTML = `
      <div class="panel form-card">
        <h3>Abrir turno</h3>
        <p>El turno queda a nombre de <b>${state.usuario}</b>. No se puede abrir una caja que ya tiene turno abierto.</p>
        ${
          libres.length
            ? `<form id="form-abrir">
                <label class="field">Caja
                  <select class="campo" name="caja_id" required>
                    <option value="">Selecciona…</option>
                    ${libres
                      .map(
                        (c) =>
                          `<option value="${c.id}" ${Number(selected) === c.id ? "selected" : ""}>${sucursalNombre(c.sucursal_id)} · ${c.nombre}</option>`
                      )
                      .join("")}
                  </select>
                </label>
                <label class="field">Monto inicial
                  <input class="campo" type="number" name="monto_inicial" step="0.01" min="0" placeholder="Ej: 100.00" required>
                </label>
                <div class="actions">
                  <button type="button" class="btn g" data-go="cajas">Cancelar</button>
                  <button class="btn y" type="submit">Abrir</button>
                </div>
              </form>`
            : `<div class="empty">No hay cajas libres para abrir</div>
               <div class="actions"><button type="button" class="btn g" data-go="cajas">Volver</button></div>`
        }
      </div>`;

    const form = $("#form-abrir");
    if (!form) return;
    form.onsubmit = (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const caja_id = Number(fd.get("caja_id"));
      const monto_inicial = Number(fd.get("monto_inicial"));
      if (!caja_id) return flash("Selecciona una caja", true);
      if (Number.isNaN(monto_inicial) || monto_inicial < 0) return flash("Monto inválido", true);
      if (turnoAbierto(caja_id)) return flash("Esa caja ya tiene un turno abierto", true);
      const now = new Date();
      const stamp = now.toISOString().slice(0, 19).replace("T", " ");
      state.historial.push({
        id: state.next.historia++,
        caja_id,
        empleado: state.usuario,
        apertura: stamp,
        cierre: null,
        monto_inicial,
        monto_final: null,
        estado: "ABIERTA",
      });
      save();
      preselectCaja = null;
      flash("Turno abierto");
      go("cajas");
    };
  }

  function renderCerrar() {
    const abiertas = state.historial.filter((h) => h.estado === "ABIERTA");
    const selected =
      preselectHistoria && abiertas.some((h) => h.id === preselectHistoria)
        ? preselectHistoria
        : "";

    vista.innerHTML = `
      <div class="panel form-card">
        <h3>Cerrar turno</h3>
        <p>Registra el monto final del turno abierto.</p>
        ${
          abiertas.length
            ? `<form id="form-cerrar">
                <label class="field">Turno
                  <select class="campo" name="historia_id" required>
                    <option value="">Selecciona…</option>
                    ${abiertas
                      .map((h) => {
                        const c = cajaDe(h.caja_id);
                        return `<option value="${h.id}" ${Number(selected) === h.id ? "selected" : ""}>${sucursalNombre(c?.sucursal_id)} · ${c?.nombre} · ${h.empleado} · ini ${money(h.monto_inicial)}</option>`;
                      })
                      .join("")}
                  </select>
                </label>
                <label class="field">Monto final
                  <input class="campo" type="number" name="monto_final" step="0.01" min="0" placeholder="Ej: 420.00" required>
                </label>
                <div class="actions">
                  <button type="button" class="btn g" data-go="historial">Cancelar</button>
                  <button class="btn" type="submit">Cerrar turno</button>
                </div>
              </form>`
            : `<div class="empty">No hay turnos abiertos</div>
               <div class="actions"><button type="button" class="btn g" data-go="cajas">Volver</button></div>`
        }
      </div>`;

    const form = $("#form-cerrar");
    if (!form) return;
    form.onsubmit = (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const historia_id = Number(fd.get("historia_id"));
      const monto_final = Number(fd.get("monto_final"));
      const h = state.historial.find((x) => x.id === historia_id && x.estado === "ABIERTA");
      if (!h) return flash("Turno no encontrado", true);
      if (Number.isNaN(monto_final) || monto_final < 0) return flash("Monto inválido", true);
      h.monto_final = monto_final;
      h.cierre = new Date().toISOString().slice(0, 19).replace("T", " ");
      h.estado = "CERRADA";
      save();
      preselectHistoria = null;
      flash("Turno cerrado");
      go("historial");
    };
  }

  function renderIncidentes() {
    const q = $("#filtro-buscar")?.value || "";
    let filas = state.incidentes
      .map((i) => ({ ...i, sucursal: sucursalNombre(i.sucursal_id) }))
      .sort((a, b) => b.id - a.id);
    filas = filterRows(q, filas);

    vista.innerHTML = `
      <div class="panel">
        <div class="tools">
          <h3>Incidentes <span class="count">${filas.length}</span></h3>
          <input class="search" id="filtro-buscar" placeholder="Buscar…" value="${q.replaceAll('"', "&quot;")}">
          <button type="button" class="btn y" data-go="incidente-form">+ Nuevo</button>
        </div>
        <div class="scroll">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Sucursal</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th></th>
              </tr>
            </thead>
            <tbody>
              ${filas
                .map(
                  (f) => `
                <tr>
                  <td>${f.id}</td>
                  <td>${f.sucursal}</td>
                  <td><b>${f.tipo}</b></td>
                  <td>${f.descripcion}</td>
                  <td>${f.fecha}</td>
                  <td>${tagEstado(f.estado)}</td>
                  <td>
                    <button type="button" class="btn g sm" data-edit-inc="${f.id}">Editar</button>
                    <button type="button" class="btn r sm" data-del-inc="${f.id}">Eliminar</button>
                  </td>
                </tr>`
                )
                .join("")}
            </tbody>
          </table>
          ${filas.length ? "" : '<div class="empty">Sin incidentes</div>'}
        </div>
      </div>`;

    $("#filtro-buscar").oninput = () => renderIncidentes();
  }

  function renderIncidenteForm() {
    const item = editIncidenteId
      ? state.incidentes.find((i) => i.id === editIncidenteId)
      : null;

    vista.innerHTML = `
      <div class="panel form-card">
        <h3>${item ? "Editar incidente" : "Nuevo incidente"}</h3>
        <p>Estados: ABIERTO o RESUELTO. Si dejas la fecha vacía, se usa la hora actual.</p>
        <form id="form-incidente">
          <label class="field">Sucursal
            <select class="campo" name="sucursal_id" required>
              ${state.sucursales
                .map(
                  (s) =>
                    `<option value="${s.id}" ${item?.sucursal_id === s.id ? "selected" : ""}>${s.nombre}</option>`
                )
                .join("")}
            </select>
          </label>
          <label class="field">Tipo
            <input class="campo" name="tipo" maxlength="20" placeholder="Ej: Seguridad, Equipos, Stock" value="${item?.tipo || ""}" required>
          </label>
          <label class="field">Descripción
            <textarea class="campo" name="descripcion" maxlength="250" required>${item?.descripcion || ""}</textarea>
          </label>
          <label class="field">Fecha
            <input class="campo" type="datetime-local" name="fecha" value="${
              item?.fecha ? item.fecha.replace(" ", "T").slice(0, 16) : ""
            }">
          </label>
          <label class="field">Estado
            <select class="campo" name="estado" required>
              <option value="ABIERTO" ${!item || item.estado === "ABIERTO" ? "selected" : ""}>ABIERTO</option>
              <option value="RESUELTO" ${item?.estado === "RESUELTO" ? "selected" : ""}>RESUELTO</option>
            </select>
          </label>
          <div class="actions">
            <button type="button" class="btn g" data-go="incidentes">Cancelar</button>
            <button class="btn y" type="submit">Guardar</button>
          </div>
        </form>
      </div>`;

    $("#form-incidente").onsubmit = (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const tipo = String(fd.get("tipo") || "").trim();
      const descripcion = String(fd.get("descripcion") || "").trim();
      const estado = String(fd.get("estado"));
      let fecha = String(fd.get("fecha") || "").trim();
      if (!tipo || !descripcion) return flash("Completa tipo y descripción", true);
      if (!["ABIERTO", "RESUELTO"].includes(estado)) return flash("Estado inválido", true);
      if (!fecha) fecha = new Date().toISOString().slice(0, 19).replace("T", " ");
      else fecha = fecha.replace("T", " ") + ":00";

      const payload = {
        sucursal_id: Number(fd.get("sucursal_id")),
        tipo,
        descripcion,
        fecha,
        estado,
      };

      if (item) Object.assign(item, payload);
      else state.incidentes.push({ id: state.next.incidente++, ...payload });

      save();
      editIncidenteId = null;
      flash(item ? "Incidente actualizado" : "Incidente registrado");
      go("incidentes");
    };
  }

  function render() {
    ({
      cajas: renderCajas,
      historial: renderHistorial,
      nueva: renderNueva,
      abrir: renderAbrir,
      cerrar: renderCerrar,
      incidentes: renderIncidentes,
      "incidente-form": renderIncidenteForm,
    })[view]?.();
  }

  document.querySelector("aside").addEventListener("click", (e) => {
    const btn = e.target.closest("[data-view]");
    if (!btn) return;
    if (btn.dataset.view === "incidente-form") editIncidenteId = null;
    go(btn.dataset.view);
  });

  document.body.addEventListener("click", (e) => {
    const t = e.target.closest("[data-go],[data-abrir],[data-cerrar],[data-edit-inc],[data-del-inc]");
    if (!t) return;
    if (t.dataset.go) {
      if (t.dataset.go === "incidente-form") editIncidenteId = null;
      return go(t.dataset.go);
    }
    if (t.dataset.abrir) return go("abrir", { cajaId: Number(t.dataset.abrir) });
    if (t.dataset.cerrar) return go("cerrar", { historiaId: Number(t.dataset.cerrar) });
    if (t.dataset.editInc) return go("incidente-form", { editId: Number(t.dataset.editInc) });
    if (t.dataset.delInc) {
      const id = Number(t.dataset.delInc);
      if (!confirm("¿Eliminar este incidente?")) return;
      state.incidentes = state.incidentes.filter((i) => i.id !== id);
      save();
      flash("Incidente eliminado");
      renderIncidentes();
    }
  });

  $("#btn-reset").onclick = () => {
    if (!confirm("¿Restaurar los datos de ejemplo?")) return;
    state = seed();
    save();
    flash("Datos restaurados");
    go("cajas");
  };

  go("cajas");
})();
