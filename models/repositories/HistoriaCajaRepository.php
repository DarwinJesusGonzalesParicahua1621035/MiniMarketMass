<?php
class HistoriaCajaRepository extends Repository
{
    protected string $tabla = 'historia_caja';
    protected string $listaSql = 'SELECT id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado
        FROM historia_caja ORDER BY apertura DESC';

    private function base(): string
    {
        return "SELECT h.id, h.caja_id, c.sucursal_id, s.nombre AS sucursal, c.nombre AS caja,
                h.empleado_id, e.nombre AS resp, h.apertura, h.cierre,
                h.monto_inicial AS ini, h.monto_final, h.estado,
                COALESCE(vt.n, 0) AS n, COALESCE(vt.tot, 0) AS tot,
                COALESCE(pg.ef, 0) AS ef, COALESCE(pg.ya, 0) AS ya,
                COALESCE(pg.pl, 0) AS pl, COALESCE(pg.ta, 0) AS ta,
                IF(h.estado = 'CERRADA', h.monto_final - (h.monto_inicial + COALESCE(pg.ef, 0)), NULL) AS dif
            FROM historia_caja h
            JOIN caja c ON c.id = h.caja_id
            JOIN sucursal s ON s.id = c.sucursal_id
            JOIN empleado e ON e.id = h.empleado_id
            LEFT JOIN (
                SELECT historia_caja_id AS hid, COUNT(*) AS n, SUM(total) AS tot
                FROM venta WHERE estado = 'EMITIDA' GROUP BY historia_caja_id
            ) vt ON vt.hid = h.id
            LEFT JOIN (
                SELECT v.historia_caja_id AS hid,
                    SUM(CASE WHEN pv.medio_pago = 'EFECTIVO' THEN pv.monto ELSE 0 END) AS ef,
                    SUM(CASE WHEN pv.medio_pago = 'YAPE' THEN pv.monto ELSE 0 END) AS ya,
                    SUM(CASE WHEN pv.medio_pago = 'PLIN' THEN pv.monto ELSE 0 END) AS pl,
                    SUM(CASE WHEN pv.medio_pago = 'TARJETA' THEN pv.monto ELSE 0 END) AS ta
                FROM venta v JOIN pago_venta pv ON pv.venta_id = v.id
                WHERE v.estado = 'EMITIDA' GROUP BY v.historia_caja_id
            ) pg ON pg.hid = h.id";
    }

    private function donde(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if ($f['desde'] !== '') {
            $w[] = 't.apertura >= ?';
            $p[] = $f['desde'];
        }
        if ($f['hasta'] !== '') {
            $w[] = 't.apertura < ?';
            $p[] = date('Y-m-d', strtotime($f['hasta'] . ' +1 day'));
        }
        if ($f['sucursal']) {
            $w[] = 't.sucursal_id = ?';
            $p[] = $f['sucursal'];
        }
        if ($f['resp']) {
            $w[] = 't.empleado_id = ?';
            $p[] = $f['resp'];
        }
        if ($f['estado'] !== '') {
            $w[] = 't.estado = ?';
            $p[] = $f['estado'];
        }
        if ($f['dif'] === 'cuadra') {
            $w[] = "t.estado = 'CERRADA' AND t.dif = 0";
        } elseif ($f['dif'] === 'falta') {
            $w[] = 't.dif < 0';
        } elseif ($f['dif'] === 'sobra') {
            $w[] = 't.dif > 0';
        }
        if ($f['q'] !== '') {
            $w[] = "CONCAT(t.id, ' ', t.caja, ' ', t.sucursal) LIKE ?";
            $p[] = '%' . ltrim($f['q'], '#') . '%';
        }
        return [implode(' AND ', $w), $p];
    }

    public function listado(array $f, int $limite, int $desplazamiento = 0): array
    {
        [$w, $p] = $this->donde($f);
        $col = ['apertura' => 't.apertura', 'tot' => 't.tot', 'dif' => 't.dif'][$f['orden']] ?? 't.apertura';
        $dir = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $sql = 'SELECT t.* FROM (' . $this->base() . ") t WHERE $w ORDER BY $col $dir, t.id DESC"
             . ' LIMIT ' . max(1, $limite) . ' OFFSET ' . max(0, $desplazamiento);
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    public function reporte(array $f): array
    {
        [$w, $p] = $this->donde($f);
        $s = $this->db()->prepare('SELECT COUNT(*) AS n FROM (' . $this->base() . ") t WHERE $w");
        $s->execute($p);
        return $s->fetch();
    }

    public function ventasDe(int $historiaId): ?array
    {
        $db = $this->db();
        $s = $db->prepare('SELECT h.id, h.estado, s.nombre AS sucursal, c.nombre AS caja
            FROM historia_caja h
            JOIN caja c ON c.id = h.caja_id
            JOIN sucursal s ON s.id = c.sucursal_id
            WHERE h.id = ?');
        $s->execute([$historiaId]);
        $t = $s->fetch();
        if (!$t) {
            return null;
        }
        $s = $db->prepare("SELECT v.id, v.estado, v.tipo_comprobante AS tipo, v.fecha, v.total,
            CONCAT(v.serie, '-', LPAD(v.correlativo, 6, '0')) AS comprobante,
            COALESCE(c.nombre, 'Cliente general') AS cliente,
            (SELECT GROUP_CONCAT(DISTINCT pv.medio_pago ORDER BY pv.medio_pago SEPARATOR ', ')
               FROM pago_venta pv WHERE pv.venta_id = v.id) AS pagos
            FROM venta v
            LEFT JOIN cliente c ON c.id = v.cliente_id
            WHERE v.historia_caja_id = ?
            ORDER BY v.fecha DESC, v.id DESC");
        $s->execute([$historiaId]);
        $t['ventas'] = $s->fetchAll();
        return $t;
    }

    public function abrir(int $cajaId, int $empleadoId, string $montoInicial): int
    {
        if ($cajaId <= 0) {
            throw new InvalidArgumentException('elige una caja.');
        }
        if ($empleadoId <= 0) {
            throw new InvalidArgumentException('elige un responsable.');
        }
        if (!is_numeric($montoInicial) || (float)$montoInicial < 0) {
            throw new InvalidArgumentException('el monto inicial debe ser un número mayor o igual a 0.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT id FROM caja WHERE id = ? FOR UPDATE');
            $s->execute([$cajaId]);
            if (!$s->fetch()) {
                throw new RuntimeException('la caja no existe.');
            }
            $s = $db->prepare('SELECT 1 FROM empleado WHERE id = ? AND activo = 1');
            $s->execute([$empleadoId]);
            if (!$s->fetch()) {
                throw new RuntimeException('el responsable no existe o está inactivo.');
            }
            $s = $db->prepare("SELECT 1 FROM historia_caja WHERE caja_id = ? AND estado = 'ABIERTA'");
            $s->execute([$cajaId]);
            if ($s->fetch()) {
                throw new RuntimeException('esa caja ya está abierta.');
            }
            $s = $db->prepare("INSERT INTO historia_caja (caja_id, empleado_id, apertura, monto_inicial, estado)
                               VALUES (?, ?, NOW(), ?, 'ABIERTA')");
            $s->execute([$cajaId, $empleadoId, round((float)$montoInicial, 2)]);
            $id = (int)$db->lastInsertId();
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function cerrar(int $historiaId, string $contado): void
    {
        if ($historiaId <= 0) {
            throw new InvalidArgumentException('elige una caja abierta.');
        }
        if (!is_numeric($contado) || (float)$contado < 0) {
            throw new InvalidArgumentException('el efectivo contado debe ser un número mayor o igual a 0.');
        }
        $s = $this->db()->prepare("UPDATE historia_caja
                                   SET cierre = NOW(), monto_final = ?, estado = 'CERRADA'
                                   WHERE id = ? AND estado = 'ABIERTA'");
        $s->execute([round((float)$contado, 2), $historiaId]);
        if ($s->rowCount() !== 1) {
            throw new RuntimeException('esa caja no está abierta o ya fue cerrada.');
        }
    }
}