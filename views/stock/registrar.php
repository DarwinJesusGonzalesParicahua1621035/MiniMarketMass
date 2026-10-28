<?php
$old = $old ?? [];
$tipos = [
    'ENTRADA' => 'ENTRADA (suma): llegada de mercadería',
    'SALIDA'  => 'SALIDA (resta): retiro de stock',
    'AJUSTE'  => 'AJUSTE (suma): corrección de inventario',
    'MERMA'   => 'MERMA (resta): producto dañado o vencido',
];
?>
<form class="card formulario" method="post" action="<?= Controller::url('stock/guardar') ?>">
  <h3>Registrar movimiento</h3>
  <small style="color:var(--g4)">Actualiza el stock y deja constancia en el historial.</small>
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <div class="campos">
    <label>Producto y sucursal
      <select name="stock" required>
        <?php foreach ($combos as $valor => $texto): ?>
          <option value="<?= e($valor) ?>" <?= (string)$valor === (string)($old['stock'] ?? '') ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Tipo
      <select name="tipo" required>
        <?php foreach ($tipos as $k => $t): ?>
          <option value="<?= $k ?>" <?= $k === ($old['tipo'] ?? 'ENTRADA') ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Cantidad
      <input type="number" name="cantidad" step="0.001" min="0.001" value="<?= e($old['cantidad'] ?? '') ?>" required>
    </label>
    <label>Motivo
      <input type="text" name="motivo" maxlength="100" value="<?= e($old['motivo'] ?? '') ?>" placeholder="Ej: Reposición, Inventario, Envase dañado">
    </label>
  </div>
  <div class="row">
    <a class="btn g" href="<?= Controller::url('stock/index') ?>">Cancelar</a>
    <button class="btn" type="submit">Registrar</button>
  </div>
</form>