<h2>Incidentes</h2>
<h3>Registrar incidente</h3>
<form method="post" action="productos.php?m=incidentes&a=crear">
    <label for="sucursal_id">Sucursal</label>
    <select id="sucursal_id" name="sucursal_id" required>
        <option value="">Elige una sucursal</option>
        <?php foreach ($sucursales as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= e($s['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <label for="tipo">Tipo</label>
    <input id="tipo" name="tipo" maxlength="20" required>
    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" maxlength="250" required></textarea>
    <button>Registrar como abierto</button>
</form>

<h3>Incidentes registrados</h3>
<form method="get" action="productos.php">
    <input type="hidden" name="m" value="incidentes">
    <select name="sucursal">
        <option value="0">Todas las sucursales</option>
        <?php foreach ($sucursales as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= $sucursalId === (int)$s['id'] ? 'selected' : '' ?>>
            <?= e($s['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <select name="estado">
        <option value="">Todos los estados</option>
        <option value="ABIERTO" <?= $estado === 'ABIERTO' ? 'selected' : '' ?>>Abiertos</option>
        <option value="RESUELTO" <?= $estado === 'RESUELTO' ? 'selected' : '' ?>>Resueltos</option>
    </select>
    <button>Filtrar</button>
</form>
<table>
    <tr><th>Sucursal</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado / acción</th></tr>
    <?php foreach ($incidentes as $i): ?>
    <tr>
        <td><?= e($i['sucursal']) ?></td>
        <td><?= e($i['tipo']) ?></td>
        <td><?= e($i['descripcion']) ?></td>
        <td><?= e($i['fecha']) ?></td>
        <td>
            <?= e($i['estado']) ?>
            <form class="inline" method="post" action="productos.php?m=incidentes&a=estado">
                <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
                <input type="hidden" name="estado" value="<?= $i['estado'] === 'ABIERTO' ? 'RESUELTO' : 'ABIERTO' ?>">
                <button><?= $i['estado'] === 'ABIERTO' ? 'Marcar resuelto' : 'Reabrir' ?></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$incidentes): ?><tr><td colspan="5">No hay incidentes para esos filtros.</td></tr><?php endif; ?>
</table>
