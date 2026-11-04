<?php
$activo = $activo ?? '';
$iniciales = mb_strtoupper(implode('', array_map(
    fn($p) => mb_substr($p, 0, 1),
    array_slice(preg_split('/\s+/', trim($user['nombre'])), 0, 2)
)));

/* Etapa del turno: 0 = sin asistencia · 1 = asistencia lista, falta abrir caja · 2 = caja abierta */
$uid   = (int)$user['id'];
$turno = $turno ?? (new PosRepository())->turno($uid);
$fase  = $turno ? 2 : ((new AsistenciaRepository())->deHoy($uid) ? 1 : 0);

$urlCaja = Controller::url('cajero/caja');
$items = [
    'Operación' => [
        ['venta',    'cajero/venta',    '🛒', 'Nueva venta'],
        ['ventas',   'cajero/ventas',   '🧾', 'Mis ventas'],
        ['caja',     'cajero/caja',     '💵', 'Mi caja'],
    ],
    'Consulta' => [
        ['clientes', 'cajero/clientes', '👥', 'Clientes'],
    ],
];
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/cajero-turno.css">
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <div class="rol">CAJERO</div>
  <nav>
    <?php if ($fase === 0): ?>

      <h6>Inicio de turno</h6>
      <a href="<?= $urlCaja ?>" class="on"><span>🕘</span>Registrar asistencia</a>

    <?php elseif ($fase === 1): ?>

      <h6>Inicio de turno</h6>
      <div class="done"><span>✅</span>Asistencia registrada</div>
      <a href="<?= $urlCaja ?>" class="on"><span>💵</span>Abrir caja</a>

    <?php else: ?>

      <?php foreach ($items as $grupo => $lista): ?>
        <h6><?= e($grupo) ?></h6>
        <?php foreach ($lista as [$clave, $ruta, $ico, $texto]): ?>
          <a href="<?= Controller::url($ruta) ?>" class="<?= $activo === $clave ? 'on' : '' ?>"><span><?= $ico ?></span><?= e($texto) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <h6>Fin de turno</h6>
      <a href="<?= $urlCaja ?>#formCerrar"><span>🔒</span>Cerrar caja</a>
      <div class="sep"></div>
      <button type="button" id="incAbrir"><span>⚠️</span>Incidencias</button>

    <?php endif; ?>
  </nav>
  <div class="me"><b><?= e($iniciales) ?></b>
    <div><?= e($user['nombre']) ?><small>Cajero<?= $sucursal ? ' · ' . e($sucursal) : '' ?></small>
      <a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></div></div>
</aside>

<?php if ($fase === 2): ?>
<!-- ============ DRAWER DE INCIDENCIAS (solo con la caja abierta) ============ -->
<div class="inc-fondo" id="incFondo"></div>
<aside class="inc-drawer" id="incDrawer" role="dialog" aria-modal="true" aria-labelledby="incTitulo" aria-hidden="true">
  <form method="post" action="<?= Controller::url('cajero/incidencia') ?>" style="display:flex;flex-direction:column;flex:1;min-height:0">
    <input type="hidden" name="csrf" value="<?= e(Controller::csrf()) ?>">
    <input type="hidden" name="volver" value="<?= e((string)($_GET['r'] ?? 'cajero/caja')) ?>">
    <div class="inc-cab">
      <h2 id="incTitulo">Reportar incidencia</h2>
      <button type="button" class="inc-x" id="incCerrar" aria-label="Cerrar">✕</button>
    </div>
    <div class="inc-cuerpo">
      <div class="inc-f">Tipo
        <div class="inc-tipos">
          <label><input type="radio" name="tipo" value="Equipos" checked><span>🖨️ Equipos</span></label>
          <label><input type="radio" name="tipo" value="Seguridad"><span>🛡️ Seguridad</span></label>
          <label><input type="radio" name="tipo" value="Stock"><span>📦 Stock</span></label>
          <label><input type="radio" name="tipo" value="Otro"><span>📌 Otro</span></label>
        </div>
      </div>
      <label class="inc-f">¿Qué pasó?
        <textarea name="descripcion" id="incDesc" maxlength="250" required placeholder="Ej: La impresora de tickets se atascó"></textarea>
        <small>Sucursal: <?= e($sucursal) ?> · se guarda con la fecha y hora actuales.</small>
      </label>
    </div>
    <div class="inc-pie">
      <button type="button" class="btn g flex" id="incCancelar">Cancelar</button>
      <button class="btn y flex">Enviar incidencia</button>
    </div>
  </form>
</aside>
<script>
(function () {
  var fondo = document.getElementById('incFondo'), dr = document.getElementById('incDrawer'),
      abrirBtn = document.getElementById('incAbrir');
  function abrir() {
    fondo.classList.add('abierto'); dr.classList.add('abierto'); dr.setAttribute('aria-hidden', 'false');
    document.getElementById('incDesc').focus();
  }
  function cerrar() {
    fondo.classList.remove('abierto'); dr.classList.remove('abierto'); dr.setAttribute('aria-hidden', 'true');
    abrirBtn.focus();
  }
  abrirBtn.addEventListener('click', abrir);
  fondo.addEventListener('click', cerrar);
  document.getElementById('incCerrar').addEventListener('click', cerrar);
  document.getElementById('incCancelar').addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dr.classList.contains('abierto')) cerrar(); });
})();
</script>
<?php endif; ?>