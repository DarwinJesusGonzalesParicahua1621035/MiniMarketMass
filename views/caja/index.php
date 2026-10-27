<h2>Cajas</h2>
<form method="post" action="productos.php?m=caja&a=guardar">
    <input type="hidden" name="id" value="<?= (int)($editando['id'] ?? 0) ?>">
    <label for="sucursal_id">Sucursal</label>
    <select id="sucursal_id" name="sucursal_id" required>
        <option value="">Elige una sucursal</option>
        <?php foreach ($sucursales as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= (int)($editando['sucursal_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
            <?= e($s['nombre']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <label for="nombre"><?= $editando ? 'Editar caja' : 'Nombre de la caja' ?></label>
    <input id="nombre" name="nombre" maxlength="40" required value="<?= e($editando['nombre'] ?? '') ?>">
    <button>Guardar</button>
    <?php if ($editando): ?><a href="productos.php?m=caja">Cancelar</a><?php endif; ?>
</form>

<table>
    <tr><th>Sucursal</th><th>Caja</th><th>Acciones</th></tr>
    <?php foreach ($cajas as $c): ?>
    <tr>
        <td><?= e($c['sucursal']) ?></td>
        <td><?= e($c['nombre']) ?></td>
        <td>
            <a href="productos.php?m=caja&editar=<?= (int)$c['id'] ?>">Editar</a>
            <form class="inline" method="post" action="productos.php?m=caja&a=eliminar"
                  onsubmit="return confirm('¿Eliminar esta caja?')">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button>Eliminar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$cajas): ?><tr><td colspan="3">No hay cajas registradas.</td></tr><?php endif; ?>
</table>
