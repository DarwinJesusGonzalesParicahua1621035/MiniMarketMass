<?php
class VentaRepository extends Repository
{
    protected string $tabla = 'venta';
    protected string $listaSql = "SELECT v.id, s.nombre AS sucursal, v.tipo_comprobante AS tipo,
        CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
        COALESCE(c.nombre, '—') AS cliente, v.fecha, v.subtotal, v.igv, v.total, v.estado
        FROM venta v JOIN sucursal s ON s.id = v.sucursal_id
        LEFT JOIN cliente c ON c.id = v.cliente_id ORDER BY v.fecha DESC, v.id DESC";

    public function venta(int $id): ?array
    {
        $s = $this->db()->prepare("SELECT v.*, s.nombre AS sucursal, e.nombre AS cajero,
            COALESCE(c.nombre, 'Público general') AS cliente,
            CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante
            FROM venta v JOIN sucursal s ON s.id = v.sucursal_id JOIN empleado e ON e.id = v.empleado_id
            LEFT JOIN cliente c ON c.id = v.cliente_id WHERE v.id = ?");
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function detalle(int $id): array
    {
        $s = $this->db()->prepare('SELECT p.nombre AS producto, d.cantidad, d.precio_unitario, d.total_linea
            FROM detalle_venta d JOIN producto p ON p.id = d.producto_id WHERE d.venta_id = ?');
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public function pagos(int $id): array
    {
        $s = $this->db()->prepare("SELECT medio_pago, monto, COALESCE(referencia, '—') AS referencia FROM pago_venta WHERE venta_id = ?");
        $s->execute([$id]);
        return $s->fetchAll();
    }

    public function anular(int $id, string $motivo, int $empleadoId): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $v = $this->venta($id);
            if (!$v || $v['estado'] !== 'EMITIDA') {
                throw new RuntimeException('el comprobante no se puede anular.');
            }
            $db->prepare("UPDATE venta SET estado = 'ANULADA', motivo_anulacion = ? WHERE id = ?")->execute([$motivo, $id]);

            $m = $db->prepare("SELECT producto_id, cantidad FROM movimiento_stock WHERE venta_id = ? AND tipo = 'SALIDA'");
            $m->execute([$id]);
            $stock = new MovimientoStock($db);
            foreach ($m->fetchAll() as $f) {
                $stock->registrar([
                    'sucursal_id' => (int)$v['sucursal_id'], 'producto_id' => (int)$f['producto_id'],
                    'empleado_id' => $empleadoId, 'venta_id' => $id, 'tipo' => 'ENTRADA',
                    'cantidad' => $f['cantidad'], 'motivo' => 'Anulación ' . $v['comprobante'],
                ]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}