<?php
/**
 * @var array $productos
 */
?>
<div class="tw">
  <div class="tools">
    <h3>Productos</h3>
    <button class="btn y">+ Nuevo</button>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr>
            <th>ID</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Activo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($productos as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= $p['codigo_barras'] ?></td>
          <td><?= $p['nombre'] ?></td>
          <td><?= $p['categoria'] ?></td>
          <td>S/ <?= number_format($p['precio'], 2) ?></td>
          <td>
            <?php if($p['activo']): ?>
                <span class="tag ok">Sí</span>
            <?php else: ?>
                <span class="tag bad">No</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>