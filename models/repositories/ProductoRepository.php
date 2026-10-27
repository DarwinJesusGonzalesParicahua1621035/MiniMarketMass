<?php
class ProductoRepository extends Repository
{
    protected string $tabla = 'producto';
    protected string $listaSql = 'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria, p.unidad, p.precio, p.activo
        FROM producto p JOIN categoria c ON c.id = p.categoria_id ORDER BY p.id';

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
            $db->rollBack();
            throw $e;
        }
    }

    public function actualizar($id, array $d): void
    {
        $db = $this->db();
        $old = $this->buscar($id);
        $db->beginTransaction();
        try {
            parent::actualizar($id, $d);
            if ($old && (float)$old['precio'] !== (float)$d['precio']) {
                $a = $db->prepare('SELECT id, fecha_inicio < NOW() AS pasado FROM historial_precio WHERE producto_id = ? AND fecha_fin IS NULL');
                $a->execute([$id]);
                $h = $a->fetch();
                if ($h && $h['pasado']) {
                    $db->prepare('UPDATE historial_precio SET fecha_fin = NOW() WHERE id = ?')->execute([$h['id']]);
                } elseif ($h) {
                    $db->prepare('DELETE FROM historial_precio WHERE id = ?')->execute([$h['id']]);
                }
                $db->prepare('INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())')
                   ->execute([$id, $d['precio']]);
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function eliminar($id): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM historial_precio WHERE producto_id = ?')->execute([$id]);
            parent::eliminar($id);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}