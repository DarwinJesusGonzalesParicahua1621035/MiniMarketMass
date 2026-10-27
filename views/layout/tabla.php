<?php $acc = $c['acciones'] ?? []; ?>
<div class="tw">
  <div class="tools">
    <h3><?= e($c['titulo']) ?> <span class="tag b"><?= count($filas) ?></span></h3>
    <input class="search" placeholder="Buscar…" data-filtro="tabla">
    <?php foreach ($c['extra'] ?? [] as $x): ?><a class="btn g" href="<?= Controller::url($x[1]) ?>"><?= e($x[0]) ?></a><?php endforeach; ?>
    <?php if (!empty($c['nuevo'])): ?><a class="btn y" href="<?= Controller::url($c['nuevo']) ?>">+ Nuevo</a><?php endif; ?>
  </div>
  <div class="scroll">
    <table id="tabla">
      <thead><tr>
        <?php foreach ($c['columnas'] as $col): ?><th><?= e($col[0]) ?></th><?php endforeach; ?>
        <?php if ($acc): ?><th></th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($filas as $f): ?>
        <tr>
          <?php foreach ($c['columnas'] as $k => $col): ?><td><?= celda($f[$k] ?? '', $col[1] ?? 'texto') ?></td><?php endforeach; ?>
          <?php if ($acc): ?>
          <td>
            <?php if (in_array('ver', $acc)): ?><a class="act" title="Ver" href="<?= Controller::url($c['carpeta'] . '/ver', ['id' => $f['id']]) ?>">👁️</a><?php endif; ?>
            <?php if (in_array('editar', $acc)): ?><a class="act" title="Editar" href="<?= Controller::url($c['carpeta'] . '/editar', ['id' => $f['id']]) ?>">✏️</a><?php endif; ?>
            <?php if (in_array('eliminar', $acc)): ?>
              <form class="inline" method="post" action="<?= Controller::url($c['carpeta'] . '/eliminar') ?>" data-confirm="¿Eliminar este registro?">
                <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <button class="act" title="Eliminar">🗑️</button>
              </form>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$filas): ?><div class="empty">Sin registros</div><?php endif; ?>
  </div>
</div>