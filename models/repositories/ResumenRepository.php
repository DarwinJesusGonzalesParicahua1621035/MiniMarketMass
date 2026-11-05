<?php
class ResumenRepository extends Repository
{
    private function escalar(string $sql)
    {
        return $this->db()->query($sql)->fetchColumn();
    }

    private function filas(string $sql): array
    {
        return $this->db()->query($sql)->fetchAll();
    }

    public function kpis(): array
    {
        $v = "FROM venta WHERE estado = 'EMITIDA' AND DATE(fecha) = ";
        return [
            'ventas_hoy'        => (float)$this->escalar("SELECT COALESCE(SUM(total), 0) $v CURDATE()"),
            'ventas_ayer'       => (float)$this->escalar("SELECT COALESCE(SUM(total), 0) $v CURDATE() - INTERVAL 1 DAY"),
            'comprobantes'      => (int)$this->escalar("SELECT COUNT(*) $v CURDATE()"),
            'comprobantes_ayer' => (int)$this->escalar("SELECT COUNT(*) $v CURDATE() - INTERVAL 1 DAY"),
            'ticket'            => (float)$this->escalar("SELECT COALESCE(AVG(total), 0) $v CURDATE()"),
            'stock_bajo'        => (int)$this->escalar('SELECT COUNT(*) FROM stock WHERE cantidad < 10'),
            'incidentes'        => (int)$this->escalar("SELECT COUNT(*) FROM incidente WHERE estado = 'ABIERTO'"),
            'stock_total'       => (float)$this->escalar('SELECT COALESCE(SUM(cantidad), 0) FROM stock'),
            'clientes'          => (int)$this->escalar('SELECT COUNT(*) FROM cliente'),
        ];
    }

    public function sucursales(): array
    {
        return $this->filas('SELECT id, nombre FROM sucursal ORDER BY id');
    }

    // Ventas de los últimos 7 días, separadas por sucursal.
    public function semana(): array
    {
        return $this->filas("SELECT sucursal_id, DATE(fecha) AS dia, SUM(total) AS total FROM venta
            WHERE estado = 'EMITIDA' AND fecha >= CURDATE() - INTERVAL 6 DAY GROUP BY sucursal_id, DATE(fecha)");
    }

    // Medios de pago de los últimos 7 días, separados por sucursal.
    public function medios(): array
    {
        return $this->filas("SELECT v.sucursal_id, p.medio_pago AS medio, SUM(p.monto) AS total
            FROM pago_venta p JOIN venta v ON v.id = p.venta_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= CURDATE() - INTERVAL 6 DAY
            GROUP BY v.sucursal_id, p.medio_pago");
    }

    // Estado de cada sucursal hoy: cajas abiertas y personal con su asistencia del día.
    // Devuelve [sucursal_id => ['id','nombre','direccion','cajas','abiertas','empleados' => [...]]]
    public function sucursalesHoy(): array
    {
        $out = [];
        foreach ($this->filas("SELECT s.id, s.nombre, s.direccion,
                (SELECT COUNT(*) FROM caja c WHERE c.sucursal_id = s.id) AS cajas,
                (SELECT COUNT(*) FROM caja c JOIN historia_caja h ON h.caja_id = c.id AND h.estado = 'ABIERTA'
                    WHERE c.sucursal_id = s.id) AS abiertas
            FROM sucursal s ORDER BY s.id") as $s) {
            $out[(int)$s['id']] = [
                'id' => (int)$s['id'], 'nombre' => $s['nombre'], 'direccion' => $s['direccion'],
                'cajas' => (int)$s['cajas'], 'abiertas' => (int)$s['abiertas'], 'empleados' => [],
            ];
        }
        // Empleados activos de cada sucursal + su último registro de asistencia de hoy (si lo hay)
        foreach ($this->filas("SELECT e.id, e.sucursal_id, e.nombre, e.rol, a.entrada, a.estado
            FROM empleado e
            LEFT JOIN asistencia a ON a.id = (
                SELECT MAX(x.id) FROM asistencia x
                WHERE x.empleado_id = e.id AND x.entrada >= CURDATE() AND x.entrada < CURDATE() + INTERVAL 1 DAY)
            WHERE e.activo = 1 ORDER BY e.nombre") as $e) {
            $sid = (int)$e['sucursal_id'];
            if (!isset($out[$sid])) continue;
            $reg = $e['entrada'] !== null ? ['entrada' => $e['entrada'], 'estado' => $e['estado']] : null;
            $out[$sid]['empleados'][] = [
                'nombre' => $e['nombre'], 'rol' => $e['rol'],
                'entrada' => $reg ? $e['entrada'] : null,
                'clase' => AsistenciaRepository::clasificar($reg),
            ];
        }
        return $out;
    }

    public function stockBajo(): array
    {
        return $this->filas("SELECT p.nombre AS producto, s.nombre AS sucursal, st.cantidad
            FROM stock st JOIN producto p ON p.id = st.producto_id JOIN sucursal s ON s.id = st.sucursal_id
            WHERE st.cantidad < 10 ORDER BY st.cantidad ASC LIMIT 3");
    }

    public function incidentesAbiertos(): array
    {
        return $this->filas("SELECT i.tipo, i.descripcion, s.nombre AS sucursal
            FROM incidente i JOIN sucursal s ON s.id = i.sucursal_id
            WHERE i.estado = 'ABIERTO' ORDER BY i.fecha DESC LIMIT 3");
    }

    public function masVendidos(): array
    {
        return $this->filas("SELECT p.nombre, SUM(d.cantidad) AS cantidad
            FROM detalle_venta d JOIN venta v ON v.id = d.venta_id JOIN producto p ON p.id = d.producto_id
            WHERE v.estado = 'EMITIDA' GROUP BY p.id, p.nombre ORDER BY cantidad DESC LIMIT 3");
    }
}