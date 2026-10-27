<?php
require_once __DIR__ . '/../config/database.php';

class Incidente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function getAll(string $estado = '', int $sucursalId = 0): array
    {
        $sql = "SELECT i.id, i.sucursal_id, s.nombre AS sucursal, i.tipo,
                       i.descripcion, i.fecha, i.estado
                FROM incidente i JOIN sucursal s ON s.id = i.sucursal_id
                WHERE 1 = 1";
        $params = [];
        if ($estado !== '') {
            $sql .= " AND i.estado = ?";
            $params[] = $estado;
        }
        if ($sucursalId > 0) {
            $sql .= " AND i.sucursal_id = ?";
            $params[] = $sucursalId;
        }
        $sql .= " ORDER BY i.fecha DESC, i.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSucursales(): array
    {
        return $this->db->query("SELECT id, nombre FROM sucursal ORDER BY nombre")->fetchAll();
    }

    public function create(int $sucursalId, string $tipo, string $descripcion): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO incidente (sucursal_id, tipo, descripcion, fecha)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$sucursalId, $tipo, $descripcion]);
    }

    public function setState(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare("UPDATE incidente SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        return $stmt->rowCount() > 0;
    }
}
