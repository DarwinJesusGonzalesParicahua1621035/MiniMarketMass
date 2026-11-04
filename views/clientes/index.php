<?php
// views/clientes/index.php
$ini = function (string $n): string {
    $s = '';
    foreach (array_slice(preg_split('/\s+/', trim($n)) ?: [], 0, 2) as $w) {
        $s .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    return $s;
};
$nDni = count(array_filter($filas, fn($f) => $f['tipo_documento'] === 'DNI'));
$nRuc = count($filas) - $nDni;
$nCon = count(array_filter($filas, fn($f) => (int)$f['ventas'] > 0));
$nSin = count($filas) - $nCon;
?>
<div class="cli-panel">
  <div class="cli-head">
    <div class="cli-row">
      <h3>Clientes <span class="tag b" id="cliCount"><?= count($filas) ?></span></h3>
      <input class="cli-search" id="cliBuscar" type="search" placeholder="Buscar por nombre o n.º de documento">
    </div>
    <div class="cli-filtros">
      <div class="cli-row">
        <span class="cli-lbl">Tipo doc.</span>
        <button type="button" class="cli-chip on" data-grupo="tipo" data-v="">Todos</button>
        <button type="button" class="cli-chip" data-grupo="tipo" data-v="DNI">DNI <i><?= $nDni ?></i></button>
        <button type="button" class="cli-chip" data-grupo="tipo" data-v="RUC">RUC <i><?= $nRuc ?></i></button>
      </div>
      <div class="cli-row">
        <span class="cli-lbl">Compras</span>
        <button type="button" class="cli-chip on" data-grupo="compras" data-v="">Todos</button>
        <button type="button" class="cli-chip" data-grupo="compras" data-v="con">Con compras <i><?= $nCon ?></i></button>
        <button type="button" class="cli-chip" data-grupo="compras" data-v="sin">Sin compras <i><?= $nSin ?></i></button>
      </div>
    </div>
  </div>

  <div class="cli-grid" id="cliGrid">
    <?php foreach ($filas as $f): $v = (int)$f['ventas']; ?>
      <button type="button" class="cli-item"
              data-id="<?= (int)$f['id'] ?>"
              data-tipo="<?= e($f['tipo_documento']) ?>"
              data-doc="<?= e($f['numero_documento']) ?>"
              data-nombre="<?= e($f['nombre']) ?>"
              data-ventas="<?= $v ?>">
        <span class="cli-top">
          <span class="cli-av"><?= e($ini($f['nombre'])) ?></span>
          <span>
            <span class="cli-nm"><?= e($f['nombre']) ?></span>
            <span class="cli-doc"><?= e($f['tipo_documento']) ?> <?= e($f['numero_documento']) ?></span>
          </span>
          <span class="tag <?= $v ? 'ok' : '' ?>"><?= $v ? 'Con compras' : 'Sin compras' ?></span>
        </span>
        <span class="cli-foot">
          <b><?= $v ?> <?= $v === 1 ? 'compra' : 'compras' ?></b>
          <span class="cli-pill"><?= e($f['tipo_documento']) ?></span>
        </span>
      </button>
    <?php endforeach; ?>
    <div class="empty" id="cliVacio" <?= $filas ? 'hidden' : '' ?>>No hay clientes con esos filtros.</div>
  </div>
</div>

<!-- Drawer: detalle y edición en el mismo panel -->
<div class="cli-scrim" id="cliScrim"></div>
<aside class="cli-drawer" id="cliDrawer" role="dialog" aria-modal="true" aria-labelledby="cliTitulo">
  <div class="cli-dh">
    <h2 id="cliTitulo">Detalle del cliente</h2>
    <button type="button" class="cli-x" id="cliCerrar" aria-label="Cerrar">✕</button>
  </div>

  <!-- Vista de detalle -->
  <div class="cli-pane" id="cliVer">
    <div class="cli-db">
      <div class="cli-big">
        <span class="cli-av" id="vAv"></span>
        <div><div class="cli-nm" id="vNombre"></div><div class="cli-doc" id="vId"></div></div>
      </div>
      <div class="cli-dl">
        <div><span>Tipo de documento</span><b id="vTipo"></b></div>
        <div><span>N.º de documento</span><b id="vDoc"></b></div>
        <div><span>Compras</span><b id="vVentas"></b></div>
      </div>
      <p class="cli-nota" id="vNota" hidden>Este cliente tiene compras registradas, por eso no se puede eliminar.</p>
    </div>
    <div class="cli-df">
      <form method="post" action="<?= Controller::url('clientes/eliminar') ?>" data-confirm="¿Eliminar este cliente?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="delId">
        <button class="btn cli-btn-del" id="delBtn">Eliminar</button>
      </form>
      <button type="button" class="btn cli-btn-k" id="btnEditar">Editar</button>
    </div>
  </div>

  <!-- Formulario de edición -->
  <form class="cli-pane" id="cliEdit" method="post" action="<?= Controller::url('clientes/guardar') ?>" hidden>
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="eId">
    <div class="cli-db">
      <label class="cli-campo">Tipo de documento
        <select name="tipo_documento" id="eTipo" required>
          <option value="DNI">DNI</option>
          <option value="RUC">RUC</option>
        </select>
      </label>
      <label class="cli-campo">N.º de documento
        <input type="text" name="numero_documento" id="eDoc" inputmode="numeric" required>
      </label>
      <label class="cli-campo">Nombre o razón social
        <input type="text" name="nombre" id="eNombre" maxlength="120" required>
      </label>
    </div>
    <div class="cli-df">
      <button type="button" class="btn g" id="btnCancelar">Cancelar</button>
      <button class="btn y">Guardar cambios</button>
    </div>
  </form>
</aside>

<script src="<?= Controller::base() ?>/js/clientes.js"></script>