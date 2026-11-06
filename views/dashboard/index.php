<?php
// Colores fijos por sucursal (la misma sucursal tiene el mismo color en los dos gráficos)
$paleta = ['#1F4FD8', '#F26B3A', '#0F9D9A', '#8E44AD', '#C2185B'];
$colorSuc = [];
$i = 0;
foreach ($sucursales as $id => $_) {
    $colorSuc[$id] = $paleta[$i++ % count($paleta)];
}
$maxDia = 1;
foreach ($semana as $d) {
    $maxDia = max($maxDia, ...array_values($d['ventas']) ?: [0]);
}
$corto = fn($n) => $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'k' : number_format($n, 0);
$colores = ['EFECTIVO' => '#1F4FD8', 'PLIN' => '#17a35a', 'YAPE' => '#7a3cff', 'TARJETA' => '#FFC400'];
$totSuc = [];
foreach ($sucursales as $id => $_) {
    $totSuc[$id] = array_sum($medios[$id] ?? []);
}
$maxSuc = max(1, ...array_values($totSuc) ?: [0]);
$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$nombre = explode(' ', trim($user['nombre']))[0];
$var = function ($hoy, $ayer) {
    if ($ayer <= 0) return null;
    return round(($hoy - $ayer) / $ayer * 100, 1);
};
$vVentas = $var($k['ventas_hoy'], $k['ventas_ayer']);
$vComp = $var($k['comprobantes'], $k['comprobantes_ayer']);
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/dashboard.css">
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
  <div class="card"><h3>Ventas de la semana por sucursal</h3><small>Total por día en soles, comparando sucursales</small>
    <ul class="leyenda-suc">
      <?php foreach ($sucursales as $id => $n): ?><li><i style="background:<?= $colorSuc[$id] ?>"></i><?= e($n) ?></li><?php endforeach; ?>
    </ul>
    <div class="gs">
      <?php foreach ($semana as $d): ?>
        <div class="gs-dia">
          <div class="gs-grupo">
            <?php foreach ($d['ventas'] as $id => $v): ?>
              <div class="gs-col" title="<?= e($sucursales[$id]) ?>: <?= e(money($v)) ?>">
                <b><?= $v > 0 ? $corto($v) : '' ?></b><span style="height:<?= $v / $maxDia * 100 ?>%;background:<?= $colorSuc[$id] ?>"></span>
              </div>
            <?php endforeach; ?>
          </div>
          <em><?= e($d['dia']) ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><h3>Medios de pago por sucursal</h3><small>Últimos 7 días</small>
    <div class="mp-list">
      <?php foreach ($sucursales as $id => $n): $tot = $totSuc[$id]; ?>
        <div class="mp-row">
          <div class="mp-top"><span><i style="background:<?= $colorSuc[$id] ?>"></i><?= e($n) ?></span><b><?= money($tot) ?></b></div>
          <div class="mp-track"><div class="mp-bar" style="width:<?= $tot / $maxSuc * 100 ?>%">
            <?php foreach ($colores as $medio => $c): $monto = $medios[$id][$medio] ?? 0; if ($monto <= 0) continue; ?>
              <i style="flex:<?= $monto ?>;background:<?= $c ?>" title="<?= e(ucfirst(strtolower($medio))) ?>: <?= e(money($monto)) ?> (<?= round($monto / max($tot, 1) * 100) ?>%)"></i>
            <?php endforeach; ?>
          </div></div>
          <small class="mp-pct">
            <?php if ($tot > 0): foreach ($colores as $medio => $c): $monto = $medios[$id][$medio] ?? 0; if ($monto <= 0) continue; ?>
              <span><?= e(ucfirst(strtolower($medio))) ?> <b><?= round($monto / $tot * 100) ?>%</b></span>
            <?php endforeach; else: ?>Sin ventas<?php endif; ?>
          </small>
        </div>
      <?php endforeach; ?>
    </div>
    <ul class="leyenda-med">
      <?php foreach ($colores as $m => $c): ?><li><i style="background:<?= $c ?>"></i><?= e(ucfirst(strtolower($m))) ?></li><?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="grid">
  <div class="card sb-card"><h3>Sucursales</h3><small>Estado de cajas y asistencia del personal de hoy</small>
    <div class="sb-grid">
      <?php foreach ($sedes as $sd):
          $activa = $sd['abiertas'] > 0;
          $lblCaja = $sd['cajas'] === 0 ? 'Sin cajas registradas' : $sd['abiertas'] . ' de ' . $sd['cajas'] . ($sd['cajas'] === 1 ? ' caja abierta' : ' cajas abiertas'); ?>
        <div class="sb <?= $activa ? 'on' : 'off' ?>">
          <div class="sb-top">
            <div class="sb-nom"><b><?= e($sd['nombre']) ?></b><small><?= e($sd['direccion']) ?></small></div>
            <span class="sb-est"><?= $activa ? 'Activa' : 'Cerrada' ?></span>
          </div>
          <p class="sb-caja"><?= e($lblCaja) ?></p>
          <div class="sb-emps">
            <?php foreach ($sd['empleados'] as $em):
                $tip = $em['nombre'] . ' · ' . ucfirst(strtolower($em['rol'])) . ' · ' . [
                    'temprano'  => 'Temprano (' . ($em['entrada'] ? date('H:i', strtotime($em['entrada'])) : '') . ')',
                    'tarde'     => 'Tarde (' . ($em['entrada'] ? date('H:i', strtotime($em['entrada'])) : '') . ')',
                    'falta'     => 'Falta',
                    'pendiente' => 'Sin marcar asistencia',
                ][$em['clase']]; ?>
              <span class="sb-chip <?= e($em['clase']) ?>" title="<?= e($tip) ?>"><i></i><?= e($em['nombre']) ?></span>
            <?php endforeach; ?>
            <?php if (!$sd['empleados']): ?><small class="sb-vacio">Sin empleados asignados</small><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <ul class="sb-leyenda">
      <li><span class="sb-chip temprano"><i></i>Temprano</span> hasta 8:30</li>
      <li><span class="sb-chip tarde"><i></i>Tarde</span> después de 8:30</li>
      <li><span class="sb-chip pendiente"><i></i>Sin marcar</span> no registró asistencia</li>
      <li><span class="sb-chip falta"><i></i>Falta</span> registrada por el administrador</li>
    </ul>
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
