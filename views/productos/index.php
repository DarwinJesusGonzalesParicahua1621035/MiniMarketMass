<h2>Productos</h2>
<form method="get" action="productos.php">
    <input type="hidden" name="m" value="productos">
    <input name="q" placeholder="Nombre o código de barras" value="<?= e($q) ?>" style="width:240px">
    <select name="categoria" style="width:180px">
        <option value="0">Todas las categorías</option>
        <?php foreach ($categorias as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="estado" style="width:140px">
        <option value="">Todos</option>
        <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
        <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
    </select>
    <button>Filtrar</button>
    <a href="productos.php?m=productos&a=nuevo">+ Nuevo producto</a>
</form>

<table>
    <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Unidad</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr>
    <?php foreach ($productos as $p): ?>
    <tr class="<?= $p['activo'] ? '' : 'inactivo' ?>">
        <td><?= e($p['codigo_barras']) ?></td>
        <td><?= e($p['nombre']) ?></td>
        <td><?= e($p['categoria']) ?></td>
        <td><?= e($p['unidad']) ?></td>
        <td>S/ <?= number_format((float)$p['precio'], 2) ?></td>
        <td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td>
        <td>
            <a href="productos.php?m=productos&a=editar&id=<?= (int)$p['id'] ?>">Editar</a> |
            <a href="productos.php?m=productos&a=historial&id=<?= (int)$p['id'] ?>">Precios</a>
            <form class="inline" method="post" action="productos.php?m=productos&a=estado">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="activo" value="<?= $p['activo'] ? 0 : 1 ?>">
                <button><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$productos): ?><tr><td colspan="7">No hay productos.</td></tr><?php endif; ?>
</table>