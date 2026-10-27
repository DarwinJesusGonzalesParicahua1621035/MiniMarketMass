<?php
abstract class Repository
{
    protected string $tabla;
    protected string $listaSql;

    protected function db(): PDO { return Database::pdo(); }

    public function todos(): array { return $this->db()->query($this->listaSql)->fetchAll(); }

    public function buscar($id): ?array
    {
        $s = $this->db()->prepare("SELECT * FROM {$this->tabla} WHERE id = ?");
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function insertar(array $d): int
    {
        $c = array_keys($d);
        $s = $this->db()->prepare("INSERT INTO {$this->tabla} (" . implode(',', $c) . ') VALUES (' . implode(',', array_fill(0, count($c), '?')) . ')');
        $s->execute(array_values($d));
        return (int)$this->db()->lastInsertId();
    }

    public function actualizar($id, array $d): void
    {
        $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($d)));
        $s = $this->db()->prepare("UPDATE {$this->tabla} SET $set WHERE id = ?");
        $s->execute([...array_values($d), $id]);
    }

    public function eliminar($id): void
    {
        $this->db()->prepare("DELETE FROM {$this->tabla} WHERE id = ?")->execute([$id]);
    }

    public function pares(string $sql): array
    {
        $o = [];
        foreach ($this->db()->query($sql) as $r) {
            $o[$r['id']] = $r['nombre'];
        }
        return $o;
    }
}