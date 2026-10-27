<?php if ($flash): ?>
<div class="alert <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>"><?= ModuleSupport::escape($flash[1]) ?></div>
<?php endif; ?>

<div class="card">
  <h2>Abrir caja</h2>
  <?php if (!$cajas): ?>
    <p>Primero registra una caja.</p>
  <?php elseif (!$empleados): ?>
    <p>No hay empleados activos disponibles.</p>
  <?php else: ?>
  <form class="inline-form" method="post" action="/historia-caja/abrir">
    <div class="grow">
      <label for="caja_id">Caja</label>
      <select class="field" id="caja_id" name="caja_id" required>
        <option value="">Selecciona una caja</option>
        <?php foreach ($cajas as $caja): ?>
        <option value="<?= (int)$caja['id'] ?>">
          <?= ModuleSupport::escape($caja['sucursal'] . ' · ' . $caja['nombre']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grow">
      <label for="empleado_id">Empleado responsable</label>
      <select class="field" id="empleado_id" name="empleado_id" required>
        <option value="">Selecciona un empleado</option>
        <?php foreach ($empleados as $empleado): ?>
        <option value="<?= (int)$empleado['id'] ?>">
          <?= ModuleSupport::escape($empleado['sucursal'] . ' · ' . $empleado['nombre']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="monto_inicial">Monto inicial (S/)</label>
      <input class="field" id="monto_inicial" name="monto_inicial" type="number"
             min="0" max="99999999.99" step="0.01" required>
    </div>
    <button class="btn y" type="submit">Abrir caja</button>
  </form>
  <p>El empleado debe pertenecer a la misma sucursal que la caja.</p>
  <?php endif; ?>
</div>

<div class="tw">
  <form class="tools" method="get" action="/historia-caja">
    <h3>Movimientos</h3>
    <select class="sel" name="estado">
      <option value="">Todos los estados</option>
      <option value="ABIERTA" <?= $estado === 'ABIERTA' ? 'selected' : '' ?>>Abiertas</option>
      <option value="CERRADA" <?= $estado === 'CERRADA' ? 'selected' : '' ?>>Cerradas</option>
    </select>
    <button class="btn g" type="submit">Filtrar</button>
  </form>
  <div class="scroll">
    <table>
      <thead><tr><th>Sucursal / caja</th><th>Empleado</th><th>Apertura</th><th>Cierre</th><th>Monto inicial</th><th>Monto final</th><th>Estado / acción</th></tr></thead>
      <tbody>
      <?php foreach ($historial as $registro): ?>
        <tr>
          <td><?= ModuleSupport::escape($registro['sucursal'] . ' / ' . $registro['caja']) ?></td>
          <td><?= ModuleSupport::escape($registro['empleado']) ?></td>
          <td><?= ModuleSupport::escape($registro['apertura']) ?></td>
          <td><?= ModuleSupport::escape($registro['cierre'] ?? '—') ?></td>
          <td>S/ <?= number_format((float)$registro['monto_inicial'], 2) ?></td>
          <td><?= $registro['monto_final'] === null ? '—' : 'S/ ' . number_format((float)$registro['monto_final'], 2) ?></td>
          <td>
            <?php if ($registro['estado'] === 'ABIERTA'): ?>
              <span class="tag y">Abierta</span>
              <form class="inline-form" method="post" action="/historia-caja/cerrar">
                <input type="hidden" name="id" value="<?= (int)$registro['id'] ?>">
                <input class="field" name="monto_final" type="number" min="0" max="99999999.99"
                       step="0.01" aria-label="Monto final" placeholder="Monto final" required>
                <button class="btn" type="submit">Cerrar</button>
              </form>
            <?php else: ?>
              <span class="tag ok">Cerrada</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$historial): ?><tr><td class="empty" colspan="7">No hay aperturas registradas.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
