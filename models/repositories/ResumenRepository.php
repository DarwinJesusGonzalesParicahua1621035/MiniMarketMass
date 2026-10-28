<?php
class ResumenRepository extends Repository
{
    public function kpis(): array
    {
        $q = fn($sql) => $this->db()->query($sql)->fetchColumn();
        return [
            'ventas_hoy'   => (float)$q("SELECT COALESCE(SUM(total), 0) FROM venta WHERE estado = 'EMITIDA' AND DATE(fecha) = CURDATE()"),
            'comprobantes' => (int)$q("SELECT COUNT(*) FROM venta WHERE estado = 'EMITIDA' AND DATE(fecha) = CURDATE()"),
            'stock_bajo'   => (int)$q('SELECT COUNT(*) FROM stock WHERE cantidad < 10'),
            'incidentes'   => (int)$q("SELECT COUNT(*) FROM incidente WHERE estado = 'ABIERTO'"),
        ];
    }

    public function semana(): array
    {
        return $this->db()->query("SELECT DATE(fecha) AS dia, SUM(total) AS total FROM venta
            WHERE estado = 'EMITIDA' AND fecha >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(fecha)")->fetchAll();
    }

    public function medios(): array
    {
        return $this->db()->query("SELECT p.medio_pago AS medio, SUM(p.monto) AS total
            FROM pago_venta p JOIN venta v ON v.id = p.venta_id WHERE v.estado = 'EMITIDA'
            GROUP BY p.medio_pago ORDER BY total DESC")->fetchAll();
    }

    public function ultimas(): array
    {
        return $this->db()->query("SELECT CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
            s.nombre AS sucursal, COALESCE(c.nombre, '—') AS cliente, v.total, v.estado
            FROM venta v JOIN sucursal s ON s.id = v.sucursal_id LEFT JOIN cliente c ON c.id = v.cliente_id
            ORDER BY v.fecha DESC, v.id DESC LIMIT 5")->fetchAll();
    }
}