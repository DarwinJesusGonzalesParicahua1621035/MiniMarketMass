<?php
class ProductoRepository extends Repository
{
    protected string $tabla = 'producto';
    protected string $listaSql = 'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria,
        p.unidad, p.precio, p.activo
        FROM producto p JOIN categoria c ON c.id = p.categoria_id ORDER BY p.nombre';

    // Lista de productos; si se da una categoría, solo muestra esa.
    public function resumen(?int $categoriaId = null): array
    {
        $sql = 'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria,
                p.unidad, p.precio, p.activo
                FROM producto p JOIN categoria c ON c.id = p.categoria_id '
             . ($categoriaId ? 'WHERE p.categoria_id = ? ' : '')
             . 'ORDER BY p.nombre';
        $s = $this->db()->prepare($sql);
        $s->execute($categoriaId ? [$categoriaId] : []);
        return $s->fetchAll();
    }

    // Un producto con el nombre de su categoría.
    public function detalle(int $id): ?array
    {
        $s = $this->db()->prepare(
            'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria, p.unidad, p.precio, p.activo
             FROM producto p JOIN categoria c ON c.id = p.categoria_id WHERE p.id = ?'
        );
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function existeCodigo(string $codigo, int $excluirId = 0): bool
    {
        $s = $this->db()->prepare('SELECT COUNT(*) FROM producto WHERE codigo_barras = ? AND id <> ?');
        $s->execute([$codigo, $excluirId]);
        return (int)$s->fetchColumn() > 0;
    }

    // Precios del producto, del más reciente al más antiguo.
    public function historial(int $productoId): array
    {
        $s = $this->db()->prepare(
            'SELECT id, precio, fecha_inicio, fecha_fin FROM historial_precio
             WHERE producto_id = ? ORDER BY fecha_inicio DESC'
        );
        $s->execute([$productoId]);
        return $s->fetchAll();
    }

    // Crea el producto y abre su primer precio en historial_precio (misma transacción).
    public function insertar(array $d): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $id = parent::insertar($d);
            $db->prepare('INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())')
               ->execute([$id, $d['precio']]);
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    // Actualiza el producto; si cambió el precio, cierra el vigente y abre uno nuevo.
    public function actualizar($id, array $d): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT precio FROM producto WHERE id = ? FOR UPDATE');
            $s->execute([$id]);
            $anterior = $s->fetchColumn();
            if ($anterior === false) {
                throw new RuntimeException('el producto no existe.');
            }
            parent::actualizar($id, $d);
            if (round((float)$anterior, 2) !== round((float)$d['precio'], 2)) {
                $this->cambiarPrecio((int)$id, $d['precio']);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    // Se llama dentro de la transacción de actualizar().
    private function cambiarPrecio(int $id, $precio): void
    {
        $db = $this->db();
        $s = $db->prepare('SELECT COUNT(*) FROM historial_precio WHERE producto_id = ? AND fecha_inicio >= NOW()');
        $s->execute([$id]);
        if ((int)$s->fetchColumn() > 0) {
            throw new RuntimeException('el precio ya cambió hace menos de un segundo, intenta de nuevo.');
        }
        $db->prepare('UPDATE historial_precio SET fecha_fin = NOW() WHERE producto_id = ? AND fecha_fin IS NULL')
           ->execute([$id]);
        $db->prepare('INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())')
           ->execute([$id, $precio]);
    }
}