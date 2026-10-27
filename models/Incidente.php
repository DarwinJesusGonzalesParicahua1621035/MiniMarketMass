<?php
class Incidente
{
    public function __construct(private PDO $db) {}

    public function listar(string $estado = '', int $sucursalId = 0): array
    {
        $sql = "SELECT i.id, i.sucursal_id, s.nombre AS sucursal, i.tipo,
                       i.descripcion, i.fecha, i.estado
                FROM incidente i JOIN sucursal s ON s.id = i.sucursal_id
                WHERE 1 = 1";
        $par = [];
        if ($estado !== '') {
            $sql .= " AND i.estado = ?";
            $par[] = $estado;
        }
        if ($sucursalId > 0) {
            $sql .= " AND i.sucursal_id = ?";
            $par[] = $sucursalId;
        }
        $sql .= " ORDER BY i.fecha DESC, i.id DESC";
        $st = $this->db->prepare($sql);
        $st->execute($par);
        return $st->fetchAll();
    }

    public function sucursales(): array
    {
        return $this->db->query("SELECT id, nombre FROM sucursal ORDER BY nombre")->fetchAll();
    }

    public function crear(int $sucursalId, string $tipo, string $descripcion): void
    {
        $this->db->prepare(
            "INSERT INTO incidente (sucursal_id, tipo, descripcion, fecha)
             VALUES (?, ?, ?, NOW())"
        )->execute([$sucursalId, $tipo, $descripcion]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE incidente SET estado = ? WHERE id = ?")
                 ->execute([$estado, $id]);
    }
}
