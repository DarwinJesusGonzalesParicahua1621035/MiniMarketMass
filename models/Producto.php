<?php
class Producto
{
    public function __construct(private PDO $db) {}

    public function listar(string $q = '', int $categoriaId = 0, string $estado = ''): array
    {
        $sql = "SELECT p.id, p.codigo_barras, p.nombre, p.unidad, p.precio, p.activo,
                       c.nombre AS categoria
                FROM producto p JOIN categoria c ON c.id = p.categoria_id WHERE 1=1";
        $par = [];
        if ($q !== '') {
            $sql .= " AND (p.nombre LIKE :q1 OR p.codigo_barras LIKE :q2)";
            $par[':q1'] = $par[':q2'] = "%$q%";
        }
        if ($categoriaId > 0) { $sql .= " AND p.categoria_id = :cat"; $par[':cat'] = $categoriaId; }
        if ($estado === 'activo')   $sql .= " AND p.activo = 1";
        if ($estado === 'inactivo') $sql .= " AND p.activo = 0";
        $sql .= " ORDER BY p.nombre";
        $st = $this->db->prepare($sql);
        $st->execute($par);
        return $st->fetchAll();
    }

    public function buscar(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM producto WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function crear(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO producto (categoria_id, codigo_barras, nombre, unidad, precio)
                 VALUES (?, ?, ?, ?, ?)"
            )->execute([$d['categoria_id'], $d['codigo_barras'], $d['nombre'], $d['unidad'], $d['precio']]);
            $id = (int)$this->db->lastInsertId();

            $this->db->prepare(
                "INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())"
            )->execute([$id, $d['precio']]);

            $this->db->commit();
            return $id;
        } catch (Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    public function actualizar(int $id, array $d): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT precio FROM producto WHERE id = ? FOR UPDATE");
            $st->execute([$id]);
            $actual = $st->fetch();
            if (!$actual) { throw new RuntimeException('Producto no encontrado'); }

            $this->db->prepare(
                "UPDATE producto SET categoria_id = ?, codigo_barras = ?, nombre = ?, unidad = ?, precio = ?
                 WHERE id = ?"
            )->execute([$d['categoria_id'], $d['codigo_barras'], $d['nombre'], $d['unidad'], $d['precio'], $id]);

            if (round((float)$actual['precio'], 2) !== round((float)$d['precio'], 2)) {
                $this->rotarPrecio($id, $d['precio']);
            }
            $this->db->commit();
        } catch (Throwable $ex) {
            $this->db->rollBack();
            throw $ex;
        }
    }

    // Cierra el precio vigente y abre uno nuevo (regla: fecha_fin > fecha_inicio y un inicio por producto).
    private function rotarPrecio(int $id, string $precio): void
    {
        $st = $this->db->prepare(
            "SELECT id, (fecha_inicio >= NOW()) AS mismo_instante
             FROM historial_precio WHERE producto_id = ? AND fecha_fin IS NULL
             ORDER BY fecha_inicio DESC LIMIT 1"
        );
        $st->execute([$id]);
        $abierto = $st->fetch();

        if ($abierto && $abierto['mismo_instante']) {
            // Cambio en el mismo segundo del anterior: se corrige ese registro en vez de crear otro.
            $this->db->prepare("UPDATE historial_precio SET precio = ? WHERE id = ?")
                     ->execute([$precio, $abierto['id']]);
            return;
        }
        if ($abierto) {
            $this->db->prepare("UPDATE historial_precio SET fecha_fin = NOW() WHERE id = ?")
                     ->execute([$abierto['id']]);
        }
        $this->db->prepare(
            "INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())"
        )->execute([$id, $precio]);
    }

    public function cambiarEstado(int $id, int $activo): void
    {
        $this->db->prepare("UPDATE producto SET activo = ? WHERE id = ?")
                 ->execute([$activo === 1 ? 1 : 0, $id]);
    }

    public function historial(int $id): array
    {
        $st = $this->db->prepare(
            "SELECT precio, fecha_inicio, fecha_fin FROM historial_precio
             WHERE producto_id = ? ORDER BY fecha_inicio DESC"
        );
        $st->execute([$id]);
        return $st->fetchAll();
    }
}