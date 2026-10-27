<?php
class CajaRepository extends Repository
{
    protected string $tabla = 'caja';
    protected string $listaSql = "SELECT c.id, s.nombre AS sucursal, c.nombre AS caja,
        COALESCE(e.nombre, '—') AS cajero, COALESCE(h.apertura, '—') AS apertura,
        COALESCE(h.monto_inicial, '—') AS monto_inicial, COALESCE(h.estado, 'CERRADA') AS estado
        FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
        LEFT JOIN historia_caja h ON h.caja_id = c.id AND h.estado = 'ABIERTA'
        LEFT JOIN empleado e ON e.id = h.empleado_id ORDER BY c.id";

    public function historial(): array
    {
        return $this->db()->query("SELECT h.id, s.nombre AS sucursal, c.nombre AS caja, e.nombre AS cajero,
            h.apertura, COALESCE(h.cierre, '—') AS cierre, h.monto_inicial,
            COALESCE(h.monto_final, '—') AS monto_final, h.estado
            FROM historia_caja h JOIN caja c ON c.id = h.caja_id JOIN sucursal s ON s.id = c.sucursal_id
            JOIN empleado e ON e.id = h.empleado_id ORDER BY h.apertura DESC")->fetchAll();
    }
}