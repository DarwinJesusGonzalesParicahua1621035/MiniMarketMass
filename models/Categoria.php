<?php
class Categoria
{
    public function __construct(private PDO $db) {}

    public function todas(): array
    {
        return $this->db->query(
            "SELECT c.id, c.nombre, COUNT(p.id) AS total_productos
             FROM categoria c LEFT JOIN producto p ON p.categoria_id = c.id
             GROUP BY c.id, c.nombre ORDER BY c.nombre"
        )->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare("SELECT id, nombre FROM categoria WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(string $nombre): int
    {
        $this->db->prepare("INSERT INTO categoria (nombre) VALUES (?)")->execute([$nombre]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre): void
    {
        $this->db->prepare("UPDATE categoria SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);
    }

    public function eliminar(int $id): void
    {
        $this->db->prepare("DELETE FROM categoria WHERE id = ?")->execute([$id]);
    }
}