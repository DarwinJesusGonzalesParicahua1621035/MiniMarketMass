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

$urlCaja  = Controller::url('cajero/caja');
$urlVenta = Controller::url('cajero/venta');
?>
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
      <h6>Reportes</h6>
      <button type="button" data-rep="incidencia"><span>⚠️</span>Incidencias</button>
      <button type="button" data-rep="stock"><span>📦</span>Merma / ajuste</button>
      <h6>Fin de turno</h6>
      <a href="<?= $urlCaja ?>#cierre" id="navCierre" class="out"><span>🔒</span>Cerrar caja</a>

    <?php else: ?>

      <h6>Turno finalizado</h6>
      <div class="done"><span>✅</span>Salida registrada</div>

    <?php endif; ?>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small></div>
    <a href="<?= Controller::url('auth/salir') ?>" title="Cerrar sesión">Salir</a></div>
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
