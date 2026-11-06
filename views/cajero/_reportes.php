<?php
// Ventanas de reportes del cajero: incidencias y merma / ajuste de stock.
if (empty($reporte)) {
    return;
}
?>
<link rel="stylesheet" href="<?= Controller::base() ?>/css/cajero-reportes.css?v=1">
<script>window.REP = <?= json_encode($reporte, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= Controller::base() ?>/js/reportes.js?v=4"></script>
