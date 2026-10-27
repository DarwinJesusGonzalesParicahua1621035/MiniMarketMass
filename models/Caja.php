<?php
class Caja
{
    public function __construct(private PDO $db) {}

    public function listar(): array
    {
        return $this->db->query(
            "SELECT c.id, c.sucursal_id, c.nombre, s.nombre AS sucursal
             FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
             ORDER BY s.nombre, c.nombre"
        )->fetchAll();
    }

    public function sucursales(): array
    {
        return $this->db->query("SELECT id, nombre FROM sucursal ORDER BY nombre")->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare("SELECT id, sucursal_id, nombre FROM caja WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function guardar(int $id, int $sucursalId, string $nombre): void
    {
        if ($id <= 0) {
            $this->db->prepare("INSERT INTO caja (sucursal_id, nombre) VALUES (?, ?)")
                     ->execute([$sucursalId, $nombre]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT sucursal_id FROM caja WHERE id = ? FOR UPDATE");
            $st->execute([$id]);
            $actual = $st->fetch();
            if (!$actual) {
                throw new RuntimeException('Caja no encontrada.');
            }
            if ((int)$actual['sucursal_id'] !== $sucursalId) {
                $st = $this->db->prepare("SELECT id FROM historia_caja WHERE caja_id = ? LIMIT 1");
                $st->execute([$id]);
                if ($st->fetch()) {
                    throw new RuntimeException('No se puede cambiar de sucursal una caja con historial registrado.');
                }
            }
            $this->db->prepare("UPDATE caja SET sucursal_id = ?, nombre = ? WHERE id = ?")
                     ->execute([$sucursalId, $nombre, $id]);
            $this->db->commit();
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $ex;
        }
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM caja WHERE id = ?")->execute([$id]);
    }
}
