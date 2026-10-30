<?php $sel = (string)($old['caja_id'] ?? ($cajaSel ?: '')); ?>
<form class="card formulario" method="post" action="<?= Controller::url('caja/abrir') ?>">
  <h3>Abrir caja</h3>
  <small style="color:var(--g4)">El turno queda a tu nombre y empieza ahora.</small>
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <?php if (!$cajas): ?>
    <div class="empty">Todas las cajas ya tienen un turno abierto</div>
  <?php else: ?>
  <div class="campos">
    <label>Caja
      <select name="caja_id" required>
        <?php foreach ($cajas as $id => $t): ?>
          <option value="<?= (int)$id ?>" <?= (string)$id === $sel ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Monto inicial (S/)
      <input type="number" name="monto_inicial" step="0.01" min="0" value="<?= e($old['monto_inicial'] ?? '') ?>" placeholder="Ej: 100.00" required>
    </label>
  </div>
  <?php endif; ?>
  <div class="row">
    <a class="btn g" href="<?= Controller::url('caja/index') ?>">Cancelar</a>
    <?php if ($cajas): ?><button class="btn" type="submit">Abrir</button><?php endif; ?>
  </div>
</form>
