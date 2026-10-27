<div class="card detalle" style="margin-bottom:20px">
  <h3><?= e($v['comprobante']) ?> <?= celda($v['tipo_comprobante'], 'etiqueta') ?> <?= celda($v['estado'], 'etiqueta') ?></h3>
  <dl>
    <dt>Sucursal</dt><dd><?= e($v['sucursal']) ?></dd>
    <dt>Cajero</dt><dd><?= e($v['cajero']) ?></dd>
    <dt>Cliente</dt><dd><?= e($v['cliente']) ?></dd>
    <dt>Fecha</dt><dd><?= e($v['fecha']) ?></dd>
    <dt>Subtotal</dt><dd><?= money($v['subtotal']) ?></dd>
    <dt>IGV</dt><dd><?= money($v['igv']) ?></dd>
    <dt>Total</dt><dd><b><?= money($v['total']) ?></b></dd>
    <?php if ($v['motivo_anulacion']): ?><dt>Motivo de anulación</dt><dd><?= e($v['motivo_anulacion']) ?></dd><?php endif; ?>
  </dl>
</div>

<div class="tw">
  <div class="tools"><h3>Productos</h3></div>
  <div class="scroll"><table>
    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unit.</th><th>Total línea</th></tr></thead>
    <tbody>
    <?php foreach ($detalle as $d): ?>
      <tr><td><?= e($d['producto']) ?></td><td><?= (float)$d['cantidad'] ?></td><td><?= money($d['precio_unitario']) ?></td><td><?= money($d['total_linea']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$detalle): ?><div class="empty">Sin detalle registrado</div><?php endif; ?></div>
</div>

<div class="tw">
  <div class="tools"><h3>Pagos</h3></div>
  <div class="scroll"><table>
    <thead><tr><th>Medio</th><th>Monto</th><th>Referencia</th></tr></thead>
    <tbody>
    <?php foreach ($pagos as $p): ?>
      <tr><td><?= e($p['medio_pago']) ?></td><td><?= money($p['monto']) ?></td><td><?= e($p['referencia']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php if ($v['estado'] === 'EMITIDA'): ?>
<div class="tw">
  <form class="anular" method="post" action="<?= Controller::url('ventas/anular') ?>" data-confirm="¿Anular este comprobante? No se puede deshacer.">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
    <input name="motivo" placeholder="Motivo de anulación" maxlength="150" required>
    <button class="btn r">Anular comprobante</button>
    <a class="btn g" href="<?= Controller::url('ventas/index') ?>">Volver</a>
  </form>
</div>
<?php else: ?>
<a class="btn g" href="<?= Controller::url('ventas/index') ?>">Volver</a>
<?php endif; ?>