<?php
$activo = $activo ?? '';
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));

/* Etapa del turno: 0 = sin asistencia · 1 = falta abrir caja · 2 = caja abierta · 3 = turno terminado */
$uid   = (int)$user['id'];
$turno = $turno ?? (new PosRepository())->turno($uid);
$asis  = $turno ? null : (new AsistenciaRepository())->deHoy($uid);
$fase  = $turno ? 2 : ($asis ? ($asis['salida'] ? 3 : 1) : 0);
try {
    $q2 = Database::pdo()->prepare('SELECT totp_activo FROM empleado WHERE id = ?');
    $q2->execute([$uid]);
    $segActivo = (bool)$q2->fetchColumn();
} catch (Throwable $e) {
    $segActivo = false;
}

$urlCaja  = Controller::url('cajero/caja');
$urlVenta = Controller::url('cajero/venta');
?>
<style>
/* Lateral del cajero: mismas reglas en TODAS las pantallas (venta, caja, clientes),
   sin importar si cargan app.css o cajero-panel.css. El menú hace scroll por dentro
   y el bloque del usuario queda siempre fijo abajo, dentro del lateral. */
#aside{width:240px;height:100vh;position:sticky;top:0;display:flex;flex-direction:column;overflow:hidden;
  padding:18px 14px;background:#0F1115;color:#cfd3da;border-radius:0 26px 26px 0;flex-shrink:0}
#aside .logo{flex-shrink:0;padding:0 6px 10px}
#aside .rol{flex-shrink:0;margin:0 0 4px;padding:8px;border-radius:12px;background:#FFC400;color:#0F1115;
  font-size:18px;font-weight:900;letter-spacing:.7px;text-align:center}
#aside nav{flex:1 1 auto;min-height:0;margin:0;overflow-y:auto;overflow-x:hidden;padding-right:2px;
  scrollbar-width:thin;scrollbar-color:#2a2e38 transparent}
#aside nav::-webkit-scrollbar{width:6px}
#aside nav::-webkit-scrollbar-thumb{background:#2a2e38;border-radius:6px}
#aside nav h6{margin:10px 10px 4px;font-size:10px;letter-spacing:1.4px;text-transform:uppercase;color:#5B616C;opacity:1}
#aside nav a,#aside nav button{display:flex;align-items:center;gap:11px;width:100%;padding:8px 12px;margin:0 0 2px;
  border:0;border-radius:10px;background:transparent;color:#cfd3da;font:inherit;font-size:14px;text-align:left;
  text-decoration:none;cursor:pointer;transition:.15s}
#aside nav a:hover,#aside nav button:hover{background:#1A1D24;color:#fff}
#aside nav a.on{background:#FFC400;color:#0F1115;font-weight:700}
#aside nav a.out,#aside nav button.out{color:#ff9a9a}
#aside nav a span,#aside nav button span,#aside nav .done span{width:20px;text-align:center}
#aside nav .done{display:flex;align-items:center;gap:11px;padding:10px 12px;margin-bottom:2px;color:#7fd9a3;font-size:14px}
#aside nav a .ins{margin-left:auto;padding:2px 9px;border-radius:999px;background:#111;color:#ffc400;font-size:11px;font-weight:700;font-style:normal;white-space:nowrap}
#aside nav a .ins.ok{background:#16a34a;color:#fff}
#aside .me{flex-shrink:0;margin:8px 0 0;padding:10px;display:flex;align-items:center;gap:10px;
  border-radius:14px;background:#1A1D24;color:#fff;font-size:13px;line-height:1.3}
#aside .me b{width:34px;height:34px;flex-shrink:0;border-radius:50%;background:#1F4FD8;display:grid;place-items:center}
#aside .me small{display:block;color:#9AA0AA;font-size:11px}
#aside .me form{margin:0 0 0 auto}
#aside .me form button{background:none;border:0;padding:0;font:inherit;font-size:11px;font-weight:600;color:#FFC400;cursor:pointer}
</style>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <div class="rol">CAJERO</div>
  <nav>
    <?php if ($fase === 0): ?>

      <h6>Inicio de turno</h6>
      <a href="<?= $urlCaja ?>" class="on"><span>🕘</span>Registrar asistencia</a>

    <?php elseif ($fase === 1): ?>

      <h6>Inicio de turno</h6>
      <div class="done"><span>✅</span>Asistencia registrada</div>
      <a href="<?= $urlCaja ?>" class="on"><span>💵</span>Abrir caja</a>
      <h6>Fin de turno</h6>
      <a href="<?= $urlCaja ?>#terminar" id="navTerminar" class="out"><span>🚪</span>Terminar mi turno</a>

    <?php elseif ($fase === 2): ?>

      <h6>Operación</h6>
      <a href="<?= $urlVenta ?>" class="<?= $activo === 'venta' ? 'on' : '' ?>"><span>🛒</span>Nueva venta</a>
      <a href="<?= $urlCaja ?>" id="navCaja" class="<?= $activo === 'caja' ? 'on' : '' ?>"><span>💵</span>Mi caja</a>
      <h6>Consulta</h6>
      <a href="<?= Controller::url('cajero/clientes') ?>" class="<?= $activo === 'clientes' ? 'on' : '' ?>"><span>👥</span>Clientes</a>
      <h6>Reportes</h6>
      <button type="button" data-rep="incidencia"><span>⚠️</span>Incidencias</button>
      <button type="button" data-rep="stock"><span>📦</span>Merma / ajuste</button>
      <h6>Fin de turno</h6>
      <a href="<?= $urlCaja ?>#cierre" id="navCierre" class="out"><span>🔒</span>Cerrar caja</a>

    <?php else: ?>

      <h6>Turno finalizado</h6>
      <div class="done"><span>✅</span>Salida registrada</div>

    <?php endif; ?>

    <h6>Cuenta</h6>
    <a href="<?= Controller::url('seguridad/index') ?>" class="<?= $activo === 'seguridad' ? 'on' : '' ?>">
      <span>🛡️</span>Seguridad
      <em class="ins <?= $segActivo ? 'ok' : '' ?>"><?= $segActivo ? 'Activado' : 'Recomendado' ?></em>
    </a>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small></div>
    <form method="post" action="<?= Controller::url('auth/salir') ?>">
      <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
      <button type="submit" title="Cerrar sesión">Salir</button>
    </form></div>
</aside>
<?php
if ($fase === 2) {
    if (!isset($reporte)) {
        $reporte = [
            'urlIncidencia' => Controller::url('cajero/incidencia'),
            'urlStock'      => Controller::url('cajero/stock'),
            'csrf'          => Controller::csrf(),
            'sucursal'      => (string)($sucursal ?? ''),
            'caja'          => (string)($turno['caja'] ?? ''),
            'productos'     => array_map(fn($p) => [
                'id' => (int)$p['id'], 'nombre' => $p['nombre'], 'categoria' => $p['categoria'],
                'unidad' => $p['unidad'], 'stock' => (float)$p['stock'],
            ], (new PosRepository())->productos((int)$turno['sucursal_id'])),
            'tipos'         => ['Equipos', 'Seguridad', 'Stock', 'Otro'],
        ];
    }
    require __DIR__ . '/_reportes.php';
}
?>