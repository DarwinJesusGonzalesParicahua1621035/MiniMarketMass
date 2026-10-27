<h2>Categorías</h2>
<form method="post" action="productos.php?m=categorias&a=guardar">
    <input type="hidden" name="id" value="<?= e($editando['id'] ?? '') ?>">
    <label for="nombre"><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></label>
    <input id="nombre" name="nombre" maxlength="60" required value="<?= e($editando['nombre'] ?? '') ?>">
    <button>Guardar</button>
    <?php if ($editando): ?><a href="productos.php?m=categorias">Cancelar</a><?php endif; ?>
</form>

<table>
    <tr><th>Nombre</th><th>Productos</th><th>Acciones</th></tr>
    <?php foreach ($categorias as $c): ?>
    <tr>
        <td><?= e($c['nombre']) ?></td>
        <td><?= (int)$c['total_productos'] ?></td>
        <td>
            <a href="productos.php?m=categorias&editar=<?= (int)$c['id'] ?>">Editar</a>
            <form class="inline" method="post" action="productos.php?m=categorias&a=eliminar"
                  onsubmit="return confirm('¿Eliminar la categoría?')">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button>Eliminar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>