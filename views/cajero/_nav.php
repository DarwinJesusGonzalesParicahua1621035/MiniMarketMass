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
$segActivo = !empty($user['totp_activo']);

$urlCaja  = Controller::url('cajero/caja');
$urlVenta = Controller::url('cajero/venta');
?>
<style>
/* Insignia de Seguridad y botón "Salir" (vienen de main; aquí para no depender de cajero-turno.css) */
#aside nav a .ins{margin-left:auto;padding:2px 9px;border-radius:999px;background:#111;color:#ffc400;font-size:11px;font-weight:700;font-style:normal;white-space:nowrap}
#aside nav a .ins.ok{background:#16a34a;color:#fff}
#aside .me form{margin:0 0 0 auto}
#aside .me form button{background:none;border:0;padding:0;font:inherit;font-size:11px;font-weight:600;color:var(--y);cursor:pointer}
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
      <a href="<?= Controller::url('cajero/ventas') ?>" class="<?= $activo === 'ventas' ? 'on' : '' ?>"><span>🧾</span>Mis ventas</a>
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