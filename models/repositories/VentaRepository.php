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

    public function ultimoDia(): string
    {
        $d = $this->db()->query("SELECT DATE(MAX(fecha)) FROM venta WHERE estado = 'EMITIDA'")->fetchColumn();
        return $d ?: date('Y-m-d');
    }

    public function opciones(): array
    {
        $db = $this->db();
        return [
            'sucursales' => $db->query('SELECT id, nombre FROM sucursal ORDER BY nombre')->fetchAll(PDO::FETCH_KEY_PAIR),
            'cajas' => $db->query("SELECT c.id, CONCAT(s.nombre, ' · ', c.nombre) FROM caja c
                JOIN sucursal s ON s.id = c.sucursal_id ORDER BY s.nombre, c.nombre")->fetchAll(PDO::FETCH_KEY_PAIR),
        ];
    }

    private function donde(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (!$f['todos']) {
            $w[] = 'v.fecha >= ? AND v.fecha < ?';
            $p[] = $f['dia'];
            $p[] = date('Y-m-d', strtotime($f['dia'] . ' +1 day'));
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $w[] = "(CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) LIKE ? OR c.nombre LIKE ?
                OR EXISTS (SELECT 1 FROM detalle_venta d JOIN producto p ON p.id = d.producto_id
                           WHERE d.venta_id = v.id AND p.nombre LIKE ?))";
            array_push($p, $like, $like, $like);
        }
        if ($f['sucursal']) { $w[] = 'v.sucursal_id = ?'; $p[] = $f['sucursal']; }
        if ($f['tipo'] !== '') { $w[] = 'v.tipo_comprobante = ?'; $p[] = $f['tipo']; }
        if ($f['medio'] !== '') {
            $w[] = 'EXISTS (SELECT 1 FROM pago_venta pv WHERE pv.venta_id = v.id AND pv.medio_pago = ?)';
            $p[] = $f['medio'];
        }
        if ($f['caja']) { $w[] = 'h.caja_id = ?'; $p[] = $f['caja']; }
        if ($f['min'] !== null) { $w[] = 'v.total >= ?'; $p[] = $f['min']; }
        if ($f['max'] !== null) { $w[] = 'v.total <= ?'; $p[] = $f['max']; }
        return [implode(' AND ', $w), $p];
    }

    public function listado(array $f): array
    {
        [$w, $p] = $this->donde($f);
        $ord = $f['orden'] === 'total' ? 'v.total' : 'v.fecha';
        $dir = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $s = $this->db()->prepare("SELECT v.id, s.nombre AS sucursal, v.tipo_comprobante AS tipo, v.estado,
            CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
            c.nombre AS cliente, v.fecha, v.total,
            (SELECT GROUP_CONCAT(DISTINCT pv.medio_pago ORDER BY pv.medio_pago SEPARATOR ', ')
               FROM pago_venta pv WHERE pv.venta_id = v.id) AS pagos
            FROM venta v
            JOIN sucursal s ON s.id = v.sucursal_id
            JOIN historia_caja h ON h.id = v.historia_caja_id
            LEFT JOIN cliente c ON c.id = v.cliente_id
            WHERE $w ORDER BY $ord $dir, v.id DESC");
        $s->execute($p);
        return $s->fetchAll();
    }

    public function reporte(string $dia): array
    {
        $db = $this->db();
        $fin = date('Y-m-d', strtotime($dia . ' +1 day'));
        $ant = date('Y-m-d', strtotime($dia . ' -1 day'));

        $resumen = function (string $a, string $b) use ($db) {
            $s = $db->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS total,
                COALESCE(SUM(tipo_comprobante = 'BOLETA'), 0) AS boletas,
                COALESCE(SUM(tipo_comprobante = 'FACTURA'), 0) AS facturas
                FROM venta WHERE estado = 'EMITIDA' AND fecha >= ? AND fecha < ?");
            $s->execute([$a, $b]);
            return $s->fetch();
        };

        $s = $db->prepare("SELECT s.nombre AS n, SUM(v.total) AS t FROM venta v
            JOIN sucursal s ON s.id = v.sucursal_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ? AND v.fecha < ?
            GROUP BY s.id, s.nombre ORDER BY t DESC");
        $s->execute([$dia, $fin]);
        $suc = $s->fetchAll();

        $s = $db->prepare("SELECT pv.medio_pago AS n, SUM(pv.monto) AS t FROM pago_venta pv
            JOIN venta v ON v.id = pv.venta_id
            WHERE v.estado = 'EMITIDA' AND v.fecha >= ? AND v.fecha < ?
            GROUP BY pv.medio_pago ORDER BY t DESC");
        $s->execute([$dia, $fin]);

        return ['hoy' => $resumen($dia, $fin), 'ayer' => $resumen($ant, $dia), 'suc' => $suc, 'pago' => $s->fetchAll()];
    }

    public function ficha(int $id): ?array
    {
        $s = $this->db()->prepare("SELECT v.id, v.estado, v.tipo_comprobante AS tipo, v.fecha, v.subtotal, v.igv, v.total,
            v.motivo_anulacion AS motivo, CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
            s.nombre AS sucursal, ca.nombre AS caja, e.nombre AS cajero,
            COALESCE(c.nombre, 'Cliente general') AS cliente
            FROM venta v
            JOIN sucursal s ON s.id = v.sucursal_id
            JOIN historia_caja h ON h.id = v.historia_caja_id
            JOIN caja ca ON ca.id = h.caja_id
            JOIN empleado e ON e.id = v.empleado_id
            LEFT JOIN cliente c ON c.id = v.cliente_id
            WHERE v.id = ?");
        $s->execute([$id]);
        $v = $s->fetch();
        if (!$v) {
            return null;
        }
        $v['items'] = $this->detalle($id);
        $v['pagos'] = $this->pagos($id);
        return $v;
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