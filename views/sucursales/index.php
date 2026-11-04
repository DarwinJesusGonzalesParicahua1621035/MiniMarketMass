<?php
/**
 * Interfaz de Sucursales (Configuración · admin).
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
$fotos = [];
$porDist = [];
$cajasTot = 0;
foreach ($filas as $f) {
    $archivo = ROOT . '/public/img/sucursales/' . (int)$f['id'] . '.jpg';
    $fotos[$f['id']] = file_exists($archivo)
        ? Controller::base() . '/img/sucursales/' . (int)$f['id'] . '.jpg'
        : '';
    $porDist[$f['distrito']] = ($porDist[$f['distrito']] ?? 0) + 1;
    $cajasTot += (int)$f['cajas'];
}
ksort($porDist);

$distritos = ($c['campos']['distrito_id']['opciones'])();
?>
<section class="cfg-hero">
  <div>
    <p class="cfg-eyebrow">Configuración</p>
    <h2>Sucursales</h2>
    <p>Tiendas Mass: ubicación, series de comprobantes y cajas asignadas.</p>
  </div>
  <span><?= count($filas) ?> tienda<?= count($filas) === 1 ? '' : 's' ?></span>
</section>

<div class="cfg-kpis">
  <div class="cfg-kpi">
    <p>Sucursales</p>
    <h3 id="suc-kpi-n"><?= count($filas) ?></h3>
    <small>Registradas en el sistema</small>
  </div>
  <div class="cfg-kpi">
    <p>Distritos</p>
    <h3><?= count($porDist) ?></h3>
    <small>Cobertura geográfica</small>
  </div>
  <div class="cfg-kpi">
    <p>Cajas</p>
    <h3><?= (int)$cajasTot ?></h3>
    <small>Puntos de cobro activos</small>
  </div>
</div>

<div class="tw cfg-panel">
  <div class="tools suc-tools">
    <h3>Listado <span class="tag b" id="suc-total"><?= count($filas) ?></span></h3>
    <label class="prd-buscar">
      <span>🔍</span>
      <input id="suc-q" placeholder="Buscar sucursal, distrito o dirección…" autocomplete="off">
    </label>
    <button type="button" class="btn y" id="suc-nuevo">+ Nueva sucursal</button>
  </div>

  <div class="prd-filtros">
    <div class="prd-fila" id="suc-f-dist">
      <small>Distrito</small>
      <button type="button" class="prd-chip on" data-v="">Todos <em><?= count($filas) ?></em></button>
      <?php foreach ($porDist as $nombre => $n): ?>
        <button type="button" class="prd-chip" data-v="<?= e($nombre) ?>"><?= e($nombre) ?> <em><?= (int)$n ?></em></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="suc-grid" id="suc-grid">
    <?php foreach ($filas as $f): $foto = $fotos[$f['id']]; ?>
      <button type="button" class="suc-item"
              data-id="<?= (int)$f['id'] ?>"
              data-nombre="<?= e($f['nombre']) ?>"
              data-dir="<?= e($f['direccion']) ?>"
              data-dist="<?= e($f['distrito']) ?>"
              data-distid="<?= (int)$f['distrito_id'] ?>"
              data-sb="<?= e($f['serie_boleta']) ?>"
              data-sf="<?= e($f['serie_factura']) ?>"
              data-cajas="<?= (int)$f['cajas'] ?>"
              data-img="<?= e($foto) ?>"
              data-txt="<?= e(mb_strtolower($f['nombre'] . ' ' . $f['distrito'] . ' ' . $f['direccion'] . ' ' . $f['serie_boleta'] . ' ' . $f['serie_factura'])) ?>">
        <?php if ($foto): ?>
          <div class="suc-foto"><img src="<?= e($foto) ?>" alt="<?= e($f['nombre']) ?>"></div>
        <?php else: ?>
          <div class="suc-foto sin">🏪</div>
        <?php endif; ?>
        <div class="suc-info">
          <div>
            <h4><?= e($f['nombre']) ?></h4>
            <small><?= e($f['distrito']) ?> · <?= e($f['direccion']) ?></small>
          </div>
          <span class="suc-flecha">›</span>
        </div>
        <div class="suc-meta">
          <span class="tag b"><?= e($f['serie_boleta']) ?></span>
          <span class="tag"><?= e($f['serie_factura']) ?></span>
          <span class="tag y"><?= (int)$f['cajas'] ?> caja<?= (int)$f['cajas'] === 1 ? '' : 's' ?></span>
        </div>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="prd-vacio <?= $filas ? '' : 'show' ?>" id="suc-vacio">Sin sucursales que coincidan</div>
</div>

<!-- Detalle -->
<div class="dr-fondo" id="dr-fondo"></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="dr-nombre" aria-hidden="true">
  <div class="dr-vista" id="suc-vista">
    <div class="dr-foto" id="dr-foto">
      <img id="dr-img" src="" alt="">
      <span id="dr-emoji" style="display:none">🏪</span>
      <button type="button" class="dr-cerrar" id="dr-cerrar" aria-label="Cerrar">✕</button>
    </div>
    <div class="dr-cuerpo">
      <h2 id="dr-nombre"></h2>
      <small id="dr-sub"></small>
      <div class="dr-datos">
        <div><span>Dirección</span><b id="dr-dir"></b></div>
        <div><span>Distrito</span><b id="dr-dist"></b></div>
        <div><span>Serie boleta</span><b id="dr-sb"></b></div>
        <div><span>Serie factura</span><b id="dr-sf"></b></div>
        <div><span>Cajas</span><b id="dr-cajas"></b></div>
      </div>
    </div>
    <div class="dr-acciones">
      <button type="button" class="btn y flex" id="dr-editar">✏️ Editar</button>
      <form method="post" action="<?= Controller::url('sucursales/eliminar') ?>" data-confirm="¿Eliminar esta sucursal?">
        <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
        <input type="hidden" name="id" id="dr-id" value="">
        <button class="btn r flex">🗑️ Eliminar</button>
      </form>
    </div>
  </div>

  <!-- Formulario nuevo / editar -->
  <form class="dr-form" id="suc-form" method="post" action="<?= Controller::url('sucursales/guardar') ?>" style="display:none">
    <input type="hidden" name="csrf" value="<?= Controller::csrf() ?>">
    <input type="hidden" name="id" id="f-id" value="0">
    <div class="dr-cab">
      <button type="button" class="dr-cerrar" id="f-cerrar" aria-label="Cerrar">✕</button>
      <div class="pv-av" id="f-ico">🏬</div>
      <h2 id="f-titulo">Nueva sucursal</h2>
    </div>
    <div class="dr-cuerpo">
      <div class="pv-campo">
        <label for="f-nombre">Nombre</label>
        <input class="dr-input" id="f-nombre" name="nombre" maxlength="80" required>
      </div>
      <div class="pv-campo">
        <label for="f-dir">Dirección</label>
        <input class="dr-input" id="f-dir" name="direccion" maxlength="150" required>
      </div>
      <div class="pv-campo">
        <label for="f-dist">Distrito</label>
        <select class="dr-input" id="f-dist" name="distrito_id" required>
          <option value="">— Elige —</option>
          <?php foreach ($distritos as $k => $t): ?>
            <option value="<?= (int)$k ?>"><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="pv-campos2">
        <div class="pv-campo">
          <label for="f-sb">Serie boleta <em>(4)</em></label>
          <input class="dr-input" id="f-sb" name="serie_boleta" maxlength="4" minlength="4" placeholder="B001" required>
        </div>
        <div class="pv-campo">
          <label for="f-sf">Serie factura <em>(4)</em></label>
          <input class="dr-input" id="f-sf" name="serie_factura" maxlength="4" minlength="4" placeholder="F001" required>
        </div>
      </div>
    </div>
    <div class="dr-acciones">
      <button type="button" class="btn g flex" id="f-cancelar">Cancelar</button>
      <button class="btn y flex">Guardar</button>
    </div>
  </form>
</aside>

<script src="<?= Controller::base() ?>/js/sucursales.js"></script>
