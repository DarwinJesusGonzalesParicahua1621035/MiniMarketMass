<?php $id = (int)($producto['id'] ?? 0); ?>

<?php foreach ($errores as $m): ?><div class="alert err"><?= e($m) ?></div><?php endforeach; ?>

<div class="card narrow">
    <h3><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h3>
    <form method="post" action="productos.php?m=productos&a=guardar">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label for="categoria_id">Categoría</label>
        <select class="field" id="categoria_id" name="categoria_id" required>
            <option value="">Elige una</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)($producto['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="codigo_barras">Código de barras (13 caracteres)</label>
        <input class="field" id="codigo_barras" name="codigo_barras" minlength="13" maxlength="13" required
               value="<?= e($producto['codigo_barras'] ?? '') ?>">

        <label for="nombre">Nombre</label>
        <input class="field" id="nombre" name="nombre" maxlength="120" required value="<?= e($producto['nombre'] ?? '') ?>">

        <label for="unidad">Unidad (ej. unidad, kg, litro)</label>
        <input class="field" id="unidad" name="unidad" maxlength="20" required value="<?= e($producto['unidad'] ?? '') ?>">

        <label for="precio">Precio (S/)</label>
        <input class="field" id="precio" name="precio" type="number" step="0.01" min="0" required
               value="<?= e($producto['precio'] ?? '') ?>">

        <div class="row">
            <button class="btn y">Guardar</button>
            <a class="btn g" href="productos.php?m=productos">Cancelar</a>
        </div>
    </form>
</div>