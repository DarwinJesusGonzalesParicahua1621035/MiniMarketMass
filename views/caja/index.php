<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="caja/index">
    <h3>Cajas <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="sucursal" onchange="this.form.submit()">
      <option value="">Todas las sucursales</option>
      <?php foreach ($sucursales as $id => $n): ?>
        <option value="<?= (int)$id ?>" <?= (int)$id === (int)$sucursalSel ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <a class="btn g" href="<?= Controller::url('caja/historial') ?>">Ver historial</a>
    <?php if ($esAdmin): ?><a class="btn g" href="<?= Controller::url('caja/nueva') ?>">+ Nueva caja</a><?php endif; ?>
    <a class="btn y" href="<?= Controller::url('caja/abrir') ?>">Abrir caja</a>
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>#</th><th>Sucursal</th><th>Caja</th><th>Estado</th><th>Responsable</th>
          <th>Apertura</th><th style="text-align:right">Monto inicial</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f): $abierta = !empty($f['historia_id']); ?>
        <tr>
          <td><?= (int)$f['id'] ?></td>
          <td><?= e($f['sucursal']) ?></td>
          <td><b><?= e($f['nombre']) ?></b></td>
          <td><?= $abierta ? celda('ABIERTA', 'etiqueta') : '<span class="tag">Sin turno</span>' ?></td>
          <td><?= $abierta ? e($f['empleado']) : '—' ?></td>
          <td><?= $abierta ? e($f['apertura']) : '—' ?></td>
          <td style="text-align:right"><?= $abierta ? money($f['monto_inicial']) : '—' ?></td>
          <td>
            <?php if ($abierta): ?>
              <a class="btn g" href="<?= Controller::url('caja/cerrar', ['id' => (int)$f['historia_id']]) ?>">Cerrar</a>
            <?php else: ?>
              <a class="btn" href="<?= Controller::url('caja/abrir', ['id' => (int)$f['id']]) ?>">Abrir</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin cajas registradas</div><?php endif; ?>
  </div>
</div>
