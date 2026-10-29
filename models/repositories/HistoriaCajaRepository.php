<?php
class HistoriaCajaRepository extends Repository
{
    protected string $tabla = 'historia_caja';
    protected string $listaSql = 'SELECT h.id, s.nombre AS sucursal, c.nombre AS caja, e.nombre AS empleado,
        h.apertura, h.cierre, h.monto_inicial, h.monto_final, h.estado
        FROM historia_caja h
        JOIN caja c ON c.id = h.caja_id
        JOIN sucursal s ON s.id = c.sucursal_id
        JOIN empleado e ON e.id = h.empleado_id
        ORDER BY h.apertura DESC';

    public function historial(?string $estado = null, int $limite = 200): array
    {
        $sql = 'SELECT h.id, s.nombre AS sucursal, c.nombre AS caja, e.nombre AS empleado,
                h.apertura, h.cierre, h.monto_inicial, h.monto_final, h.estado
                FROM historia_caja h
                JOIN caja c ON c.id = h.caja_id
                JOIN sucursal s ON s.id = c.sucursal_id
                JOIN empleado e ON e.id = h.empleado_id '
             . ($estado ? 'WHERE h.estado = ? ' : '')
             . 'ORDER BY h.apertura DESC, h.id DESC LIMIT ' . (int)$limite;
        $s = $this->db()->prepare($sql);
        $s->execute($estado ? [$estado] : []);
        return $s->fetchAll();
    }

    // Turnos abiertos, para el formulario de cierre.
    public function abiertas(): array
    {
        $o = [];
        $q = "SELECT h.id, CONCAT(s.nombre, ' · ', c.nombre, ' · ', e.nombre,
                     ' (desde ', DATE_FORMAT(h.apertura, '%d/%m %H:%i'), ', S/ ', h.monto_inicial, ')') AS texto
              FROM historia_caja h
              JOIN caja c ON c.id = h.caja_id
              JOIN sucursal s ON s.id = c.sucursal_id
              JOIN empleado e ON e.id = h.empleado_id
              WHERE h.estado = 'ABIERTA'
              ORDER BY s.nombre, c.nombre";
        foreach ($this->db()->query($q) as $r) {
            $o[$r['id']] = $r['texto'];
        }
        return $o;
    }

    public function abrir(int $cajaId, int $empleadoId, string $montoInicial): int
    {
        if ($cajaId <= 0) {
            throw new InvalidArgumentException('elige una caja.');
        }
        if (!is_numeric($montoInicial) || (float)$montoInicial < 0) {
            throw new InvalidArgumentException('el monto inicial debe ser un número mayor o igual a 0.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try {
            // Bloquea la caja para que dos personas no abran el mismo turno a la vez.
            $s = $db->prepare('SELECT id FROM caja WHERE id = ? FOR UPDATE');
            $s->execute([$cajaId]);
            if (!$s->fetch()) {
                throw new RuntimeException('la caja no existe.');
            }
            $s = $db->prepare("SELECT 1 FROM historia_caja WHERE caja_id = ? AND estado = 'ABIERTA'");
            $s->execute([$cajaId]);
            if ($s->fetch()) {
                throw new RuntimeException('esa caja ya tiene un turno abierto.');
            }
            $s = $db->prepare("INSERT INTO historia_caja (caja_id, empleado_id, apertura, monto_inicial, estado)
                               VALUES (?, ?, NOW(), ?, 'ABIERTA')");
            $s->execute([$cajaId, $empleadoId, round((float)$montoInicial, 2)]);
            $id = (int)$db->lastInsertId();
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function cerrar(int $historiaId, string $montoFinal): void
    {
        if ($historiaId <= 0) {
            throw new InvalidArgumentException('elige un turno abierto.');
        }
        if (!is_numeric($montoFinal) || (float)$montoFinal < 0) {
            throw new InvalidArgumentException('el monto final debe ser un número mayor o igual a 0.');
        }
        $s = $this->db()->prepare("UPDATE historia_caja
                                   SET cierre = NOW(), monto_final = ?, estado = 'CERRADA'
                                   WHERE id = ? AND estado = 'ABIERTA'");
        $s->execute([round((float)$montoFinal, 2), $historiaId]);
        if ($s->rowCount() !== 1) {
            throw new RuntimeException('ese turno no existe o ya estaba cerrado.');
        }
    }
}
