<?php
$max = max(1, ...array_column($semana, 1));
$totalMedios = max(1, array_sum(array_column($medios, 'total')));
?>
<section class="franja">
  <div class="kpi"><p>Ventas de hoy</p><h2><?= money($k['ventas_hoy']) ?></h2></div>
  <div class="kpi"><p>Comprobantes emitidos hoy</p><h2><?= $k['comprobantes'] ?></h2></div>
  <div class="kpi"><p>Productos con stock bajo</p><h2><?= $k['stock_bajo'] ?></h2><em class="d">Requieren reposición</em></div>
  <div class="kpi"><p>Incidentes abiertos</p><h2><?= $k['incidentes'] ?></h2><em class="d">Atender hoy</em></div>
</section>

<div class="grid">
  <div class="card"><h3>Ventas de la semana</h3><small>Total por día en soles</small>
    <div class="bars">
      <?php foreach ($semana as $s): ?>
        <div class="<?= $s[1] == $max ? 'top' : '' ?>"><b><?= number_format($s[1], 0) ?></b><span style="height:<?= $s[1] / $max * 100 ?>%"></span><?= e($s[0]) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card"><h3>Medios de pago</h3><small>Participación sobre ventas emitidas</small>
    <div class="pay">
      <?php foreach ($medios as $m): $p = round($m['total'] / $totalMedios * 100); ?>
        <div><label><span><?= e($m['medio']) ?></span><b><?= $p ?>%</b></label><i><s style="width:<?= $p ?>%"></s></i></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="tw">
  <div class="tools"><h3>Últimas ventas</h3><a class="btn g" href="<?= Controller::url('ventas/index') ?>">Ver todas</a></div>
  <div class="scroll"><table>
    <thead><tr><th>Comprobante</th><th>Sucursal</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($ultimas as $v): ?>
      <tr><td><b><?= e($v['comprobante']) ?></b></td><td><?= e($v['sucursal']) ?></td><td><?= e($v['cliente']) ?></td>
          <td><?= money($v['total']) ?></td><td><?= celda($v['estado'], 'etiqueta') ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>