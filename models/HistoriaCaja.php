<?php
class HistoriaCaja
{
    public function __construct(private PDO $db) {}

    public function listar(string $estado = ''): array
    {
        $sql = "SELECT h.id, h.caja_id, c.nombre AS caja, s.nombre AS sucursal,
                       e.nombre AS empleado, h.apertura, h.cierre, h.monto_inicial,
                       h.monto_final, h.estado
                FROM historia_caja h
                JOIN caja c ON c.id = h.caja_id
                JOIN sucursal s ON s.id = c.sucursal_id
                JOIN empleado e ON e.id = h.empleado_id";
        $par = [];
        if ($estado !== '') {
            $sql .= " WHERE h.estado = ?";
            $par[] = $estado;
        }
        $sql .= " ORDER BY h.apertura DESC, h.id DESC";
        $st = $this->db->prepare($sql);
        $st->execute($par);
        return $st->fetchAll();
    }

    public function cajas(): array
    {
        return $this->db->query(
            "SELECT c.id, c.nombre, s.nombre AS sucursal
             FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
             ORDER BY s.nombre, c.nombre"
        )->fetchAll();
    }

    public function empleados(): array
    {
        return $this->db->query(
            "SELECT e.id, e.nombre, e.sucursal_id, s.nombre AS sucursal
             FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id
             WHERE e.activo = 1 ORDER BY s.nombre, e.nombre"
        )->fetchAll();
    }

    public function abrir(int $cajaId, int $empleadoId, string $montoInicial): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT sucursal_id FROM caja WHERE id = ? FOR UPDATE");
            $st->execute([$cajaId]);
            $caja = $st->fetch();
            if (!$caja) {
                throw new RuntimeException('La caja seleccionada no existe.');
            }

            $st = $this->db->prepare(
                "SELECT id FROM empleado WHERE id = ? AND sucursal_id = ? AND activo = 1"
            );
            $st->execute([$empleadoId, $caja['sucursal_id']]);
            if (!$st->fetch()) {
                throw new RuntimeException('El empleado debe estar activo y pertenecer a la sucursal de la caja.');
            }

            $st = $this->db->prepare(
                "SELECT id FROM historia_caja WHERE caja_id = ? AND estado = 'ABIERTA' LIMIT 1"
            );
            $st->execute([$cajaId]);
            if ($st->fetch()) {
                throw new RuntimeException('Esta caja ya tiene una apertura vigente.');
            }

            $this->db->prepare(
                "INSERT INTO historia_caja (caja_id, empleado_id, apertura, monto_inicial)
                 VALUES (?, ?, NOW(), ?)"
            )->execute([$cajaId, $empleadoId, $montoInicial]);
            $this->db->commit();
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $ex;
        }
    }

    public function cerrar(int $id, string $montoFinal): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT estado FROM historia_caja WHERE id = ? FOR UPDATE");
            $st->execute([$id]);
            $historia = $st->fetch();
            if (!$historia) {
                throw new RuntimeException('La apertura de caja no existe.');
            }
            if ($historia['estado'] !== 'ABIERTA') {
                throw new RuntimeException('Esta apertura ya fue cerrada.');
            }

            $this->db->prepare(
                "UPDATE historia_caja
                 SET cierre = NOW(), monto_final = ?, estado = 'CERRADA'
                 WHERE id = ?"
            )->execute([$montoFinal, $id]);
            $this->db->commit();
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $ex;
        }
    }
}
