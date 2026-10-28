<form class="card formulario" method="post" action="<?= Controller::url($c['carpeta'] . '/guardar') ?>">
  <h3><?= e($titulo) ?></h3>
  <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <div class="campos">
  <?php foreach ($c['campos'] as $n => $f):
      $val = $reg[$n] ?? '';
      $req = !empty($f['req']) && !($f['tipo'] === 'password' && $id);
      $attrs = ($req ? 'required ' : '') . (isset($f['max']) ? 'maxlength="' . (int)$f['max'] . '" ' : '') . ($f['attr'] ?? ''); ?>
    <label><?= e($f['label']) ?>
      <?php if ($f['tipo'] === 'select'): ?>
        <select name="<?= $n ?>" <?= $attrs ?>>
          <?php foreach ($f['opciones'] as $k => $t): ?>
            <option value="<?= e($k) ?>" <?= (string)$k === (string)$val ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      <?php elseif ($f['tipo'] === 'password'): ?>
        <input type="password" name="<?= $n ?>" placeholder="<?= $id ? 'Dejar vacío para no cambiar' : '' ?>" <?= $attrs ?>>
      <?php else:
          if ($f['tipo'] === 'datetime-local' && $val) $val = str_replace(' ', 'T', substr($val, 0, 16)); ?>
        <input type="<?= $f['tipo'] ?>" name="<?= $n ?>" value="<?= e($val) ?>" <?= $f['tipo'] === 'number' ? 'step="' . ($f['step'] ?? 'any') . '"' : '' ?> <?= $attrs ?>>
      <?php endif; ?>
    </label>
  <?php endforeach; ?>
  </div>
  <div class="row">
    <a class="btn g" href="<?= Controller::url($c['carpeta'] . '/index') ?>">Cancelar</a>
    <button class="btn">Guardar</button>
  </div>
</form>