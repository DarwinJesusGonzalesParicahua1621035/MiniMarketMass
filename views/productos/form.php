<?php $id = (int)($producto['id'] ?? 0); ?>
<h2><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h2>

<?php foreach ($errores as $m): ?><p class="err"><?= e($m) ?></p><?php endforeach; ?>

<form method="post" action="productos.php?m=productos&a=guardar">
    <input type="hidden" name="id" value="<?= $id ?>">

    <label for="categoria_id">Categoría</label>
    <select id="categoria_id" name="categoria_id" required>
        <option value="">Elige una</option>
        <?php foreach ($categorias as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (int)($producto['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="codigo_barras">Código de barras (13 caracteres)</label>
    <input id="codigo_barras" name="codigo_barras" minlength="13" maxlength="13" required value="<?= e($producto['codigo_barras'] ?? '') ?>">

    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" maxlength="120" required value="<?= e($producto['nombre'] ?? '') ?>">

    <label for="unidad">Unidad (ej. UNIDAD, KG, LITRO)</label>
    <input id="unidad" name="unidad" maxlength="20" required value="<?= e($producto['unidad'] ?? '') ?>">

    <label for="precio">Precio (S/)</label>
    <input id="precio" name="precio" type="number" step="0.01" min="0" required value="<?= e($producto['precio'] ?? '') ?>">

    <p><button>Guardar</button> <a href="productos.php?m=productos">Cancelar</a></p>
</form>