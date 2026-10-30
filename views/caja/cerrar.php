<?php $sel = (string)($old['historia_id'] ?? ($turnoSel ?: '')); ?>
<form class="card formulario" method="post" action="<?= Controller::url('caja/cerrar') ?>">
  <h3>Cerrar caja</h3>
  <small style="color:var(--g4)">Registra el dinero contado al final del turno.</small>
  <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
  <?php if (!$turnos): ?>
    <div class="empty">No hay turnos abiertos</div>
  <?php else: ?>
  <div class="campos">
    <label>Turno abierto
      <select name="historia_id" required>
        <?php foreach ($turnos as $id => $t): ?>
          <option value="<?= (int)$id ?>" <?= (string)$id === $sel ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Monto final (S/)
      <input type="number" name="monto_final" step="0.01" min="0" value="<?= e($old['monto_final'] ?? '') ?>" placeholder="Ej: 420.00" required>
    </label>
  </div>
  <?php endif; ?>
  <div class="row">
    <a class="btn g" href="<?= Controller::url('caja/index') ?>">Cancelar</a>
    <?php if ($turnos): ?><button class="btn" type="submit">Cerrar</button><?php endif; ?>
  </div>
</form>
