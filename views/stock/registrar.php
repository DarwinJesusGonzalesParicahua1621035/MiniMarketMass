<form method="post" action="<?= Controller::url('stock/guardar') ?>">
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">

  <label>Producto y sucursal
    <select name="stock" required>
      <?php foreach ($combos as $valor => $texto): ?>
        <option value="<?= e($valor) ?>"><?= e($texto) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Tipo
    <select name="tipo" required>
      <option value="ENTRADA">ENTRADA (suma)</option>
      <option value="SALIDA">SALIDA (resta)</option>
      <option value="AJUSTE">AJUSTE (suma)</option>
      <option value="MERMA">MERMA (resta)</option>
    </select>
  </label>

  <label>Cantidad
    <input type="number" name="cantidad" step="0.001" min="0.001" required>
  </label>

  <label>Motivo
    <input type="text" name="motivo" maxlength="100">
  </label>

  <button type="submit">Registrar</button>
  <a href="<?= Controller::url('stock/index') ?>">Cancelar</a>
</form>