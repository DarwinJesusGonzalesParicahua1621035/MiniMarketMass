<?php
class PagoRepository extends Repository
{
    public const MEDIOS = ['EFECTIVO', 'YAPE', 'PLIN', 'TARJETA'];

    protected string $tabla = 'pago_venta';

    public function listado(?string $medio, ?int $sucursal, string $desde, string $hasta): array
    {
        [$where, $args] = $this->filtros($medio, $sucursal, $desde, $hasta);
        $s = $this->db()->prepare("SELECT p.id, v.id AS venta_id,
                CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
                v.fecha, s.nombre AS sucursal, e.nombre AS cajero, p.medio_pago, p.monto,
                COALESCE(p.referencia, '—') AS referencia, v.estado,
                (SELECT COUNT(*) FROM pago_venta x WHERE x.venta_id = v.id) AS medios
            FROM pago_venta p
            JOIN venta v ON v.id = p.venta_id
            JOIN sucursal s ON s.id = v.sucursal_id
            JOIN empleado e ON e.id = v.empleado_id
            WHERE $where
            ORDER BY v.fecha DESC, v.id DESC, p.id");
        $s->execute($args);
        return $s->fetchAll();
    }

    /** Total cobrado por medio de pago; las ventas anuladas no suman. */
    public function totales(?string $medio, ?int $sucursal, string $desde, string $hasta): array
    {
        [$where, $args] = $this->filtros($medio, $sucursal, $desde, $hasta);
        $s = $this->db()->prepare("SELECT p.medio_pago, SUM(p.monto) AS total
            FROM pago_venta p JOIN venta v ON v.id = p.venta_id
            WHERE $where AND v.estado = 'EMITIDA'
            GROUP BY p.medio_pago");
        $s->execute($args);
        $o = array_fill_keys(self::MEDIOS, 0.0);
        foreach ($s->fetchAll() as $r) {
            $o[$r['medio_pago']] = (float)$r['total'];
        }
        return $o;
    }

    private function filtros(?string $medio, ?int $sucursal, string $desde, string $hasta): array
    {
        $where = ['v.fecha >= ?', 'v.fecha < ? + INTERVAL 1 DAY'];
        $args = [$desde, $hasta];
        if ($medio !== null) {
            $where[] = 'p.medio_pago = ?';
            $args[] = $medio;
        }
        if ($sucursal !== null) {
            $where[] = 'v.sucursal_id = ?';
            $args[] = $sucursal;
        }
        return [implode(' AND ', $where), $args];
    }
}
