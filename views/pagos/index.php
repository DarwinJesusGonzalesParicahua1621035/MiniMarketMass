<?php
$estilo = ['EFECTIVO' => 'b', 'YAPE' => 'p', 'PLIN' => '', 'TARJETA' => 'y'];
?>
<section class="kp-grid">
  <?php foreach ($totales as $m => $t): ?>
    <div class="kp <?= $estilo[$m] ?>"><p><?= e(ucfirst(strtolower($m))) ?></p><h2><?= money($t) ?></h2></div>
  <?php endforeach; ?>
  <div class="kp r"><p>Total cobrado</p><h2><?= money(array_sum($totales)) ?></h2><em class="n">Sin ventas anuladas</em></div>
</section>

<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="pagos/index">
    <h3>Pagos <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="medio" onchange="this.form.submit()">
      <option value="">Todos los medios</option>
      <?php foreach (PagoRepository::MEDIOS as $m): ?>
        <option value="<?= $m ?>" <?= $m === $medioSel ? 'selected' : '' ?>><?= $m ?></option>
      <?php endforeach; ?>
    </select>
    <select class="search" name="sucursal" onchange="this.form.submit()">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $id => $n): ?>
        <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" type="date" name="desde" value="<?= e($desde) ?>" onchange="this.form.submit()">
    <input class="search" type="date" name="hasta" value="<?= e($hasta) ?>" onchange="this.form.submit()">
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>Comprobante</th><th>Fecha</th><th>Sucursal</th><th>Cajero</th><th>Medio</th>
          <th style="text-align:right">Monto</th><th>Referencia</th><th>Venta</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f): ?>
        <tr>
          <td><a href="<?= Controller::url('ventas/ver', ['id' => (int)$f['venta_id']]) ?>"><b><?= e($f['comprobante']) ?></b></a></td>
          <td><?= e($f['fecha']) ?></td>
          <td><?= e($f['sucursal']) ?></td>
          <td><?= e($f['cajero']) ?></td>
          <td><?= celda($f['medio_pago'], 'etiqueta') ?></td>
          <td style="text-align:right"><?= money($f['monto']) ?></td>
          <td><?= e($f['referencia']) ?></td>
          <td>
            <?= celda($f['estado'], 'etiqueta') ?>
            <?php if ((int)$f['medios'] > 1): ?><span class="tag b"><?= (int)$f['medios'] ?> medios</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin pagos en este periodo</div><?php endif; ?>
  </div>
</div>
