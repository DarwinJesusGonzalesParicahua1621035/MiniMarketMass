<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="caja/historial">
    <h3>Aperturas y cierres <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="estado" onchange="this.form.submit()">
      <option value="">Todos los estados</option>
      <?php foreach (['ABIERTA', 'CERRADA'] as $t): ?>
        <option value="<?= $t ?>" <?= $t === $estadoSel ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <a class="btn g" href="<?= Controller::url('caja/index') ?>">Ver cajas</a>
    <a class="btn y" href="<?= Controller::url('caja/cerrar') ?>">Cerrar caja</a>
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>#</th><th>Sucursal</th><th>Caja</th><th>Empleado</th><th>Apertura</th><th>Cierre</th>
          <th style="text-align:right">Inicial</th><th style="text-align:right">Final</th>
          <th style="text-align:right">Diferencia</th><th>Estado</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f):
          $dif = $f['monto_final'] !== null ? (float)$f['monto_final'] - (float)$f['monto_inicial'] : null; ?>
        <tr>
          <td><?= (int)$f['id'] ?></td>
          <td><?= e($f['sucursal']) ?></td>
          <td><?= e($f['caja']) ?></td>
          <td><?= e($f['empleado']) ?></td>
          <td><?= e($f['apertura']) ?></td>
          <td><?= e($f['cierre'] ?? '—') ?></td>
          <td style="text-align:right"><?= money($f['monto_inicial']) ?></td>
          <td style="text-align:right"><?= $f['monto_final'] !== null ? money($f['monto_final']) : '—' ?></td>
          <td style="text-align:right;font-weight:700;color:var(--<?= ($dif ?? 0) < 0 ? 'bad' : 'ok' ?>)"><?= $dif !== null ? money($dif) : '—' ?></td>
          <td><?= celda($f['estado'], 'etiqueta') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin aperturas registradas</div><?php endif; ?>
  </div>
</div>
