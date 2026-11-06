<?php
/**
 * Arma el "paquete de datos" que se le da a la IA: ventas, stock, incidentes, cajas,
 * personal, asistencia, capacitaciones, proveedores, catálogo, clientes (solo cifras) y sucursales.
 * Solo lectura. NUNCA se envían: usuarios, claves, secretos 2FA, DNI/RUC, teléfonos ni correos.
 * Los clientes solo van como cifras agregadas (sin nombres).
 */
class AsistenteRepository extends Repository
{
    private const DIAS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    private function filas(string $sql, array $p = []): array
    {
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    public function contexto(): string
    {
        $hoy      = date('Y-m-d');
        $ayer     = date('Y-m-d', strtotime('-1 day'));
        $hace7    = date('Y-m-d', strtotime('-6 day'));
        $hace30   = date('Y-m-d 00:00:00', strtotime('-29 day'));
        $inicioMes = date('Y-m-01');
        $L = [];
        $L[] = 'Fecha y hora actual: ' . self::DIAS[(int)date('w')] . ' ' . date('Y-m-d H:i') . ' (hora de Arequipa).';
        $L[] = 'Los datos de ventas cubren los últimos 30 días (desde ' . substr($hace30, 0, 10) . ').';

        // ---- Ventas por sucursal ----
        $totales = [];
        foreach ($this->filas('SELECT nombre FROM sucursal ORDER BY id') as $s) {
            $totales[$s['nombre']] = ['hoy' => [0, 0], 'ayer' => [0, 0], '7d' => [0, 0], 'mes' => [0, 0], '30d' => [0, 0]];
        }
        $diarias = $this->filas("SELECT s.nombre AS sucursal, DATE(v.fecha) AS dia, COUNT(*) AS n, SUM(v.total) AS total
            FROM venta v JOIN sucursal s ON s.id = v.sucursal_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ?
            GROUP BY s.id, s.nombre, DATE(v.fecha) ORDER BY dia DESC, s.id", [$hace30]);
        foreach ($diarias as $f) {
            $d = $f['dia'];
            $periodos = ['30d' => true, '7d' => $d >= $hace7, 'mes' => $d >= $inicioMes, 'hoy' => $d === $hoy, 'ayer' => $d === $ayer];
            foreach ($periodos as $k => $entra) {
                if ($entra) {
                    $totales[$f['sucursal']][$k][0] += (float)$f['total'];
                    $totales[$f['sucursal']][$k][1] += (int)$f['n'];
                }
            }
        }
        $L[] = "\n== VENTAS EMITIDAS POR SUCURSAL (total en soles y n.º de comprobantes) ==";
        $nombres = ['hoy' => 'hoy', 'ayer' => 'ayer', '7d' => 'últimos 7 días', 'mes' => 'mes actual', '30d' => 'últimos 30 días'];
        foreach ($totales as $suc => $per) {
            $partes = [];
            foreach ($nombres as $k => $n) {
                $partes[] = $n . ' ' . money($per[$k][0]) . ' (' . $per[$k][1] . ')';
            }
            $L[] = "- $suc: " . implode(' | ', $partes);
        }

        $L[] = "\n== VENTAS POR DÍA Y SUCURSAL (los días que no aparecen no tuvieron ventas) ==";
        foreach ($diarias as $f) {
            $L[] = $f['dia'] . ' ' . self::DIAS[(int)date('w', strtotime($f['dia']))] . ' | ' . $f['sucursal']
                 . ' | ' . money($f['total']) . ' | ' . (int)$f['n'] . ' comprobantes';
        }

        $anul = $this->filas("SELECT s.nombre AS sucursal, COUNT(*) AS n, COALESCE(SUM(v.total), 0) AS total
            FROM venta v JOIN sucursal s ON s.id = v.sucursal_id
            WHERE v.estado = 'ANULADA' AND v.fecha >= ? GROUP BY s.id, s.nombre ORDER BY s.id", [$hace30]);
        $L[] = "\n== VENTAS ANULADAS (últimos 30 días) ==";
        foreach ($anul as $f) {
            $L[] = '- ' . $f['sucursal'] . ': ' . (int)$f['n'] . ' anuladas por ' . money($f['total']);
        }
        if (!$anul) {
            $L[] = '- Ninguna.';
        }

        // ---- Medios de pago ----
        $L[] = "\n== MEDIOS DE PAGO (últimos 30 días, por sucursal) ==";
        foreach ($this->filas("SELECT s.nombre AS sucursal, p.medio_pago AS medio, SUM(p.monto) AS total
            FROM pago_venta p JOIN venta v ON v.id = p.venta_id JOIN sucursal s ON s.id = v.sucursal_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ?
            GROUP BY s.id, s.nombre, p.medio_pago ORDER BY s.id, total DESC", [$hace30]) as $f) {
            $L[] = '- ' . $f['sucursal'] . ' | ' . $f['medio'] . ' | ' . money($f['total']);
        }

        // ---- Productos más vendidos ----
        $L[] = "\n== 15 PRODUCTOS MÁS VENDIDOS (últimos 30 días, todas las sucursales) ==";
        foreach ($this->filas("SELECT p.nombre, p.unidad, SUM(d.cantidad) AS cant, SUM(d.total_linea) AS monto
            FROM detalle_venta d JOIN venta v ON v.id = d.venta_id JOIN producto p ON p.id = d.producto_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ?
            GROUP BY p.id, p.nombre, p.unidad ORDER BY cant DESC LIMIT 15", [$hace30]) as $i => $f) {
            $L[] = ($i + 1) . '. ' . $f['nombre'] . ' | ' . num($f['cant']) . ' ' . $f['unidad'] . ' | ' . money($f['monto']);
        }

        // ---- Stock ----
        $L[] = "\n== RESUMEN DE STOCK POR SUCURSAL ==";
        foreach ($this->filas("SELECT s.nombre AS sucursal, COUNT(*) AS productos,
                SUM(st.cantidad <= 0) AS agotados, SUM(st.cantidad > 0 AND st.cantidad < 10) AS bajos,
                SUM(st.cantidad * p.precio) AS valor
            FROM stock st JOIN producto p ON p.id = st.producto_id JOIN sucursal s ON s.id = st.sucursal_id
            WHERE p.activo = 1 GROUP BY s.id, s.nombre ORDER BY s.id") as $f) {
            $L[] = '- ' . $f['sucursal'] . ': ' . (int)$f['productos'] . ' productos, ' . (int)$f['agotados'] . ' agotados, '
                 . (int)$f['bajos'] . ' con stock bajo, valor del inventario a precio de venta ' . money($f['valor']);
        }

        $L[] = "\n== STOCK ACTUAL (de menor a mayor; 'bajo' = menos de 10; 'AGOTADO' = 0; máx. 1500 filas) ==";
        foreach ($this->filas("SELECT p.nombre AS producto, c.nombre AS categoria, s.nombre AS sucursal,
                p.unidad, p.precio, st.cantidad
            FROM stock st JOIN producto p ON p.id = st.producto_id
            JOIN categoria c ON c.id = p.categoria_id JOIN sucursal s ON s.id = st.sucursal_id
            WHERE p.activo = 1 ORDER BY st.cantidad ASC, p.nombre LIMIT 1500") as $f) {
            $c = (float)$f['cantidad'];
            $marca = $c <= 0 ? ' AGOTADO' : ($c < 10 ? ' (bajo)' : '');
            $L[] = $f['producto'] . ' [' . $f['categoria'] . '] | ' . $f['sucursal'] . ' | ' . num($c) . ' ' . $f['unidad']
                 . $marca . ' | precio ' . money($f['precio']);
        }

        // ---- Movimientos de stock ----
        $L[] = "\n== ENTRADAS, MERMAS Y AJUSTES DE STOCK (últimos 30 días) ==";
        $mov = $this->filas("SELECT m.tipo, p.nombre AS producto, s.nombre AS sucursal, SUM(m.cantidad) AS cant, COUNT(*) AS veces
            FROM movimiento_stock m JOIN producto p ON p.id = m.producto_id JOIN sucursal s ON s.id = m.sucursal_id
            WHERE m.tipo IN ('ENTRADA', 'MERMA', 'AJUSTE') AND m.fecha >= ?
            GROUP BY m.tipo, p.id, p.nombre, s.id, s.nombre ORDER BY m.tipo, cant DESC LIMIT 40", [$hace30]);
        foreach ($mov as $f) {
            $L[] = $f['tipo'] . ' | ' . $f['producto'] . ' | ' . $f['sucursal'] . ' | ' . num($f['cant']) . ' u. en ' . (int)$f['veces'] . ' movimiento(s)';
        }
        if (!$mov) {
            $L[] = '- Sin movimientos.';
        }

        // ---- Incidentes y cajas ----
        $L[] = "\n== INCIDENTES ABIERTOS ==";
        $inc = $this->filas("SELECT i.tipo, i.descripcion, s.nombre AS sucursal, i.fecha
            FROM incidente i JOIN sucursal s ON s.id = i.sucursal_id
            WHERE i.estado = 'ABIERTO' ORDER BY i.fecha DESC LIMIT 10");
        foreach ($inc as $f) {
            $L[] = '- ' . $f['fecha'] . ' | ' . $f['sucursal'] . ' | ' . $f['tipo'] . ': ' . $f['descripcion'];
        }
        if (!$inc) {
            $L[] = '- Ninguno.';
        }

        $L[] = "\n== CAJAS ABIERTAS AHORA ==";
        $cajas = $this->filas("SELECT s.nombre AS sucursal, c.nombre AS caja
            FROM historia_caja h JOIN caja c ON c.id = h.caja_id JOIN sucursal s ON s.id = c.sucursal_id
            WHERE h.estado = 'ABIERTA' ORDER BY s.id, c.nombre");
        foreach ($cajas as $f) {
            $L[] = '- ' . $f['sucursal'] . ' | ' . $f['caja'];
        }
        if (!$cajas) {
            $L[] = '- Ninguna caja abierta.';
        }

        $this->seccionesExtra($L, $hace30);

        return implode("\n", $L);
    }

    /** Secciones adicionales: personal, asistencia, capacitaciones, proveedores, catálogo, clientes, sucursales, cajas, horas. */
    private function seccionesExtra(array &$L, string $hace30): void
    {
        $desde = substr($hace30, 0, 10);

        // ---- Sucursales ----
        $L[] = "\n== SUCURSALES ==";
        foreach ($this->filas("SELECT s.nombre, s.direccion, d.nombre AS distrito, COUNT(c.id) AS cajas
            FROM sucursal s JOIN distrito d ON d.id = s.distrito_id LEFT JOIN caja c ON c.sucursal_id = s.id
            GROUP BY s.id, s.nombre, s.direccion, d.nombre ORDER BY s.id") as $f) {
            $L[] = '- ' . $f['nombre'] . ' | ' . $f['direccion'] . ', ' . $f['distrito'] . ' | ' . (int)$f['cajas'] . ' cajas registradas';
        }

        // ---- Personal (sin usuario, clave ni 2FA) ----
        $tot = $this->filas("SELECT COUNT(*) AS total, COALESCE(SUM(activo = 1), 0) AS activos FROM empleado")[0];
        $L[] = "\n== EMPLEADOS (total registrados: " . (int)$tot['total'] . '; activos: ' . (int)$tot['activos'] . '; inactivos: ' . ((int)$tot['total'] - (int)$tot['activos']) . ') ==';
        $L[] = 'Resumen por sucursal y rol (activos / inactivos):';
        foreach ($this->filas("SELECT s.nombre AS sucursal, e.rol, SUM(e.activo = 1) AS act, SUM(e.activo = 0) AS inact
            FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id
            GROUP BY s.id, s.nombre, e.rol ORDER BY s.id, e.rol") as $f) {
            $L[] = '- ' . $f['sucursal'] . ' | ' . $f['rol'] . ' | ' . (int)$f['act'] . ' activos / ' . (int)$f['inact'] . ' inactivos';
        }
        $L[] = 'Lista (nombre | rol | sucursal | estado | supervisor):';
        foreach ($this->filas("SELECT e.nombre, e.rol, s.nombre AS sucursal, e.activo, sup.nombre AS supervisor
            FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id LEFT JOIN empleado sup ON sup.id = e.supervisor_id
            ORDER BY e.activo DESC, s.id, e.rol, e.nombre LIMIT 300") as $f) {
            $L[] = $f['nombre'] . ' | ' . $f['rol'] . ' | ' . $f['sucursal'] . ' | ' . ($f['activo'] ? 'activo' : 'inactivo')
                 . ' | ' . ($f['supervisor'] ?: 'sin supervisor');
        }

        // ---- Ventas por empleado ----
        $L[] = "\n== VENTAS POR EMPLEADO (últimos 30 días, solo EMITIDAS) ==";
        foreach ($this->filas("SELECT e.nombre, s.nombre AS sucursal, COUNT(*) AS n, SUM(v.total) AS total
            FROM venta v JOIN empleado e ON e.id = v.empleado_id JOIN sucursal s ON s.id = v.sucursal_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ? GROUP BY e.id, e.nombre, s.id, s.nombre
            ORDER BY total DESC LIMIT 40", [$hace30]) as $f) {
            $L[] = '- ' . $f['nombre'] . ' | ' . $f['sucursal'] . ' | ' . money($f['total']) . ' | ' . (int)$f['n'] . ' comprobantes';
        }

        // ---- Asistencia ----
        $L[] = "\n== ASISTENCIA (últimos 30 días, por empleado; PRESENTE = a tiempo, TARDANZA = después de 08:30) ==";
        foreach ($this->filas("SELECT e.nombre, s.nombre AS sucursal,
                SUM(a.estado = 'PRESENTE') AS ok, SUM(a.estado = 'TARDANZA') AS tarde, SUM(a.estado = 'FALTA') AS falta
            FROM asistencia a JOIN empleado e ON e.id = a.empleado_id JOIN sucursal s ON s.id = e.sucursal_id
            WHERE a.entrada >= ? GROUP BY e.id, e.nombre, s.id, s.nombre ORDER BY falta DESC, tarde DESC, e.nombre LIMIT 200", [$hace30]) as $f) {
            $L[] = '- ' . $f['nombre'] . ' | ' . $f['sucursal'] . ' | ' . (int)$f['ok'] . ' a tiempo, '
                 . (int)$f['tarde'] . ' tardanzas, ' . (int)$f['falta'] . ' faltas';
        }
        $L[] = 'Asistencia de HOY (' . date('Y-m-d') . '):';
        $hoy = $this->filas("SELECT e.nombre, s.nombre AS sucursal, a.estado, TIME(a.entrada) AS ent, TIME(a.salida) AS sal
            FROM asistencia a JOIN empleado e ON e.id = a.empleado_id JOIN sucursal s ON s.id = e.sucursal_id
            WHERE DATE(a.entrada) = CURDATE() ORDER BY a.entrada");
        foreach ($hoy as $f) {
            $L[] = '- ' . $f['nombre'] . ' | ' . $f['sucursal'] . ' | ' . $f['estado'] . ' | entrada ' . $f['ent']
                 . ' | salida ' . ($f['sal'] ?: 'sin marcar');
        }
        if (!$hoy) {
            $L[] = '- Nadie ha marcado asistencia hoy.';
        }

        // ---- Capacitaciones ----
        $L[] = "\n== CAPACITACIONES (últimos 12 meses) ==";
        $cap = $this->filas("SELECT c.tema, c.fecha, c.horas, e.nombre FROM capacitacion c JOIN empleado e ON e.id = c.empleado_id
            WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) ORDER BY c.fecha DESC, c.tema LIMIT 120");
        foreach ($cap as $f) {
            $L[] = $f['fecha'] . ' | ' . $f['tema'] . ' | ' . $f['nombre'] . ' | ' . num($f['horas']) . ' h';
        }
        if (!$cap) {
            $L[] = '- Sin capacitaciones registradas.';
        }

        // ---- Proveedores ----
        $L[] = "\n== PROVEEDORES (razón social | estado | productos activos | contacto) ==";
        foreach ($this->filas("SELECT pr.razon_social, pr.contacto, pr.activo, COUNT(p.id) AS productos
            FROM proveedor pr LEFT JOIN producto p ON p.proveedor_id = pr.id AND p.activo = 1
            GROUP BY pr.id, pr.razon_social, pr.contacto, pr.activo ORDER BY pr.activo DESC, pr.razon_social LIMIT 100") as $f) {
            $L[] = $f['razon_social'] . ' | ' . ($f['activo'] ? 'activo' : 'inactivo') . ' | ' . (int)$f['productos'] . ' productos | ' . $f['contacto'];
        }
        $L[] = 'Compras a proveedores (ENTRADAS de stock, últimos 30 días):';
        $comp = $this->filas("SELECT pr.razon_social, COUNT(*) AS movs, SUM(m.cantidad) AS unidades
            FROM movimiento_stock m JOIN proveedor pr ON pr.id = m.proveedor_id
            WHERE m.tipo = 'ENTRADA' AND m.fecha >= ? GROUP BY pr.id, pr.razon_social ORDER BY unidades DESC LIMIT 30", [$hace30]);
        foreach ($comp as $f) {
            $L[] = '- ' . $f['razon_social'] . ' | ' . (int)$f['movs'] . ' ingresos | ' . num($f['unidades']) . ' unidades';
        }
        if (!$comp) {
            $L[] = '- Sin ingresos de proveedores.';
        }

        // ---- Catálogo y categorías ----
        $L[] = "\n== CATEGORÍAS Y CATÁLOGO (productos activos | inactivos | ventas 30 días) ==";
        foreach ($this->filas("SELECT c.nombre, SUM(p.activo = 1) AS act, SUM(p.activo = 0) AS inact,
                COALESCE((SELECT SUM(d.total_linea) FROM detalle_venta d JOIN venta v ON v.id = d.venta_id
                          JOIN producto p2 ON p2.id = d.producto_id
                          WHERE p2.categoria_id = c.id AND v.estado = 'EMITIDA' AND v.fecha >= ?), 0) AS ventas
            FROM categoria c LEFT JOIN producto p ON p.categoria_id = c.id
            GROUP BY c.id, c.nombre ORDER BY ventas DESC", [$hace30]) as $f) {
            $L[] = '- ' . $f['nombre'] . ' | ' . (int)$f['act'] . ' activos | ' . (int)$f['inact'] . ' inactivos | ' . money($f['ventas']);
        }
        $L[] = 'Productos activos SIN ventas en 30 días (máx. 40):';
        $sin = $this->filas("SELECT p.nombre, c.nombre AS categoria FROM producto p JOIN categoria c ON c.id = p.categoria_id
            WHERE p.activo = 1 AND NOT EXISTS (SELECT 1 FROM detalle_venta d JOIN venta v ON v.id = d.venta_id
                WHERE d.producto_id = p.id AND v.estado = 'EMITIDA' AND v.fecha >= ?) ORDER BY p.nombre LIMIT 40", [$hace30]);
        foreach ($sin as $f) {
            $L[] = '- ' . $f['nombre'] . ' [' . $f['categoria'] . ']';
        }
        if (!$sin) {
            $L[] = '- Todos los productos activos tuvieron ventas.';
        }
        $L[] = 'Cambios de precio (últimos 30 días):';
        $pre = $this->filas("SELECT p.nombre, h.precio, h.fecha_inicio,
                (SELECT h2.precio FROM historial_precio h2 WHERE h2.producto_id = h.producto_id AND h2.fecha_inicio < h.fecha_inicio
                 ORDER BY h2.fecha_inicio DESC LIMIT 1) AS anterior
            FROM historial_precio h JOIN producto p ON p.id = h.producto_id
            WHERE h.fecha_inicio >= ? ORDER BY h.fecha_inicio DESC LIMIT 30", [$hace30]);
        foreach ($pre as $f) {
            $L[] = '- ' . substr($f['fecha_inicio'], 0, 10) . ' | ' . $f['nombre'] . ' | '
                 . ($f['anterior'] !== null ? money($f['anterior']) . ' → ' : 'nuevo: ') . money($f['precio']);
        }
        if (!$pre) {
            $L[] = '- Sin cambios de precio.';
        }

        // ---- Clientes (solo cifras, sin nombres ni documentos) ----
        $cl = $this->filas("SELECT COUNT(*) AS total, COALESCE(SUM(tipo_documento = 'DNI'), 0) AS dni, COALESCE(SUM(tipo_documento = 'RUC'), 0) AS ruc FROM cliente")[0];
        $L[] = "\n== CLIENTES (solo cifras; los datos personales no se comparten) ==";
        $L[] = '- Registrados: ' . (int)$cl['total'] . ' (' . (int)$cl['dni'] . ' con DNI, ' . (int)$cl['ruc'] . ' con RUC).';
        $vc = $this->filas("SELECT COUNT(*) AS n, COUNT(DISTINCT cliente_id) AS clientes, COALESCE(SUM(total), 0) AS total
            FROM venta WHERE estado = 'EMITIDA' AND cliente_id IS NOT NULL AND fecha >= ?", [$hace30])[0];
        $L[] = '- Últimos 30 días: ' . (int)$vc['n'] . ' comprobantes a clientes identificados (' . (int)$vc['clientes'] . ' clientes distintos) por ' . money($vc['total']) . '.';
        $L[] = 'Comprobantes por tipo (últimos 30 días, EMITIDAS):';
        foreach ($this->filas("SELECT tipo_comprobante, COUNT(*) AS n, SUM(total) AS total FROM venta
            WHERE estado = 'EMITIDA' AND fecha >= ? GROUP BY tipo_comprobante", [$hace30]) as $f) {
            $L[] = '- ' . $f['tipo_comprobante'] . ' | ' . (int)$f['n'] . ' | ' . money($f['total']);
        }

        // ---- Horas pico ----
        $L[] = "\n== VENTAS POR HORA DEL DÍA (últimos 30 días, todas las sucursales, EMITIDAS) ==";
        foreach ($this->filas("SELECT HOUR(fecha) AS h, COUNT(*) AS n, SUM(total) AS total FROM venta
            WHERE estado = 'EMITIDA' AND fecha >= ? GROUP BY HOUR(fecha) ORDER BY h", [$hace30]) as $f) {
            $L[] = sprintf('%02d:00 | %d comprobantes | %s', $f['h'], $f['n'], money($f['total']));
        }

        // ---- Cierres de caja (con diferencia de efectivo) ----
        $L[] = "\n== CIERRES DE CAJA (últimos 30 días; esperado = monto inicial + cobros en EFECTIVO; diferencia = final − esperado) ==";
        $cie = $this->filas("SELECT s.nombre AS sucursal, c.nombre AS caja, e.nombre AS cajero, h.apertura, h.cierre,
                h.monto_inicial, h.monto_final,
                h.monto_inicial + COALESCE((SELECT SUM(p.monto) FROM pago_venta p JOIN venta v ON v.id = p.venta_id
                    WHERE v.historia_caja_id = h.id AND v.estado = 'EMITIDA' AND p.medio_pago = 'EFECTIVO'), 0) AS esperado
            FROM historia_caja h JOIN caja c ON c.id = h.caja_id JOIN sucursal s ON s.id = c.sucursal_id
            JOIN empleado e ON e.id = h.empleado_id
            WHERE h.estado = 'CERRADA' AND h.cierre >= ? ORDER BY h.cierre DESC LIMIT 60", [$hace30]);
        foreach ($cie as $f) {
            $dif = (float)$f['monto_final'] - (float)$f['esperado'];
            $L[] = substr($f['cierre'], 0, 16) . ' | ' . $f['sucursal'] . ' | ' . $f['caja'] . ' | ' . $f['cajero']
                 . ' | inicial ' . money($f['monto_inicial']) . ' | final ' . money($f['monto_final'])
                 . ' | esperado ' . money($f['esperado']) . ' | diferencia ' . ($dif >= 0 ? '+' : '-') . money(abs($dif));
        }
        if (!$cie) {
            $L[] = '- Sin cierres de caja.';
        }

        // ---- Incidentes recientes ----
        $L[] = "\n== INCIDENTES (últimos 30 días, por tipo y estado) ==";
        foreach ($this->filas("SELECT tipo, estado, COUNT(*) AS n FROM incidente WHERE fecha >= ?
            GROUP BY tipo, estado ORDER BY n DESC", [$hace30]) as $f) {
            $L[] = '- ' . $f['tipo'] . ' | ' . $f['estado'] . ' | ' . (int)$f['n'];
        }
    }
}
