<div class="card" style="margin-bottom:20px">
  <div class="tools" style="border:0;padding:0">
    <h3><?= e($p['nombre']) ?>
      <span class="tag <?= $p['activo'] ? 'ok' : 'bad' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span>
    </h3>
    <a class="btn g" href="<?= Controller::url('productos/index') ?>">Volver</a>
    <a class="btn y" href="<?= Controller::url('productos/editar', ['id' => $p['id']]) ?>">Editar</a>
  </div>
  <small style="color:var(--g4)"><?= e($p['categoria']) ?> · <?= e($p['codigo_barras']) ?> · por <?= e($p['unidad']) ?></small>
  <h2 style="font-size:30px;letter-spacing:-1px;margin-top:12px"><?= money($p['precio']) ?></h2>
  <small style="color:var(--g4)">Precio vigente</small>
</div>

<div class="tw">
  <div class="tools">
    <h3>Historial de precios <span class="tag b"><?= count($historial) ?></span></h3>
  </div>
  <div class="scroll">
    <table>
      <thead>
        <tr><th>Desde</th><th>Hasta</th><th style="text-align:right">Precio</th><th>Cambio</th><th>Estado</th></tr>
      </thead>
      <tbody>
      <?php foreach ($historial as $i => $h):
          $previo = $historial[$i + 1]['precio'] ?? null;
          $dif = $previo === null ? null : round((float)$h['precio'] - (float)$previo, 2); ?>
        <tr>
          <td><?= e($h['fecha_inicio']) ?></td>
          <td><?= $h['fecha_fin'] ? e($h['fecha_fin']) : '—' ?></td>
          <td style="text-align:right"><b><?= money($h['precio']) ?></b></td>
          <td>
            <?php if ($dif === null): ?>—
            <?php elseif ($dif > 0): ?><span class="tag bad">▲ +<?= number_format($dif, 2) ?></span>
            <?php elseif ($dif < 0): ?><span class="tag ok">▼ <?= number_format($dif, 2) ?></span>
            <?php else: ?><span class="tag">igual</span><?php endif; ?>
          </td>
          <td><span class="tag <?= $h['fecha_fin'] ? '' : 'ok' ?>"><?= $h['fecha_fin'] ? 'Anterior' : 'Vigente' ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$historial): ?><div class="empty">Sin historial de precios</div><?php endif; ?>
  </div>
</div>