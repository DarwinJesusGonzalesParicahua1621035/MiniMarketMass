<?php if ($flash): ?>
<div class="alert <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>"><?= ModuleSupport::escape($flash[1]) ?></div>
<?php endif; ?>

<div class="card narrow">
  <h2>Registrar incidente</h2>
  <form method="post" action="/incidentes/crear">
    <label for="sucursal_id">Sucursal</label>
    <select class="field" id="sucursal_id" name="sucursal_id" required>
      <option value="">Selecciona una sucursal</option>
      <?php foreach ($sucursales as $sucursal): ?>
      <option value="<?= (int)$sucursal['id'] ?>"><?= ModuleSupport::escape($sucursal['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <label for="tipo">Tipo</label>
    <input class="field" id="tipo" name="tipo" maxlength="20" required>
    <label for="descripcion">Descripción</label>
    <textarea class="field" id="descripcion" name="descripcion" maxlength="250" required></textarea>
    <div class="row"><button class="btn y" type="submit">Registrar como abierto</button></div>
  </form>
</div>

<div class="tw">
  <form class="tools" method="get" action="/incidentes">
    <h3>Incidentes registrados</h3>
    <select class="sel" name="sucursal">
      <option value="0">Todas las sucursales</option>
      <?php foreach ($sucursales as $sucursal): ?>
      <option value="<?= (int)$sucursal['id'] ?>" <?= $sucursalId === (int)$sucursal['id'] ? 'selected' : '' ?>>
        <?= ModuleSupport::escape($sucursal['nombre']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <select class="sel" name="estado">
      <option value="">Todos los estados</option>
      <option value="ABIERTO" <?= $estado === 'ABIERTO' ? 'selected' : '' ?>>Abiertos</option>
      <option value="RESUELTO" <?= $estado === 'RESUELTO' ? 'selected' : '' ?>>Resueltos</option>
    </select>
    <button class="btn g" type="submit">Filtrar</button>
  </form>
  <div class="scroll">
    <table>
      <thead><tr><th>Sucursal</th><th>Tipo</th><th>Descripción</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead>
      <tbody>
      <?php foreach ($incidentes as $incidente): ?>
        <tr>
          <td><?= ModuleSupport::escape($incidente['sucursal']) ?></td>
          <td><?= ModuleSupport::escape($incidente['tipo']) ?></td>
          <td><?= ModuleSupport::escape($incidente['descripcion']) ?></td>
          <td><?= ModuleSupport::escape($incidente['fecha']) ?></td>
          <td><span class="tag <?= $incidente['estado'] === 'ABIERTO' ? 'y' : 'ok' ?>"><?= ModuleSupport::escape($incidente['estado']) ?></span></td>
          <td>
            <form method="post" action="/incidentes/estado">
              <input type="hidden" name="id" value="<?= (int)$incidente['id'] ?>">
              <input type="hidden" name="estado" value="<?= $incidente['estado'] === 'ABIERTO' ? 'RESUELTO' : 'ABIERTO' ?>">
              <button class="btn g" type="submit"><?= $incidente['estado'] === 'ABIERTO' ? 'Marcar resuelto' : 'Reabrir' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$incidentes): ?><tr><td class="empty" colspan="6">No hay incidentes para esos filtros.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
