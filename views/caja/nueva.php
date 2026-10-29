<form class="card formulario" method="post" action="<?= Controller::url('caja/nueva') ?>">
  <h3>Nueva caja</h3>
  <small style="color:var(--g4)">El nombre no se puede repetir dentro de la misma sucursal.</small>
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <div class="campos">
    <label>Sucursal
      <select name="sucursal_id" required>
        <?php foreach ($sucursales as $id => $n): ?>
          <option value="<?= (int)$id ?>" <?= (string)$id === ($old['sucursal_id'] ?? '') ? 'selected' : '' ?>><?= e($n) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Nombre
      <input type="text" name="nombre" maxlength="40" value="<?= e($old['nombre'] ?? '') ?>" placeholder="Ej: Caja 3" required>
    </label>
  </div>
  <div class="row">
    <a class="btn g" href="<?= Controller::url('caja/index') ?>">Cancelar</a>
    <button class="btn" type="submit">Guardar</button>
  </div>
</form>
