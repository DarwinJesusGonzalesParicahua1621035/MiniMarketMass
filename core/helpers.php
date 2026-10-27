<?php
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n) { return 'S/ ' . number_format((float)$n, 2); }

function celda($v, $t = 'texto') {
    $tags = ['EMITIDA'=>'ok','ANULADA'=>'bad','ABIERTA'=>'ok','CERRADA'=>'','ABIERTO'=>'y','RESUELTO'=>'ok',
        'PRESENTE'=>'ok','TARDANZA'=>'y','FALTA'=>'bad','ENTRADA'=>'ok','SALIDA'=>'b','AJUSTE'=>'y','MERMA'=>'bad',
        'ADMINISTRADOR'=>'b','CAJERO'=>'y','ALMACENERO'=>'','BOLETA'=>'','FACTURA'=>'b'];
    switch ($t) {
        case 'etiqueta': return '<span class="tag ' . ($tags[$v] ?? '') . '">' . e($v) . '</span>';
        case 'bool':     return $v ? '<span class="tag ok">Sí</span>' : '<span class="tag bad">No</span>';
        case 'dinero':   return is_numeric($v) ? money($v) : e($v);
        case 'stock':    $n = (float)$v; return $n < 10 ? '<span class="tag bad">' . $n . ' · bajo</span>' : (string)$n;
        default:         return e($v);
    }
}