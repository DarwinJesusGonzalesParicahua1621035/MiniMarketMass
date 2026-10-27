<?php
require_once __DIR__ . '/../config/database.php';

class Caja
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(): array
    {
        return $this->db->query(
            "SELECT c.id, c.sucursal_id, c.nombre, s.nombre AS sucursal
             FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
             ORDER BY s.nombre, c.nombre"
        )->fetchAll();
    }

    public function getSucursales(): array
    {
        return $this->db->query("SELECT id, nombre FROM sucursal ORDER BY nombre")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, sucursal_id, nombre FROM caja WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(int $id, int $sucursalId, string $nombre): void
    {
        if ($id === 0) {
            $stmt = $this->db->prepare("INSERT INTO caja (sucursal_id, nombre) VALUES (?, ?)");
            $stmt->execute([$sucursalId, $nombre]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT sucursal_id FROM caja WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if (!$existing) {
                throw new RuntimeException('No se encontró la caja.');
            }
            if ((int)$existing['sucursal_id'] !== $sucursalId) {
                $stmt = $this->db->prepare("SELECT id FROM historia_caja WHERE caja_id = ? LIMIT 1");
                $stmt->execute([$id]);
                if ($stmt->fetch()) {
                    throw new RuntimeException('No se puede cambiar de sucursal una caja con historial.');
                }
            }

            $stmt = $this->db->prepare("UPDATE caja SET sucursal_id = ?, nombre = ? WHERE id = ?");
            $stmt->execute([$sucursalId, $nombre, $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM caja WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
