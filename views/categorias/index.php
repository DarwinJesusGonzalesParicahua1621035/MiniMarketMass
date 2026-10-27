<div class="card">
    <form class="inline-form" method="post" action="productos.php?m=categorias&a=guardar">
        <input type="hidden" name="id" value="<?= e($editando['id'] ?? '') ?>">
        <div class="grow">
            <label for="nombre"><?= $editando ? 'Editar categoría' : 'Nueva categoría' ?></label>
            <input class="field" id="nombre" name="nombre" maxlength="60" required
                   value="<?= e($editando['nombre'] ?? '') ?>" placeholder="Ej. Abarrotes">
        </div>
        <button class="btn y">Guardar</button>
        <?php if ($editando): ?><a class="btn g" href="productos.php?m=categorias">Cancelar</a><?php endif; ?>
    </form>
</div>

<div class="tw">
    <div class="tools"><h3>Categorías <span class="tag b"><?= count($categorias) ?></span></h3></div>
    <div class="scroll">
        <table>
            <thead><tr><th>Nombre</th><th>Productos</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categorias as $c): ?>
                <tr>
                    <td><b><?= e($c['nombre']) ?></b></td>
                    <td><span class="tag"><?= (int)$c['total_productos'] ?></span></td>
                    <td>
                        <a class="act" title="Editar" href="productos.php?m=categorias&editar=<?= (int)$c['id'] ?>">✏️</a>
                        <form class="inline" method="post" action="productos.php?m=categorias&a=eliminar"
                              onsubmit="return confirm('¿Eliminar la categoría?')">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="act" title="Eliminar">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$categorias): ?><div class="empty">Aún no hay categorías</div><?php endif; ?>
    </div>
</div>