<h2>Historial de caja</h2>
<h3>Abrir caja</h3>
<?php if (!$cajas): ?>
<p>Primero registra una caja.</p>
<?php elseif (!$empleados): ?>
<p>No hay empleados activos disponibles.</p>
<?php else: ?>
<form method="post" action="productos.php?m=historia_caja&a=abrir">
    <label for="caja_id">Caja</label>
    <select id="caja_id" name="caja_id" required>
        <option value="">Elige una caja</option>
        <?php foreach ($cajas as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['sucursal'] . ' - ' . $c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="empleado_id">Empleado responsable (misma sucursal de la caja)</label>
    <select id="empleado_id" name="empleado_id" required>
        <option value="">Elige un empleado</option>
        <?php foreach ($empleados as $eEmpleado): ?>
        <option value="<?= (int)$eEmpleado['id'] ?>">
            <?= e($eEmpleado['sucursal'] . ' - ' . $eEmpleado['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <label for="monto_inicial">Monto inicial (S/)</label>
    <input id="monto_inicial" name="monto_inicial" type="number" min="0" max="99999999.99" step="0.01" required>
    <button>Abrir caja</button>
</form>
<?php endif; ?>

<h3>Movimientos</h3>
<form method="get" action="productos.php">
    <input type="hidden" name="m" value="historia_caja">
    <select name="estado">
        <option value="">Todos los estados</option>
        <option value="ABIERTA" <?= $estado === 'ABIERTA' ? 'selected' : '' ?>>Abiertas</option>
        <option value="CERRADA" <?= $estado === 'CERRADA' ? 'selected' : '' ?>>Cerradas</option>
    </select>
    <button>Filtrar</button>
</form>
<table>
    <tr><th>Sucursal / caja</th><th>Empleado</th><th>Apertura</th><th>Cierre</th><th>Monto inicial</th><th>Monto final</th><th>Estado / acción</th></tr>
    <?php foreach ($historial as $h): ?>
    <tr>
        <td><?= e($h['sucursal'] . ' / ' . $h['caja']) ?></td>
        <td><?= e($h['empleado']) ?></td>
        <td><?= e($h['apertura']) ?></td>
        <td><?= e($h['cierre'] ?? '—') ?></td>
        <td>S/ <?= number_format((float)$h['monto_inicial'], 2) ?></td>
        <td><?= $h['monto_final'] === null ? '—' : 'S/ ' . number_format((float)$h['monto_final'], 2) ?></td>
        <td>
            <?= e($h['estado']) ?>
            <?php if ($h['estado'] === 'ABIERTA'): ?>
            <form method="post" action="productos.php?m=historia_caja&a=cerrar">
                <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                <label for="monto_final_<?= (int)$h['id'] ?>">Monto final</label>
                <input id="monto_final_<?= (int)$h['id'] ?>" name="monto_final" type="number" min="0" max="99999999.99" step="0.01" required>
                <button>Cerrar caja</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$historial): ?><tr><td colspan="7">No hay aperturas de caja registradas.</td></tr><?php endif; ?>
</table>
