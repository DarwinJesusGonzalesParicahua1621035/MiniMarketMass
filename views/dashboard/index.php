<?php
$max = max(1, ...array_column($semana, 1));
$totalMedios = max(1, array_sum(array_column($medios, 'total')));
$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre = explode(' ', trim($user['nombre']))[0];
$var = function ($hoy, $ayer) {
    if ($ayer <= 0) return null;
    return round(($hoy - $ayer) / $ayer * 100, 1);
};
$vVentas = $var($k['ventas_hoy'], $k['ventas_ayer']);
$vComp = $var($k['comprobantes'], $k['comprobantes_ayer']);
$colores = ['EFECTIVO' => '#1F4FD8', 'PLIN' => '#17a35a', 'YAPE' => '#7a3cff', 'TARJETA' => '#FFC400'];
$ini = 0;
$partes = [];
foreach ($medios as $m) {
    $p = $m['total'] / $totalMedios * 100;
    $c = $colores[$m['medio']] ?? '#9AA0AA';
    $partes[] = $c . ' ' . number_format($ini, 2, '.', '') . '% ' . number_format($ini + $p, 2, '.', '') . '%';
    $ini += $p;
}
$grad = $partes ? implode(',', $partes) : '#E3E5E9 0% 100%';
$sumaMedios = array_sum(array_column($medios, 'total'));
?>
<section class="hero">
  <div><h2><?= $saludo ?>, <?= e($nombre) ?>!</h2><p>Aquí tienes el resumen de tu tienda hoy.</p></div>
  <span><?= date('d/m/Y · H:i') ?></span>
</section>

<section class="kp-grid">
  <div class="kp y">
    <p>Ventas de hoy</p><h2><?= money($k['ventas_hoy']) ?></h2>
    <?php if ($vVentas !== null): ?><em class="<?= $vVentas < 0 ? 'd' : '' ?>"><?= $vVentas >= 0 ? '▲ +' : '▼ ' ?><?= $vVentas ?>% vs. ayer</em><?php else: ?><em class="n">Sin ventas ayer</em><?php endif; ?>
  </div>
  <div class="kp b">
    <p>Comprobantes emitidos hoy</p><h2><?= $k['comprobantes'] ?></h2>
    <?php if ($vComp !== null): ?><em class="<?= $vComp < 0 ? 'd' : '' ?>"><?= $vComp >= 0 ? '▲ +' : '▼ ' ?><?= $vComp ?>% vs. ayer</em><?php else: ?><em class="n">Sin comprobantes ayer</em><?php endif; ?>
  </div>
  <div class="kp r">
    <p>Productos con stock bajo</p><h2><?= $k['stock_bajo'] ?></h2><em class="d">Requieren reposición</em>
  </div>
  <div class="kp p">
    <p>Incidentes abiertos</p><h2><?= $k['incidentes'] ?></h2><em class="d">Atender hoy</em>
  </div>
</section>

<div class="grid">
  <div class="card"><h3>Ventas de la semana</h3><small>Total por día en soles</small>
    <div class="bars">
      <?php foreach ($semana as $s): ?>
        <div class="<?= $s[1] == $max ? 'top' : '' ?>"><b><?= number_format($s[1], 0) ?></b><span style="height:<?= $s[1] / $max * 100 ?>%"></span><?= e($s[0]) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><h3>Medios de pago</h3><small>Últimos 7 días</small>
    <div class="dona-wrap">
      <div class="dona" style="background:conic-gradient(<?= $grad ?>)"><div><small>Total</small><b><?= money($sumaMedios) ?></b></div></div>
      <ul class="leyenda">
        <?php foreach ($medios as $m): $p = round($m['total'] / $totalMedios * 100); ?>
          <li><i style="background:<?= $colores[$m['medio']] ?? '#9AA0AA' ?>"></i><span><?= e(ucfirst(strtolower($m['medio']))) ?></span><b><?= $p ?>%</b></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="grid">
  <div class="tw">
    <div class="tools"><h3>Últimas ventas</h3><a class="btn g" href="<?= Controller::url('ventas/index') ?>">Ver todas</a></div>
    <div class="scroll"><table>
      <thead><tr><th>Comprobante</th><th>Fecha / Hora</th><th>Sucursal</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
      <tbody>
      <?php foreach ($ultimas as $v): ?>
        <tr><td><b><?= e($v['comprobante']) ?></b></td><td><?= e($v['fecha']) ?></td><td><?= e($v['sucursal']) ?></td><td><?= e($v['cliente']) ?></td>
            <td><?= money($v['total']) ?></td><td><?= celda($v['estado'], 'etiqueta') ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card"><h3>Alertas y notificaciones</h3>
    <ul class="alertas">
      <?php foreach ($stockBajo as $a): ?>
        <li><i class="r">!</i><div><b>Stock bajo: <?= e($a['producto']) ?></b><small><?= e($a['sucursal']) ?> · quedan <?= (float)$a['cantidad'] ?></small></div></li>
      <?php endforeach; ?>
      <?php foreach ($incidentes as $a): ?>
        <li><i class="y">⚠</i><div><b><?= e($a['tipo']) ?></b><small><?= e($a['sucursal']) ?> · <?= e($a['descripcion']) ?></small></div></li>
      <?php endforeach; ?>
      <?php if (!$stockBajo && !$incidentes): ?><li class="vacio">Sin alertas por ahora</li><?php endif; ?>
    </ul>
  </div>
</div>

<section class="pie">
  <div><p>Stock total</p><h3><?= number_format($k['stock_total'], 0) ?></h3><small>productos en tienda</small></div>
  <div><p>Productos más vendidos</p>
    <?php foreach ($top as $i => $t): ?><small><?= $i + 1 ?>. <?= e($t['nombre']) ?></small><?php endforeach; ?>
    <?php if (!$top): ?><small>Sin ventas aún</small><?php endif; ?>
  </div>
  <div><p>Clientes registrados</p><h3><?= number_format($k['clientes']) ?></h3><small>en el sistema</small></div>
  <div><p>Ticket promedio hoy</p><h3><?= money($k['ticket']) ?></h3><small>por comprobante</small></div>
</section>