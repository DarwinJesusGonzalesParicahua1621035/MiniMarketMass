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

    public function semana(): array
    {
        return $this->filas("SELECT DATE(fecha) AS dia, SUM(total) AS total FROM venta
            WHERE estado = 'EMITIDA' AND fecha >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(fecha)");
    }

    public function medios(): array
    {
        return $this->filas("SELECT p.medio_pago AS medio, SUM(p.monto) AS total
            FROM pago_venta p JOIN venta v ON v.id = p.venta_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= CURDATE() - INTERVAL 6 DAY
            GROUP BY p.medio_pago ORDER BY total DESC");
    }

    public function ultimas(): array
    {
        return $this->filas("SELECT CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
            DATE_FORMAT(v.fecha, '%d/%m/%Y %H:%i') AS fecha,
            s.nombre AS sucursal, COALESCE(c.nombre, '—') AS cliente, v.total, v.estado
            FROM venta v JOIN sucursal s ON s.id = v.sucursal_id LEFT JOIN cliente c ON c.id = v.cliente_id
            ORDER BY v.fecha DESC, v.id DESC LIMIT 5");
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