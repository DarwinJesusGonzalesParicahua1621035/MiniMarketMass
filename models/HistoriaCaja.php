<?php
require_once __DIR__ . '/../config/database.php';

class HistoriaCaja
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $estado = ''): array
    {
        $sql = "SELECT h.id, c.nombre AS caja, s.nombre AS sucursal,
                       e.nombre AS empleado, h.apertura, h.cierre, h.monto_inicial,
                       h.monto_final, h.estado
                FROM historia_caja h
                JOIN caja c ON c.id = h.caja_id
                JOIN sucursal s ON s.id = c.sucursal_id
                JOIN empleado e ON e.id = h.empleado_id";
        $params = [];
        if ($estado !== '') {
            $sql .= " WHERE h.estado = ?";
            $params[] = $estado;
        }
        $sql .= " ORDER BY h.apertura DESC, h.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCajas(): array
    {
        return $this->db->query(
            "SELECT c.id, c.nombre, s.nombre AS sucursal
             FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
             ORDER BY s.nombre, c.nombre"
        )->fetchAll();
    }

    public function getEmpleados(): array
    {
        return $this->db->query(
            "SELECT e.id, e.nombre, e.sucursal_id, s.nombre AS sucursal
             FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id
             WHERE e.activo = 1 ORDER BY s.nombre, e.nombre"
        )->fetchAll();
    }

    public function open(int $cajaId, int $empleadoId, string $montoInicial): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT sucursal_id FROM caja WHERE id = ? FOR UPDATE");
            $stmt->execute([$cajaId]);
            $caja = $stmt->fetch();
            if (!$caja) {
                throw new RuntimeException('La caja seleccionada no existe.');
            }

            $stmt = $this->db->prepare(
                "SELECT id FROM empleado WHERE id = ? AND sucursal_id = ? AND activo = 1"
            );
            $stmt->execute([$empleadoId, $caja['sucursal_id']]);
            if (!$stmt->fetch()) {
                throw new RuntimeException('El empleado debe estar activo y pertenecer a la sucursal de la caja.');
            }

            $stmt = $this->db->prepare(
                "SELECT id FROM historia_caja WHERE caja_id = ? AND estado = 'ABIERTA' LIMIT 1"
            );
            $stmt->execute([$cajaId]);
            if ($stmt->fetch()) {
                throw new RuntimeException('Esta caja ya tiene una apertura vigente.');
            }

            $stmt = $this->db->prepare(
                "INSERT INTO historia_caja (caja_id, empleado_id, apertura, monto_inicial)
                 VALUES (?, ?, NOW(), ?)"
            );
            $stmt->execute([$cajaId, $empleadoId, $montoInicial]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function close(int $id, string $montoFinal): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT estado FROM historia_caja WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $history = $stmt->fetch();
            if (!$history) {
                throw new RuntimeException('La apertura de caja no existe.');
            }
            if ($history['estado'] !== 'ABIERTA') {
                throw new RuntimeException('Esta apertura ya fue cerrada.');
            }

            $stmt = $this->db->prepare(
                "UPDATE historia_caja
                 SET cierre = NOW(), monto_final = ?, estado = 'CERRADA'
                 WHERE id = ?"
            );
            $stmt->execute([$montoFinal, $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
