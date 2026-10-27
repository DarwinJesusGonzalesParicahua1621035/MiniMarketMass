<?php if ($flash): ?>
<div class="alert <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>"><?= ModuleSupport::escape($flash[1]) ?></div>
<?php endif; ?>

<div class="card narrow">
  <h2><?= $editing ? 'Editar caja' : 'Registrar caja' ?></h2>
  <form method="post" action="/caja/guardar">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <label for="sucursal_id">Sucursal</label>
    <select class="field" id="sucursal_id" name="sucursal_id" required>
      <option value="">Selecciona una sucursal</option>
      <?php foreach ($sucursales as $sucursal): ?>
      <option value="<?= (int)$sucursal['id'] ?>"
          <?= (int)($editing['sucursal_id'] ?? 0) === (int)$sucursal['id'] ? 'selected' : '' ?>>
        <?= ModuleSupport::escape($sucursal['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <label for="nombre">Nombre de la caja</label>
    <input class="field" id="nombre" name="nombre" maxlength="40" required
           value="<?= ModuleSupport::escape($editing['nombre'] ?? '') ?>">
    <div class="row">
      <button class="btn y" type="submit">Guardar</button>
      <?php if ($editing): ?><a class="btn g" href="/caja">Cancelar</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="tw">
  <div class="tools"><h3>Cajas registradas</h3></div>
  <div class="scroll">
    <table>
      <thead><tr><th>Sucursal</th><th>Caja</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($cajas as $caja): ?>
        <tr>
          <td><?= ModuleSupport::escape($caja['sucursal']) ?></td>
          <td><?= ModuleSupport::escape($caja['nombre']) ?></td>
          <td>
            <a class="act" href="/caja?editar=<?= (int)$caja['id'] ?>" aria-label="Editar">✏️</a>
            <form class="inline" method="post" action="/caja/eliminar"
                  onsubmit="return confirm('¿Eliminar esta caja?')">
              <input type="hidden" name="id" value="<?= (int)$caja['id'] ?>">
              <button class="act" type="submit" aria-label="Eliminar">🗑️</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$cajas): ?><tr><td class="empty" colspan="3">No hay cajas registradas.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
