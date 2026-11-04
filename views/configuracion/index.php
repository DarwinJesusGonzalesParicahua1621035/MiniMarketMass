<?php
/**
 * Hub de Configuración del administrador.
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
?>
<section class="cfg-hero">
  <div>
    <p class="cfg-eyebrow">Administración</p>
    <h2>Configuración</h2>
    <p>Gestiona las tiendas y los incidentes operativos desde un solo lugar.</p>
  </div>
  <span><?= date('d/m/Y · H:i') ?></span>
</section>

<div class="cfg-kpis">
  <div class="cfg-kpi">
    <p>Sucursales</p>
    <h3><?= (int)$nSuc ?></h3>
    <small><?= (int)$nDist ?> distrito<?= $nDist === 1 ? '' : 's' ?> · <?= (int)$nCajas ?> caja<?= $nCajas === 1 ? '' : 's' ?></small>
  </div>
  <div class="cfg-kpi">
    <p>Incidentes abiertos</p>
    <h3><?= (int)$nAbiertos ?></h3>
    <small><?= (int)$nTarde ?> con más de 24 h · <?= (int)$nInc ?> en total</small>
  </div>
</div>

<div class="cfg-mods">
  <a class="cfg-mod" href="<?= Controller::url('sucursales/index') ?>">
    <div class="cfg-mod-ico">🏬</div>
    <div class="cfg-mod-body">
      <h3>Sucursales</h3>
      <p>Direcciones, distritos, series de boleta/factura y cajas por tienda.</p>
      <span class="cfg-mod-cta">Abrir sucursales →</span>
    </div>
  </a>
  <a class="cfg-mod" href="<?= Controller::url('incidentes/index') ?>">
    <div class="cfg-mod-ico">⚠️</div>
    <div class="cfg-mod-body">
      <h3>Incidentes</h3>
      <p>Reportes de seguridad, equipos y stock; seguimiento y resolución.</p>
      <span class="cfg-mod-cta">Abrir incidentes →</span>
    </div>
  </a>
</div>
