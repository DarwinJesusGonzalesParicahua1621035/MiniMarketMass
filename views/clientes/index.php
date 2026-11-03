<?php
$g   = $general;
$est = ['VIP' => ['y', 'VIP'], 'ACTIVO' => ['ok', 'Activo'], 'INACTIVO' => ['bad', 'Inactivo']];
$ini = fn($n) => mb_strtoupper(mb_substr(trim($n), 0, 1) . (preg_match('/\s(\S)/u', trim($n), $m) ? $m[1] : ''));
$fmt = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$maxMes = max(array_merge([1], array_column($g['meses'], 'v')));
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/clientes.css">

<div class="cl" id="cl"
     data-csrf="<?= Controller::csrf() ?>"
     data-detalle="<?= Controller::url('clientes/detalle') ?>"
     data-guardar="<?= Controller::url('clientes/guardar') ?>"
     data-eliminar="<?= Controller::url('clientes/eliminar') ?>"
     data-ver="<?= Controller::url('ventas/ver') ?>">

  <!-- ===================== LISTA ===================== -->
  <section class="cl-main">
    <div class="cl-bar">
      <div class="cl-search">
        <span>🔍</span>
        <input id="cl-q" type="search" placeholder="Buscar por nombre o documento…" autocomplete="off">
      </div>
      <div class="cl-chips" id="cl-chips">
        <button class="on" data-f="">Todos <b><?= $g['clientes'] ?></b></button>
        <button data-f="ACTIVO">Activos <b><?= $g['estados']['ACTIVO'] ?></b></button>
        <button data-f="INACTIVO">Inactivos <b><?= $g['estados']['INACTIVO'] ?></b></button>
      </div>
      <button class="btn cl-new" id="cl-nuevo">＋ Nuevo cliente</button>
    </div>

    <div class="cl-card">
      <div class="scroll">
        <table id="cl-tabla">
          <thead><tr>
            <th>Cliente</th><th>Documento</th><th>Historial de compras</th><th>Estado</th><th></th>
          </tr></thead>
          <tbody>
          <?php foreach ($filas as $f): [$cls, $txt] = $est[$f['estado']]; ?>
            <tr tabindex="0" data-id="<?= (int)$f['id'] ?>" data-estado="<?= $f['estado'] ?>"
                data-tipo="<?= e($f['tipo_documento']) ?>" data-doc="<?= e($f['numero_documento']) ?>" data-nombre="<?= e($f['nombre']) ?>">
              <td><div class="cl-who"><i class="av c<?= $f['id'] % 6 ?>"><?= e($ini($f['nombre'])) ?></i><span><?= e($f['nombre']) ?></span></div></td>
              <td><span class="cl-doc"><?= e($f['tipo_documento']) ?></span> <?= e($f['numero_documento']) ?></td>
              <td><b><?= (int)$f['compras'] ?> <?= (int)$f['compras'] === 1 ? 'compra' : 'compras' ?></b>
                  <small><?= money($f['total']) ?> · últ. <?= $fmt($f['ultima']) ?></small></td>
              <td><span class="tag <?= $cls ?>"><?= $txt ?></span></td>
              <td class="cl-acts">
                <button type="button" class="act" title="Editar" data-editar>✏️</button>
                <form class="inline" method="post" action="<?= Controller::url('clientes/eliminar') ?>" data-confirm="¿Eliminar a <?= e($f['nombre']) ?>?">
                  <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
                  <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                  <button class="act" title="Eliminar">🗑️</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <div class="empty" id="cl-vacio" <?= $filas ? 'hidden' : '' ?>>No hay clientes que mostrar</div>
      </div>
    </div>
  </section>

  <!-- ===================== PANEL LATERAL ===================== -->
  <div class="cl-panel" id="cl-panel">

    <!-- Informe general -->
    <div class="cl-gen">
      <div class="cl-ph"><h3>Informe general</h3><small>Todos los clientes registrados</small></div>

      <div class="cl-kpis">
        <div><p>Clientes</p><h4><?= number_format($g['clientes']) ?></h4></div>
        <div><p>Compras</p><h4><?= number_format($g['compras']) ?></h4></div>
        <div><p>Ingresos</p><h4><?= money($g['ingresos']) ?></h4></div>
        <div><p>Ticket prom.</p><h4><?= money($g['ticket']) ?></h4></div>
      </div>

      <h5>Estado de la cartera</h5>
      <?php $tot = max(1, $g['clientes']); ?>
      <div class="cl-stack">
        <i class="ok" style="width:<?= $g['estados']['ACTIVO'] / $tot * 100 ?>%"></i>
        <i class="bad" style="width:<?= $g['estados']['INACTIVO'] / $tot * 100 ?>%"></i>
      </div>
      <ul class="cl-leg">
        <li><i class="ok"></i>Activos<b><?= $g['estados']['ACTIVO'] ?></b></li>
        <li><i class="bad"></i>Inactivos<b><?= $g['estados']['INACTIVO'] ?></b></li>
      </ul>

      <h5>Compras de clientes · últimos 6 meses</h5>
      <div class="cl-bars">
        <?php foreach ($g['meses'] as $m): ?>
          <div><b><?= $m['v'] > 0 ? 'S/ ' . number_format($m['v'], 0) : '' ?></b>
            <span style="height:<?= max(3, $m['v'] / $maxMes * 100) ?>%"></span><em><?= $m['l'] ?></em></div>
        <?php endforeach; ?>
      </div>

      <h5>Mejores clientes</h5>
      <ol class="cl-top">
        <?php foreach ($g['top'] as $t): ?>
          <li data-abrir="<?= (int)$t['id'] ?>" tabindex="0">
            <i class="av c<?= $t['id'] % 6 ?>"><?= e($ini($t['nombre'])) ?></i>
            <span><?= e($t['nombre']) ?><small><?= (int)$t['compras'] ?> compras</small></span>
            <b><?= money($t['total']) ?></b>
          </li>
        <?php endforeach; ?>
        <?php if (!$g['top']): ?><li class="none">Aún no hay compras registradas</li><?php endif; ?>
      </ol>
      <p class="cl-hint">Haz clic en un cliente para ver su informe personal.</p>
    </div>

    <!-- Informe personal (se superpone al general) -->
    <div class="cl-det" id="cl-det" aria-hidden="true">
      <div class="cl-dtop">
        <button class="cl-back" id="cl-back">← Informe general</button>
        <button class="cl-x" id="cl-cerrar" aria-label="Cerrar">✕</button>
      </div>
      <div id="cl-dbody"></div>
    </div>
  </div>
</div>

<!-- ===================== DRAWER (crear / editar) ===================== -->
<div class="cl-back-drop" id="cl-drop" hidden></div>
<div class="cl-drawer" id="cl-drawer" aria-hidden="true" role="dialog" aria-labelledby="cl-dtitulo">
  <form method="post" action="<?= Controller::url('clientes/guardar') ?>" id="cl-form">
    <div class="cl-dhead">
      <div><h3 id="cl-dtitulo">Editar cliente</h3><small id="cl-dsub"></small></div>
      <button type="button" class="cl-x" id="cl-dclose" aria-label="Cerrar">✕</button>
    </div>
    <div class="cl-dbody campos">
      <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
      <input type="hidden" name="id" id="f-id" value="0">
      <label>Tipo de documento
        <select name="tipo_documento" id="f-tipo" required><option>DNI</option><option>RUC</option></select>
      </label>
      <label>N.º de documento <span id="f-ayuda">(8 dígitos)</span>
        <input type="text" name="numero_documento" id="f-doc" inputmode="numeric" maxlength="11" required>
      </label>
      <label>Nombre o razón social
        <input type="text" name="nombre" id="f-nombre" maxlength="120" required>
      </label>
    </div>
    <div class="cl-dfoot">
      <button type="button" class="btn g" id="cl-dcancel">Cancelar</button>
      <button class="btn">Guardar cambios</button>
    </div>
  </form>
</div>

<script src="<?= Controller::base() ?>/js/clientes.js"></script>