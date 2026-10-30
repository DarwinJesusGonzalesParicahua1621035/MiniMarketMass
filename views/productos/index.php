<div class="tw">
  <form class="tools" method="get" action="<?= Controller::base() ?>/index.php">
    <input type="hidden" name="r" value="productos/index">
    <h3>Productos <span class="tag b"><?= count($filas) ?></span></h3>
    <select class="search" name="categoria" onchange="this.form.submit()">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $id => $n): ?>
        <option value="<?= (int)$id ?>" <?= (int)$id === (int)$categoriaSel ? 'selected' : '' ?>><?= e($n) ?></option>
      <?php endforeach; ?>
    </select>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <a class="btn g" href="<?= Controller::url('categorias/index') ?>">Categorías</a>
    <a class="btn y" href="<?= Controller::url('productos/nuevo') ?>">+ Nuevo producto</a>
  </form>
  <div class="scroll">
    <table id="tabla">
      <thead>
        <tr>
          <th>Código</th><th>Producto</th><th>Categoría</th><th>Unidad</th>
          <th style="text-align:right">Precio</th><th>Estado</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($filas as $f): ?>
        <tr>
          <td><?= e($f['codigo_barras']) ?></td>
          <td><?= e($f['nombre']) ?></td>
          <td><span class="tag b"><?= e($f['categoria']) ?></span></td>
          <td><?= e($f['unidad']) ?></td>
          <td style="text-align:right"><b><?= money($f['precio']) ?></b></td>
          <td><span class="tag <?= $f['activo'] ? 'ok' : 'bad' ?>"><?= $f['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <a class="act" title="Ver precios" href="<?= Controller::url('productos/ver', ['id' => $f['id']]) ?>">👁️</a>
            <a class="act" title="Editar" href="<?= Controller::url('productos/editar', ['id' => $f['id']]) ?>">✏️</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin productos</div><?php endif; ?>
  </div>
</div>