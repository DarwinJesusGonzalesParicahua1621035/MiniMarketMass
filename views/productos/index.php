<div class="tw">
    <form class="tools" method="get" action="productos.php">
        <input type="hidden" name="m" value="productos">
        <h3>Productos <span class="tag b"><?= count($productos) ?></span></h3>
        <input class="search" name="q" placeholder="Buscar nombre o código…" value="<?= e($q) ?>">
        <select class="sel" name="categoria">
            <option value="0">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <select class="sel" name="estado">
            <option value="">Todos</option>
            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activos</option>
            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn g">Filtrar</button>
        <a class="btn y" href="productos.php?m=productos&a=nuevo">+ Nuevo</a>
    </form>

    <div class="scroll">
        <table>
            <thead>
                <tr><th>Código de barras</th><th>Nombre</th><th>Categoría</th><th>Unidad</th><th>Precio</th><th>Activo</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $p): ?>
                <tr class="<?= $p['activo'] ? '' : 'inactivo' ?>">
                    <td><?= e($p['codigo_barras']) ?></td>
                    <td><b><?= e($p['nombre']) ?></b></td>
                    <td><?= e($p['categoria']) ?></td>
                    <td><?= e($p['unidad']) ?></td>
                    <td>S/ <?= number_format((float)$p['precio'], 2) ?></td>
                    <td><?= $p['activo'] ? '<span class="tag ok">Sí</span>' : '<span class="tag bad">No</span>' ?></td>
                    <td>
                        <a class="act" title="Editar" href="productos.php?m=productos&a=editar&id=<?= (int)$p['id'] ?>">✏️</a>
                        <a class="act" title="Historial de precios" href="productos.php?m=productos&a=historial&id=<?= (int)$p['id'] ?>">💲</a>
                        <form class="inline" method="post" action="productos.php?m=productos&a=estado">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="activo" value="<?= $p['activo'] ? 0 : 1 ?>">
                            <button class="act" title="<?= $p['activo'] ? 'Desactivar' : 'Activar' ?>"><?= $p['activo'] ? '🚫' : '✅' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$productos): ?><div class="empty">Sin resultados</div><?php endif; ?>
    </div>
</div>